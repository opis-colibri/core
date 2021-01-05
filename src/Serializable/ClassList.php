<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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

use function Opis\Colibri\make;

class ClassList
{
    protected array $list = [];
    protected ?array $cache = null;
    protected bool $singleton = false;

    /**
     * ClassList constructor.
     * @param bool $singleton
     */
    public function __construct(bool $singleton = false)
    {
        $this->singleton = $singleton;
        if ($singleton) {
            $this->cache = [];
        }
    }

    public function add(string $type, string $class): static
    {
        $this->list[$type] = $class;
        return $this;
    }

    public function remove(string $type): static
    {
        unset($this->list[$type]);
        return $this;
    }

    public function has(string $type): bool
    {
        return isset($this->list[$type]);
    }

    /**
     * @return string[]
     */
    public function getList(): array
    {
        return $this->list;
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_keys($this->list);
    }

    /**
     * @param string $type
     * @return null|object
     */
    public function get(string $type): ?object
    {
        if (!isset($this->list[$type])) {
            return null;
        }

        if (!$this->singleton) {
            return make($this->list[$type]);
        }

        if (!$this->cache || !array_key_exists($type, $this->cache)) {
            $this->cache[$type] = make($this->list[$type]);
        }

        return $this->cache[$type];
    }

    public function __serialize(): array
    {
        return [
            'list' => $this->list,
            'singleton' => $this->singleton,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->list = $data['list'];
        $this->singleton = $data['singleton'];
    }
}
