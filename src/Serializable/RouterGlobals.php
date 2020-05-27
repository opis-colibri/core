<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

class RouterGlobals
{
    private $globals = [
        'mixin' => [],
        'implicit' => [],
        'bind' => [],
        'filter' => [],
        'guard' => [],
        'placeholder' => [],
    ];

    /**
     * @param string $name
     * @param callable $callback
     */
    public function mixin(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function bind(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function filter(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function guard(string $name, callable $callback)
    {
        $this->globals[__FUNCTION__][$name] = $callback;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function implicit(string $name, $value)
    {
        $this->globals[__FUNCTION__][$name] = $value;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function placeholder(string $name, string $value)
    {
        $this->globals[__FUNCTION__][$name] = $value;
    }

    /**
     * @return array
     */
    public function getGlobals(): array
    {
        return $this->globals;
    }

    public function __serialize(): array
    {
        return $this->globals;
    }

    public function __unserialize(array $data): void
    {
        $this->globals = $data;
    }
}