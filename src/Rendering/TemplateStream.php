<?php
/* ============================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Rendering;

use Opis\Colibri\Traits\StreamTrait;
use function Opis\Colibri\Functions\collect;

final class TemplateStream
{
    use StreamTrait {
        url_stat as private u_stat;
        stream_stat as private s_stat;
        stream_open as private s_open;
    }

    const PROTOCOL = 'template';

    const REGEX = '`^' . self::PROTOCOL . '://(?<type>[^/]+)/(?<id>.*)\.(?<extension>.*)$`';

    /** @var ITemplateData|null */
    private $data = false;

    /** @var null|string */
    private $type = null;

    /** @var null|string */
    private $id = null;

    /** @var null|string */
    private $extension = null;

    /** @var bool */
    private $initialized = false;

    /** @var ITemplateData[] */
    private static $cache = [];

    /**
     * @inheritDoc
     */
    function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->initPath($path);
        return $this->s_open($path, $mode, $options, $opened_path);
    }

    /**
     * @inheritDoc
     */
    public function stream_stat()
    {
        return $this->setStatTimestamps($this->s_stat(), $this->viewData());
    }

    /**
     * @inheritDoc
     */
    public function url_stat($path, $flags)
    {
        $this->initPath($path);
        return $this->setStatTimestamps($this->u_stat($path, $flags), $this->viewData());
    }

    /**
     * @inheritDoc
     */
    public function getContent(string $path): string
    {
        if (!$this->initialized) {
            $this->initPath($path);
        }

        if ($this->type === null || $this->id === null || $this->extension === null) {
            return '';
        }

        $data = $this->viewData();

        if ($data === null) {
            return '';
        }

        return $data->content() ?? '';
    }

    /**
     * @return ITemplateData|null
     */
    private function viewData(): ?ITemplateData
    {
        if ($this->data === false) {
            if ($this->type !== null && $this->id !== null && $this->extension !== null) {
                $key = $this->type . '/' . $this->id . '.' . $this->extension;
                if (array_key_exists($key, self::$cache)) {
                    $this->data = self::$cache[$key];
                } else {
                    /** @var ITemplateStreamHandler|null $provider */
                    $provider = collect('template-stream-handlers')->get($this->type);
                    if ($provider !== null) {
                        $this->data = self::$cache[$key] = $provider->handle($this->id, $this->extension);
                    } else {
                        $this->data = self::$cache[$key] = null;
                    }
                }
            } else {
                $this->data = null;
            }
        }

        return $this->data;
    }

    /**
     * @param array $stat
     * @param ITemplateData|null $data
     * @return array
     */
    private function setStatTimestamps(array $stat, ?ITemplateData $data): array
    {
        if ($data) {
            $stat[8] = $stat['atime'] =
            $stat[9] = $stat['mtime'] = $data->updatedAt();
            $stat[10] = $stat['ctime'] = $data->createdAt();
        }
        return $stat;
    }

    /**
     * @param string $path
     */
    private function initPath(string $path)
    {
        if ($this->initialized) {
            return;
        }
        if ($this->type === null && $this->id === null) {
            if (preg_match(self::REGEX, $path, $m)) {
                $this->type = $m['type'];
                $this->id = $m['id'];
                $this->extension = $m['extension'];
                $this->initialized = true;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getProtocol(): string
    {
        return self::PROTOCOL;
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $extension
     * @return string
     */
    public static function url(string $type, string $id, string $extension): string
    {
        return self::PROTOCOL . "://{$type}/{$id}.{$extension}";
    }

    /**
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
