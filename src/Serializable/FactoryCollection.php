<?php
/* ===========================================================================
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

use RuntimeException;
use InvalidArgumentException;

class FactoryCollection extends Collection
{
    private bool $exception;
    private array $cache = [];

    public function __construct(bool $exception = true)
    {
        $this->exception = $exception;
    }

    /**
     * @param string $key
     * @param callable $value
     */
    public function add(string $key, mixed $value): void
    {
        if (!is_callable($value)) {
            throw new InvalidArgumentException("Callable expected");
        }

        parent::add($key, $value);
    }

    public function getInstance(string $key): ?object
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if (null === $item = $this->get($key)) {
            if ($this->exception) {
                throw new RuntimeException("Invalid key ${key}");
            }
            return null;
        }

        return $this->cache[$key] = $item();
    }

    public function __serialize(): array
    {
        return [
            'exception' => $this->exception,
            'parent' => parent::__serialize(),
        ];
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data['parent']);
        $this->exception = $data['exception'];
    }
}