<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

use Closure, Serializable;
use Opis\Closure\SerializableClosure;

class CallbackList implements Serializable
{
    /** @var callable[] */
    protected $list = [];

    /**
     * @param string $name
     * @param callable $callback
     * @return CallbackList
     */
    public function add(string $name, callable $callback): self
    {
        $this->list[$name] = $callback;
        return $this;
    }

    /**
     * @param string $name
     * @return CallbackList
     */
    public function remove(string $name): self
    {
        unset($this->list[$name]);
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->list[$name]);
    }

    /**
     * @param string $name
     * @return callable|null
     */
    public function get(string $name): ?callable
    {
        return $this->list[$name] ?? null;
    }

    /**
     * @return callable[]
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
     * @inheritdoc
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $object = serialize(array_map(function ($value) {
            if ($value instanceof Closure) {
                return SerializableClosure::from($value);
            }
            return $value;
        }, $this->list));

        SerializableClosure::exitContext();

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $this->list = array_map(function ($value) {
            if ($value instanceof SerializableClosure) {
                return $value->getClosure();
            }
            return $value;
        }, $data);
    }
}
