<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

use Closure;
use Opis\Closure\SerializableClosure;
use Opis\Colibri\Application;
use RuntimeException;
use Serializable;

class StorageCollection implements Serializable
{
    protected $storages = array();
    protected $instances = array();
    protected $builder;
    protected $defaultStorage;

    public function __construct(Closure $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string  $storage
     * @param Closure $constructor
     * @return $this
     */
    public function add($storage, Closure $constructor)
    {
        $this->storages[$storage] = $constructor;
        unset($this->instances[$storage]);

        return $this;
    }


    /**
     * @param Application $app
     * @param string $storage
     * @return mixed
     */
    public function get(Application $app, $storage)
    {
        if (!isset($this->storages[$storage])) {
            throw new RuntimeException('Unknown storage ' . $storage);
        }

        if (!isset($this->instances[$storage])) {
            $constructor = $this->storages[$storage];
            $builder = $this->builder;
            $this->instances[$storage] = $builder($storage, $constructor, $app);
        }

        return $this->instances[$storage];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        SerializableClosure::enterContext();
        $object = serialize(array(
            'builder' => SerializableClosure::from($this->builder),
            'defaultStorage' => $this->defaultStorage,
            'storages' => array_map(function ($value) {
                return SerializableClosure::from($value);
            }, $this->storages),
        ));
        SerializableClosure::exitContext();
        return $object;
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);
        $this->builder = $object['builder']->getClosure();
        $this->defaultStorage = $object['defaultStorage'];
        $this->storages = array_map(function ($value) {
            return $value->getClosure();
        }, $object['storages']);
    }
}
