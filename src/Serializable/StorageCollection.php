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

use Serializable, Closure, RuntimeException;
use Opis\Closure\SerializableClosure;

class StorageCollection implements Serializable
{
    /** @var callable[] */
    protected $storage = [];

    /** @var array */
    protected $instances = [];

    /** @var callable|null */
    protected $builder;

    /** @var bool */
    protected $throw;

    /**
     * StorageCollection constructor.
     * @param callable|null $builder
     * @param bool $throw
     */
    public function __construct(?callable $builder, bool $throw = true)
    {
        $this->builder = $builder;
        $this->throw = $throw;
    }

    /**
     * @param string $storage
     * @param callable $factory
     * @return StorageCollection
     */
    public function add(string $storage, callable $factory): self
    {
        $this->storage[$storage] = $factory;
        unset($this->instances[$storage]);

        return $this;
    }

    /**
     * @param string $storage
     * @return bool
     */
    public function has(string $storage): bool
    {
        return isset($this->storage[$storage]);
    }

    /**
     * @param string $storage
     * @return StorageCollection
     */
    public function remove(string $storage): self
    {
        unset($this->storage[$storage], $this->instances[$storage]);

        return $this;
    }


    /**
     * @param string $storage
     * @return mixed|null
     */
    public function get(string $storage)
    {
        if (!isset($this->storage[$storage])) {
            if ($this->throw) {
                throw new RuntimeException("Unknown storage '$storage'");
            }
            return null;
        }

        if (!array_key_exists($storage, $this->instances)) {
            $constructor = $this->storage[$storage];
            if ($builder = $this->builder) {
                $this->instances[$storage] = $builder($storage, $constructor);
            } else {
                $this->instances[$storage] = $constructor($storage);
            }
        }

        return $this->instances[$storage];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $map = function ($value) {
            if ($value instanceof Closure) {
                return SerializableClosure::from($value);
            }
            return $value;
        };

        $object = serialize([
            'builder' => $map($this->builder),
            'storage' => array_map($map, $this->storage),
            'throw' => $this->throw,
        ]);

        SerializableClosure::exitContext();

        return $object;
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $data = unserialize($data);

        $map = function ($value) {
            if ($value instanceof SerializableClosure) {
                return $value->getClosure();
            }
            return $value;
        };

        $this->builder = $map($data['builder']);
        $this->storage = array_map($map, $data['storage']);
        $this->throw = $data['throw'] ?? true;
    }
}
