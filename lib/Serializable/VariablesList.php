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

class VariablesList implements Serializable
{
    public $variables = array();

    public function add($name, $value)
    {
        $this->variables[$name] = $value;
    }

    public function get($key, $default = null)
    {
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        }

        return $default;
    }

    protected function mapFunction1($value)
    {
        if (is_array($value)) {
            return array_map(array($this, __FUNCTION__), $value);
        } elseif ($value instanceof \stdClass) {
            $ret = (array) $value;
            $ret = array_map(array($this, __FUNCTION__), $ret);
            $ret = (object) $ret;
            return $ret;
        } elseif ($value instanceof Closure) {
            return SerializableClosure::from($value);
        }

        return $value;
    }

    protected function mapFunction2($value)
    {
        if (is_array($value)) {
            return array_map(array($this, __FUNCTION__), $value);
        } elseif ($value instanceof \stdClass) {
            $ret = (array) $value;
            $ret = array_map(array($this, __FUNCTION__), $ret);
            $ret = (object) $ret;
            return $ret;
        } elseif ($value instanceof SerializableClosure) {
            return $value->getClosure();
        }

        return $value;
    }

    public function serialize()
    {
        SerializableClosure::enterContext();
        $object = $this->mapFunction1($this->variables);
        SerializableClosure::exitContext();
        return serialize($object);
    }

    public function unserialize($data)
    {
        $object = SerializableClosure::unserializeData($data);
        $this->variables = $this->mapFunction2($object);
    }
}
