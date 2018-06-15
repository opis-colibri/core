<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\{
    ItemCollector, Serializable\RoutingGlobals
};

/**
 * @property RoutingGlobals $data
 */
class GlobalsCollector extends ItemCollector
{
    /**
     * GlobalsCollector constructor.
     */
    public function __construct()
    {
        parent::__construct(new RoutingGlobals());
    }

    /**
     * Set a global mixin
     *
     * @param string $name
     * @param callable $callback
     * @return $this
     */
    public function mixin(string $name, callable $callback): self
    {
        $this->data->mixin($name, $callback);
        return $this;
    }

    /**
     * Defines a global binding
     *
     * @param   string $name The name of the binding
     * @param   callable $callback A callback that will return the binding's value
     *
     * @return  $this
     */
    public function bind(string $name, callable $callback): self
    {
        $this->data->bind($name, $callback);
        return $this;
    }

    /**
     * Defines a global callback
     *
     * @param   string $name The name of the callback
     * @param   callable $callback A callback
     *
     * @return  $this
     */
    public function callback(string $name, callable $callback): self
    {
        $this->data->callback($name, $callback);
        return $this;
    }

    /**
     * Set a global implicit value for a wildcard
     *
     * @param   string $name The name of the wildcard
     * @param   mixed $value The implicit value
     *
     * @return  $this
     */
    public function implicit(string $name, $value): self
    {
        $this->data->implicit($name, $value);
        return $this;
    }

    /**
     * Set a global placeholder
     *
     * @param   string $name The name of the wildcard
     * @param   string $value A regex expression
     *
     * @return  $this
     */
    public function placeholder(string $name, string $value): self
    {
        $this->data->placeholder($name, $value);
        return $this;
    }
}