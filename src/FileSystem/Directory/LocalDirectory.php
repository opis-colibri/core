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

use Opis\Colibri\FileSystem\{Directory, FileInfo};
use Opis\Colibri\FileSystem\Handler\FileSystemHandler;
use Opis\Colibri\FileSystem\Traits\DirectoryFullPathTrait;

class LocalDirectory implements Directory
{
    use DirectoryFullPathTrait;

    /** @var resource|null|bool */
    protected $dir = false;

    protected FileSystemHandler $fs;
    protected string $path;
    protected string $root;

    public function __construct(FileSystemHandler $handler, string $path, string $root = '')
    {
        $this->fs = $handler;
        $this->path = $path;
        $this->root = $root;
    }

    public function path(): string
    {
        return '/' . trim($this->path, '/');
    }

    public function doNext(): ?FileInfo
    {
        if ($this->dir === false) {
            $this->dir = @opendir($this->root . $this->path);

            if (!$this->dir) {
                $this->dir = null;
                return null;
            }
        }

        do {
            $next = @readdir($this->dir);
            if ($next === false) {
                return null;
            }
            if ($next !== '.' && $next !== '..') {
                break;
            }
        } while (true);

        if ($this->path !== '' && $this->path !== '/') {
            $next = rtrim($this->path, '/') . '/' . $next;
        }

        return $this->fs->info($next);
    }

    public function rewind(): bool
    {
        if ($this->dir) {
            @rewinddir($this->dir);
            return true;
        }

        return false;
    }

    public function close(): void
    {
        if ($this->dir) {
            @closedir($this->dir);
            $this->dir = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }
}