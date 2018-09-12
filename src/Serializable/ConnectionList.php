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

use Serializable, RuntimeException;
use Opis\Database\Connection;

class ConnectionList implements Serializable
{
    /** @var Connection[] */
    protected $list = [];

    /**
     * @param string $name
     * @param Connection $connection
     * @return ConnectionList
     */
    public function set(string $name, Connection $connection): self
    {
        $this->list[$name] = $connection;
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
     * @return ConnectionList
     */
    public function remove(string $name): self
    {
        unset($this->list[$name]);
        return $this;
    }

    /**
     * @param string $name
     * @return Connection
     */
    public function get(string $name): Connection
    {
        if (!isset($this->list[$name])) {
            throw new RuntimeException("Invalid connection name $name");
        }

        return $this->list[$name];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize($this->list);
    }

    /**
     * @param string $data
     */
    public function unserialize($data)
    {
        $this->list = unserialize($data);
    }
}
