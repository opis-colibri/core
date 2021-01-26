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

trait PathTrait
{
    protected function parsePath(string $path, ?string $protocol = null, bool $normalize = true): ?array
    {
        if (!preg_match('~^([^:]+)://([^/]+)(?:/(.*))?$~', $path, $m)) {
            return null;
        }

        if (isset($m[3])) {
            if ($normalize && ($m[3] = $this->normalizePath($m[3])) === null) {
                return null;
            }
        } else {
            $m[3] = '';
        }

        if ($protocol !== null && $m[1] !== $protocol) {
            return null;
        }

        return ['proto' => $m[1], 'handler' => $m[2], 'path' => $m[3]];
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path, ' /');

        if ($path === '') {
            return '';
        }

        $normalized = [];

        foreach (explode('/', $path) as $name) {
            $name = trim($name);

            if ($name === '' || $name === '.') {
                continue;
            }

            if ($name === '..') {
                if ($normalized) {
                    array_pop($normalized);
                }
                continue;
            }

            $normalized[] = $name;
        }

        return $normalized ? implode('/', $normalized) : '';
    }
}