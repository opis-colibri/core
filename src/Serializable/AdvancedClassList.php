<?php
/* ============================================================================
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

use Closure;
use Opis\Closure\SerializableClosure;

class AdvancedClassList extends ClassList
{
    /**
     * @param string $type
     * @param callable $builder
     * @return AdvancedClassList
     */
    public function addCallable(string $type, callable $builder): self
    {
        $this->list[$type] = $builder;
        return $this;
    }

    /**
     * @param string $type
     * @return null|mixed
     */
    public function get(string $type)
    {
        if (!isset($this->list[$type])) {
            return null;
        }

        $callable = $this->list[$type];

        if (!is_callable($callable)) {
            return parent::get($type);
        }

        if (!$this->singleton) {
            return $callable($type);
        }

        if (!array_key_exists($type, $this->cache)) {
            $this->cache[$type] = $callable($type);
        }

        return $this->cache[$type];
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