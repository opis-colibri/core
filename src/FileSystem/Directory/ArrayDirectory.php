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

namespace Opis\Colibri\FileSystem\Directory;

use Opis\Colibri\FileSystem\Traits\DirectoryFullPathTrait;
use Opis\Colibri\FileSystem\{Directory, ProtocolInfo, FileInfo};

final class ArrayDirectory implements Directory, ProtocolInfo
{
    use DirectoryFullPathTrait;

    private string $path;
    /** @var FileInfo[]|null */
    private ?array $items = null;

    /**
     * @param string $path
     * @param FileInfo[] $items
     */
    public function __construct(string $path, array $items)
    {
        $this->path = trim($path, ' /');
        $this->items = $items;
        reset($this->items);
    }

    public function path(): string
    {
        return $this->path;
    }

    public function doNext(): ?FileInfo
    {
        if ($this->items === null) {
            return null;
        }

        $next = current($this->items);
        next($this->items);

        return $next instanceof FileInfo ? $next : null;
    }

    public function rewind(): bool
    {
        if ($this->items === null) {
            return false;
        }

        reset($this->items);

        return true;
    }

    public function close(): void
    {
        $this->items = null;
    }
}