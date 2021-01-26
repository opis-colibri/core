<?php
/* ============================================================================
 * Copyright 2021 Zindex Software
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Colibri\FileSystem\Handler;

use Aws\S3\S3Client;
use Aws\S3\BatchDelete;
use Aws\ResultInterface;
use Aws\S3\Exception\{DeleteMultipleObjectsException, S3Exception};
use Opis\Colibri\Stream\Stream;
use Opis\Colibri\Stream\Scanner\MimeScanner;
use Psr\Http\Message\StreamInterface;
use Opis\Colibri\FileSystem\{Directory, FileInfo, FileStream, Stat};
use function Aws\flatmap;

class S3FileHandler implements FileSystemHandler, SearchHandler
{
    protected const TEMP_STREAM = 'php://temp/maxmemory:' . (10 * 1024 * 1024); // 10 MB

    protected S3Client $client;
    protected string $bucket;
    protected array $options;
    protected int $defaultMode;
    protected ?MimeScanner $typeScanner = null;
    /** @var callable|null */
    protected $url = null;

    public function __construct(S3Client $client, string $bucket, ?callable $url = null, int $defaultMode = 0777)
    {
        $this->client = $client;
        $this->bucket = $bucket;
        $this->url = $url;
        $this->defaultMode = $defaultMode;

        $this->options = [
            'Bucket' => $bucket,
            'ACL' => $this->determineAcl($defaultMode),
        ];
    }

    public function mkdir(string $path, int $mode = 0777, bool $recursive = true): ?FileInfo
    {
        $parts = $this->getParts($path);

        if (!isset($parts['ACL'])) {
            $parts['ACL'] = $this->determineAcl($mode);
        }

        if (!isset($parts['Key'])) {
            if ($this->client->doesBucketExist($parts['Bucket'])) {
                return null;
            }
            try {
                $this->client->createBucket($parts['Bucket']);
            } catch (S3Exception) {
                return null;
            }
            return $this->createInfo($parts, $mode);
        }

        $parts['Key'] .= '/';
        $parts['Body'] = '';

        if ($this->client->doesObjectExist($parts['Bucket'], $parts['Key'])) {
            // already exists
            return null;
        }

        try {
            $this->client->putObject($parts);
        } catch (S3Exception) {
            return null;
        }

        return $this->createInfo($path, $mode);
    }

    public function rmdir(string $path, bool $recursive = true): bool
    {
        $parts = $this->getParts($path);

        if (!isset($parts['Key'])) {
            try {
                $this->client->deleteBucket($parts);
                return true;
            } catch (S3Exception) {
                return false;
            }
        }

        $prefix = rtrim($parts['Key'], '/') . '/';

        if ($recursive) {
            try {
                BatchDelete::fromListObjects($this->client, [
                    'Bucket' => $parts['Bucket'],
                    'Delimiter' => '/',
                    'Prefix' => $prefix,
                ])->delete();
            } catch (DeleteMultipleObjectsException) {
                return false;
            }
            return true;
        }

        try {
            $result = $this->client->listObjects([
                'Bucket' => $parts['Bucket'],
                'Prefix' => $prefix,
                'MaxKeys' => 1,
            ]);
        } catch (S3Exception) {
            return false;
        }

        // Check if the bucket contains keys other than the placeholder
        if ($contents = $result['Contents']) {
            if (count($contents) > 1 || $contents[0]['Key'] != $prefix) {
                // Sub-folder not empty
                return false;
            }

            try {
                $this->client->deleteObject([
                    'Bucket' => $parts['Bucket'],
                    'Key' => $prefix,
                ]);
            } catch (S3Exception) {
                return false;
            }

            return true;
        }

        // Check if there are nested sub-folders
        return $result['CommonPrefixes'] ? false : true;
    }

    public function unlink(string $path): bool
    {
        $parts = $this->getParts($path);

        if (!isset($parts['Key'])) {
            return false;
        }

        $data = $this->getPartsInfo($parts);
        if (!$data || $data['dir']) {
            return false;
        }

        try {
            $this->client->deleteObject($parts);
            return true;
        } catch (S3Exception) {
            return false;
        }
    }

    public function rename(string $from, string $to): ?FileInfo
    {
        return $this->doCopy($from, $to, true, false);
    }

    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo
    {
        return $this->doCopy($from, $from, false, $overwrite);
    }

    public function stat(string $path, bool $resolve_links = true): ?Stat
    {
        $data = $this->getPartsInfo($path);

        if ($data === null) {
            return null;
        }

        if ($data['dir']) {
            return new Stat\DirectoryStat($this->defaultMode, $data['time']);
        }

        return new Stat\FileStat($this->defaultMode, $data['size'], $data['time']);
    }

    public function write(string $path, Stream $stream, int $mode = 0777): ?FileInfo
    {
        $parts = $this->getParts($path);
        if (!isset($parts['Key']) || $parts['Key'][-1] === '/') {
            return null;
        }

        $data = $this->getPartsInfo($path);
        if ($data && $data['dir']) {
            return null;
        }
        unset($data);

        $parts['ACL'] = $this->determineAcl($mode);

        if (($size = $stream->size()) !== null) {
            $parts['ContentLength'] = $size;
        }

        if ($body = $stream->resource()) {
            if (get_resource_type($body) !== 'stream') {
                $body = null;
            }
        }

        if ($body === null) {
            $body = (string)$stream;
        }

        if ($type = $this->typeScanner()->mime($stream)) {
            $parts['ContentType'] = $type;
        }

        $parts['Body'] = $body;

        try {
            $this->client->putObject($parts);
        } catch (S3Exception) {
            return null;
        }

        return $this->createInfo($parts, $mode);
    }

    public function file(string $path, string $mode = 'rb'): ?Stream
    {
        $parts = $this->getParts($path);
        if (!isset($parts['Key']) || $parts['Key'][-1] === '/') {
            return null;
        }

        /** @var Stat|null $stat */
        $stat = null;
        /** @var ResultInterface|null $object */
        $object = null;

        $data = $this->getPartsInfo($path, true);

        if ($data !== null) {
            if ($data['dir']) {
                return null;
            }
            $stat = new Stat\FileStat($this->defaultMode, $data['size'], $data['time']);
            $object = $data['result'];
        }
        unset($data);

        if ($stat === null) {
            if (!array_intersect(str_split($mode), ['w', 'a', 'x', 'c', '+'])) {
                // opened only for reading, but was not found
                return null;
            }
            return $this->stream($path, $mode, self::TEMP_STREAM, $stat);
        } else {
            if (str_contains($mode, 'x')) {
                // already exists
                return null;
            }
            if (str_contains($mode, 'w')) {
                // Truncate, we don;t need body
                return $this->stream($path, $mode, self::TEMP_STREAM, $stat);
            }
        }

        $body = $object->get('Body');

        if (!isset($body)) {
            $body = '';
        }

        $stream = null;

        if (is_string($body)) {
            $type = $object->get('ContentType') ?: 'text/plain';
            $stream = $this->stream($path, $mode, 'data://' . $type . ';base64,' . base64_encode($body), $stat);
        } else {
            if ($body instanceof StreamInterface) {
                $body = $body->detach();
            }
            if (is_resource($body)) {
                $stream = $this->stream($path, $mode, $body, $stat);
            }
        }

        if (!$stream) {
            return null;
        }

        if (str_contains($mode, 'a')) {
            $stream->seek(0, SEEK_END);
        }

        return $stream;
    }

    public function dir(string $path): ?Directory
    {
        $parts = $this->getParts($path);

        try {
            $op = [
                'Bucket' => $parts['Bucket'],
                'Delimiter' => '/',
            ];
            if (isset($parts['Key'])) {
                $op['Prefix'] = rtrim($parts['Key'], '/') . '/';
            }

            $list = $this->client->getPaginator('ListObjects', $op);
        } catch (S3Exception) {
            return null;
        }

        $it = flatmap($list, function (ResultInterface $result) {
            return array_filter(
                $result->search('[Contents[], CommonPrefixes[]][]'),
                static fn($item) => (!isset($item['Key']) || substr($item['Key'], -1, 1) !== '/')
            );
        });

        $list = [];

        foreach ($it as $result) {
            if (isset($result['Key'])) {
                $item = $this->createInfo($result['Key']);
            } else {
                $item = $this->createInfo($result['Prefix']);
            }

            if ($item) {
                $list[] = $item;
            }
        }

        return new Directory\ArrayDirectory($path, $list);
    }

    public function info(string $path): ?FileInfo
    {
        return $this->createInfo($path);
    }

    /**
     * @param string $path
     * @param string $text
     * @param callable|null $filter
     * @param array|null $options
     * @param int|null $depth
     * @param int|null $limit
     * @return FileInfo[]
     */
    public function search(
        string $path,
        string $text,
        ?callable $filter = null,
        ?array $options = null,
        ?int $depth = 0,
        ?int $limit = null
    ): iterable {
        // TODO: Implement search() method.
        return [];
    }

    protected function getParts(?string $path): array
    {
        if (isset($path)) {
            $path = trim($path, '/');
            if ($path === '') {
                $path = null;
            }
        }

        return ['Key' => $path] + $this->options;
    }

    protected function determineAcl(int $mode): string
    {
        return match (decoct($mode)[0]) {
            '7' => 'public-read',
            '6' => 'authenticated-read',
            default => 'private',
        };
    }

    protected function getPartsInfo(array|string $parts, bool $body = false): ?array
    {
        if (is_string($parts)) {
            $parts = $this->getParts($parts);
        }

        $data = [
            'dir' => false,
            'size' => 0,
            'time' => null,
            'type' => null,
            'result' => null,
            'path' => isset($parts['Key']) ? '/' . $parts['Key'] : '/',
        ];

        if (!isset($parts['Key'])) {
            try {
                $result = $this->client->headBucket($parts);

                $data['dir'] = true;
                $data['time'] = isset($result['LastModified']) ? strtotime($result['LastModified']) : null;

                return $data;
            } catch (S3Exception) {
                return null;
            }
        }

        try {
            if ($body) {
                $result = $this->client->getObject($parts);
            } else {
                $result = $this->client->headObject($parts);
            }

            $data['time'] = isset($result['LastModified']) ? strtotime($result['LastModified']) : null;

            if ($parts['Key'][-1] === '/' && $result['ContentLength'] == 0) {
                $result = null;
            }
        } catch (S3Exception) {
            // Maybe prefix
            $result = $this->client->listObjects([
                'Bucket' => $parts['Bucket'],
                'Prefix' => rtrim($parts['Key'], '/') . '/',
                'MaxKeys' => 1,
            ]);

            if (!$result['Contents'] && !$result['CommonPrefixes']) {
                // Not found
                return null;
            }

            $data['time'] = isset($result['LastModified']) ? strtotime($result['LastModified']) : null;

            $result = null;
        }

        if ($result) {
            $data['type'] = $result->get('ContentType') ?: null;
            $data['size'] = (int)($result->get('ContentLength') ?: $result->get('Size') ?: 0);
            $data['result'] = $result;
        } else {
            $data['dir'] = true;
        }

        return $data;
    }

    protected function createInfo(string|array $path, ?int $mode = null): ?FileInfo
    {
        $data = $this->getPartsInfo($path);

        if ($data === null) {
            return null;
        }

        $url = null;
        $mode ??= $this->defaultMode;

        if ($data['dir']) {
            $stat = new Stat\DirectoryStat($mode, $data['time']);
        } else {
            $stat = new Stat\FileStat($mode, $data['size'], $data['time']);
            $url = $this->createUrl(is_array($path) ? $path['Key'] ?? '' : $path,
                $this->getObjectData($data['result']));
        }

        return new FileInfo($data['path'], $stat, $data['type'], $url);
    }

    protected function getObjectData(ResultInterface $object): array
    {
        return [
            'LastModified' => $object->get('LastModified'),
            'Size' => $object->get('ContentLength') ?: $object->get('Size') ?: 0,
            'ContentType' => $object->get('ContentType'),
            'ETag' => $object->get('ETag'),
            'StorageClass' => $object->get('StorageClass'),
        ];
    }

    protected function createUrl(string $path, array $info): ?string
    {
        if (!$this->url) {
            return null;
        }
        return ($this->url)($this->bucket, $path, $path, $info);
    }

    protected function stream(string $path, string $mode, $stream, ?Stat $stat): Stream
    {
        return new FileStream($stream, $mode, $stat, function (Stream $stream) use ($path) {
            return $this->write($path, $stream, $this->defaultMode);
        });
    }

    protected function doCopy(string $from, string $to, bool $move, bool $overwrite = true): ?FileInfo
    {
        // TODO: use $overwrite when not move

        $partsFrom = $this->getParts($from);
        if (!isset($partsFrom['Key'])) {
            return null;
        }

        $partsTo = $this->getParts($to);
        if (!isset($partsTo['Key'])) {
            return null;
        }

        try {
            $this->client->copy(
                $partsFrom['Bucket'], $partsFrom['Key'],
                $partsTo['Bucket'], $partsTo['Key'],
                $this->options['ACL']
            );
            if ($move) {
                $this->client->deleteObject($partsFrom);
            }
        } catch (S3Exception) {
            return null;
        }

        return $this->createInfo($partsTo);
    }

    protected function typeScanner(): MimeScanner
    {
        if ($this->typeScanner === null) {
            $this->typeScanner = new MimeScanner();
        }
        return $this->typeScanner;
    }
}