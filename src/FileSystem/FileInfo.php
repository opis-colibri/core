<?php
/* ============================================================================
 * Copyright 2019-2021 Zindex Software
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

namespace Opis\Colibri\FileSystem;

use Opis\Colibri\FileSystem\Traits\FullPathTrait;

class FileInfo implements ProtocolInfo
{
    use FullPathTrait;

    protected string $path;
    protected ?string $name = null;
    protected Stat $stat;
    protected ?string $mime = null;
    protected ?string $url = null;
    protected ?array $metadata = null;

    public function __construct(
        string $path,
        Stat $stat,
        ?string $mime = null,
        ?string $url = null,
        ?array $metadata = null
    )
    {
        $this->path = $path;
        $this->stat = $stat;
        $this->mime = $mime;
        $this->url = $url;
        $this->metadata = $metadata;
    }

    /**
     * @return Stat
     */
    public function stat(): Stat
    {
        return $this->stat;
    }

    /**
     * File/Dir name
     * @return string
     */
    public function name(): string
    {
        if ($this->name === null) {
            $name = explode('/', $this->path);
            $this->name = array_pop($name);
        }

        return $this->name;
    }

    /**
     * Path
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * Content type
     * @return string|null
     */
    public function mime(): ?string
    {
        return $this->mime;
    }

    /**
     * Public URL
     * @return string|null
     */
    public function url(): ?string
    {
        return $this->url;
    }

    /**
     * Metadata
     * @return array|null
     */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function __serialize(): array
    {
        $data = [
            'path' => $this->path,
            'stat' => $this->stat,
        ];

        if ($this->mime !== null) {
            $data['mime'] = $this->mime;
        }

        if ($this->url !== null) {
            $data['url'] = $this->url;
        }

        if ($this->metadata) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->path = $data['path'];
        $this->stat = $data['stat'];
        $this->mime = $data['mime'] ?? null;
        $this->url = $data['url'] ?? null;
        $this->metadata = $data['metadata'] ?? null;
    }

    public function jsonSerialize()
    {
        return [
            'path' => $this->path,
            'name' => $this->name(),
            'mime' => $this->mime,
            'url' => $this->url,
            'metadata' => $this->metadata ?: null,
            'stat' => $this->stat,
        ];
    }
}