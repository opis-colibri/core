<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\Stream;

use InvalidArgumentException;

class ResourceStream implements Stream
{

    /** @var null|resource */
    protected $resource = null;
    protected ?string $str = null;
    protected bool $detached = false;

    /**
     * ResourceStream constructor.
     * @param string|resource|Stream $stream
     * @param string $mode
     */
    public function __construct($stream, string $mode = 'rb')
    {
        if (is_string($stream)) {
            $resource = @fopen($stream, $mode);
            if ($resource === false) {
                throw new InvalidArgumentException("Invalid stream {$stream}");
            }
        } elseif ($stream instanceof Stream) {
            $resource = $stream->resource();
        } else {
            $resource = $stream;
        }

        unset($stream);

        if (!is_resource($resource)) {
            throw new InvalidArgumentException("Stream must be a resource or a string");
        }

        if (get_resource_type($resource) !== 'stream') {
            throw new InvalidArgumentException("Resource must be a stream");
        }

        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function isClosed(): bool
    {
        return $this->resource === null;
    }

    /**
     * @inheritDoc
     */
    public function size(): ?int
    {
        if (!$this->resource) {
            return null;
        }
        return fstat($this->resource)['size'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function tell(): ?int
    {
        if (!$this->resource) {
            return null;
        }

        $pos = ftell($this->resource);

        if ($pos === false) {
            return null;
        }

        return $pos;
    }

    /**
     * @inheritDoc
     */
    public function isEOF(): bool
    {
        return !$this->resource || feof($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return $this->resource ? (bool)$this->metadata('seekable') : false;
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (!$this->resource) {
            return false;
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): bool
    {
        return $this->seek(0);
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        $mode = $this->metadata('mode');

        if (!$mode) {
            return false;
        }

        $flags = ['w', 'a', 'x', 'c'];
        if (!isset($mode[1])) {
            return in_array($mode, $flags);
        }

        array_unshift($flags, '+');

        foreach ($flags as $f) {
            if (str_contains($mode, $f)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): ?int
    {
        if (!$this->resource) {
            return null;
        }

        $len = fwrite($this->resource, $string);

        if ($len === false) {
            return null;
        }

        return $len;
    }

    /**
     * @inheritDoc
     */
    public function truncate(int $size): bool
    {
        if (!$this->resource) {
            return false;
        }

        return ftruncate($this->resource, $size);
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
    {
        if (!$this->resource) {
            return false;
        }

        return fflush($this->resource);
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        $mode = $this->metadata('mode');

        if (!$mode) {
            return false;
        }

        if (str_contains($mode, 'r')) {
            return true;
        }

        if (str_contains($mode, '+')) {
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function read(int $length = 8192): ?string
    {
        if (!$this->resource || feof($this->resource)) {
            return null;
        }

        $result = fread($this->resource, $length);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function readLine(?int $maxLength = null): ?string
    {
        if (!$this->resource) {
            return null;
        }

        $result = $maxLength ? fgets($this->resource, $maxLength) : fgets($this->resource);

        if ($result === false) {
            return null;
        }

        return rtrim($result, "\r\n");
    }

    /**
     * @inheritDoc
     */
    public function readToEnd(): ?string
    {
        if (!$this->resource) {
            return null;
        }

        $result = stream_get_contents($this->resource);

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function metadata(?string $key = null): mixed
    {
        if (!$this->resource) {
            return null;
        }

        $meta = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function resource(bool $detach = false)
    {
        if ($detach) {
            $this->detached = true;
        }
        return $this->resource;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if ($this->resource) {
            $res = $this->resource;
            $this->resource = null;
            fclose($res);
        }
        $this->str = '';
    }

    /**
     * @inheritDoc
     */
    public function stat(): ?array
    {
        if (!$this->resource) {
            return null;
        }

        return @fstat($this->resource) ?: null;
    }

    /**
     * @inheritDoc
     */
    public function lock(int $operation): bool
    {
        if (!$this->resource) {
            return false;
        }

        return flock($this->resource, $operation);
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        if ($this->str !== null) {
            return $this->str;
        }

        if (!$this->resource) {
            return '';
        }

        $current = ftell($this->resource);
        $seek = fseek($this->resource, 0) === 0;
        $contents = stream_get_contents($this->resource);
        if ($seek && $current !== false) {
            fseek($this->resource, $current);
        }

        $this->str = $contents === false ? '' : $contents;

        return $this->str;
    }

    public function __destruct()
    {
        if (!$this->detached) {
            $this->close();
        }
    }
}