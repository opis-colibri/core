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

use Opis\Colibri\FileSystem\Handler\FileSystemHandler;

final class FileSystemStreamPathInfo
{
    private string $path;
    private FileSystemHandler $handler;

    public function __construct(FileSystemHandler $handler, string $path)
    {
        $this->handler = $handler;
        $this->path = $path;
    }

    public function handler(): FileSystemHandler
    {
        return $this->handler;
    }

    public function path(): string
    {
        return $this->path;
    }
}