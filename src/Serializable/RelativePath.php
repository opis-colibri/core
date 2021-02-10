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

namespace Opis\Colibri\Serializable;

use Stringable;
use function Opis\Colibri\info;

final class RelativePath implements Stringable
{
    private string $path;
    private ?string $cache = null;

    public function __construct(string $module, string $path = '')
    {
        $this->path = $module . '/' . trim($path, '/');
    }

    public function __serialize(): array
    {
        return ['path' => $this->path];
    }

    public function __unserialize(array $data): void
    {
        $this->path = $data['path'];
    }

    public function __toString(): string
    {
        if ($this->cache === null) {
            $this->cache = info()->vendorDir() . '/' . $this->path;
        }
        return $this->cache;
    }
}