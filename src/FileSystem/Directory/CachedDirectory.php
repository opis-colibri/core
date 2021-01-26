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

use Opis\Colibri\FileSystem\Handler\CachedHandler;
use Opis\Colibri\FileSystem\Traits\DirectoryFullPathTrait;
use Opis\Colibri\FileSystem\{Directory, FileInfo, ProtocolInfo};

final class CachedDirectory implements Directory
{
    use DirectoryFullPathTrait;

    private string $path;
    private ?Directory $directory;
    private ?CachedHandler $handler;

    public function __construct(Directory $directory, CachedHandler $handler)
    {
        $this->directory = $directory;
        $this->handler = $handler;
        $this->path = $directory->path();

        if ($directory instanceof ProtocolInfo) {
            $this->protocol = $directory->protocol();
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    public function doNext(): ?FileInfo
    {
        if ($this->directory === null) {
            return null;
        }

        if ($info = $this->directory->next()) {
            $this->handler->updateCache($info);
        }

        return $info;
    }

    public function rewind(): bool
    {
        if ($this->directory === null) {
            return false;
        }

        return $this->directory->rewind();
    }

    public function close(): void
    {
        if ($this->directory !== null) {
            $this->directory->close();
            $this->directory = null;
        }
    }

    public function __destruct()
    {
        $this->close();
        $this->handler = null;
    }
}