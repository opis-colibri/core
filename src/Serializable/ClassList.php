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

use Serializable;
use function Opis\Colibri\Functions\make;

class ClassList implements Serializable
{

    /** @var array */
    protected $list = [];

    /** @var array */
    protected $cache = null;

    /** @var bool */
    protected $singleton = false;

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

    /**
     * @param string $key
     * @param string $value
     */
    public function add(string $key, string $value)
    {
        $this->list[$key] = $value;
    }

    /**
     * @param string $key
     */
    public function remove(string $key)
    {
        unset($this->list[$key]);
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->list;
    }

    /**
     * List types
     * @return string[]
     */
    public function getTypes()
    {
        return array_keys($this->list);
    }

    /**
     * @param string $type
     * @param array $args
     * @return bool|mixed
     */
    public function get(string $type, array $args = [])
    {
        if (!isset($this->list[$type])) {
            return false;
        }
        if (!$this->singleton) {
            return make($this->list[$type], $args);
        }
        if (!isset($this->cache[$type])) {
            $this->cache[$type] = isset($this->list[$type]) ? make($this->list[$type]) : false;
        }
        return $this->cache[$type];
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return serialize($this->list);
    }

    /**
     * @inheritdoc
     */
    public function unserialize($data)
    {
        $this->list = unserialize($data);
    }
}
