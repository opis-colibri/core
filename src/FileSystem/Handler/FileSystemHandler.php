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

namespace Opis\Colibri\FileSystem\Handler;

use Opis\Colibri\Stream\Stream;
use Opis\Colibri\FileSystem\{FileInfo, Directory, Stat};

interface FileSystemHandler
{
    public function mkdir(string $path, int $mode = 0777, bool $recursive = true): ?FileInfo;
    public function rmdir(string $path, bool $recursive = true): bool;
    public function unlink(string $path): bool;
    public function rename(string $from, string $to): ?FileInfo;
    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo;
    public function stat(string $path, bool $resolve_links = true): ?Stat;
    public function write(string $path, Stream $stream, int $mode = 0777): ?FileInfo;
    public function file(string $path, string $mode = 'rb'): ?Stream;
    public function dir(string $path): ?Directory;
    public function info(string $path): ?FileInfo;
}