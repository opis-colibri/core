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

namespace Opis\Colibri\FileSystem\Traits;

use Opis\Colibri\FileSystem\Directory;

trait StreamDirectoryTrait
{
    protected ?Directory $dir = null;

    public function dir_closedir(): bool
    {
        if ($this->dir === null) {
            return false;
        }

        $this->dir->close();
        $this->dir = null;

        return true;
    }

    public function dir_opendir(
        string $path,
        /** @noinspection PhpUnusedParameterInspection */
        int $options
    ): bool
    {
        $this->dir = $this->dir($path);

        return $this->dir !== null;
    }

    public function dir_readdir(): ?string
    {
        if ($this->dir === null) {
            return null;
        }

        $next = $this->dir->next();

        if ($next === null) {
            return null;
        }

        return $next->name();
    }

    public function dir_rewinddir(): bool
    {
        if ($this->dir === null) {
            return false;
        }

        return $this->dir->rewind();
    }

    abstract protected function dir(string $path): ?Directory;
}