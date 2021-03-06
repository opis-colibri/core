<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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

class Collection
{
    private array $entries = [];

    public function add(string $key, mixed $value): void
    {
        $this->entries[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    public function remove(string $key): void
    {
        unset($this->entries[$key]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->entries[$key];
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return array_keys($this->entries);
    }

    public function getValues(): array
    {
        return array_values($this->entries);
    }

    public function isEmpty(): bool
    {
        return empty($this->entries);
    }

    public function length(): int
    {
        return count($this->entries);
    }

    public function __serialize(): array
    {
        return [
            'entries' => $this->entries
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->entries = $data['entries'];
    }
}