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
use Serializable;
use Opis\Closure\SerializableClosure;

class CallbackList implements Serializable
{
    protected $callbacks = array();

    public function add($name, $callback)
    {
        $this->callbacks[$name] = $callback;
    }

    public function getList()
    {
        return $this->callbacks;
    }

    public function serialize()
    {
        SerializableClosure::enterContext();

        $object = serialize(array_map(function($value) {
                if ($value instanceof Closure) {
                    return SerializableClosure::from($value);
                }
                return $value;
            }, $this->callbacks));

        SerializableClosure::exitContext();

        return $object;
    }

    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);

        $this->callbacks = array_map(function($value) {
            if ($value instanceof SerializableClosure) {
                return $value->getClosure();
            }
            return $value;
        }, $object);
    }
}
