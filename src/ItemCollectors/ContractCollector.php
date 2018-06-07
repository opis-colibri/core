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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\ItemCollector;
use Opis\Colibri\Container;
use Opis\Container\Dependency;

/**
 * @property Container $data
 */
class ContractCollector extends ItemCollector
{
    /**
     * ContractCollector constructor
     */
    public function __construct()
    {
        parent::__construct(new Container());
    }

    /**
     * Register a binding with the container.
     *
     * @param   string $abstract Class name or interface name
     * @param   string|callable|null $concrete
     *
     * @return  Dependency
     */
    public function bind(string $abstract, $concrete = null): Dependency
    {
        return $this->data->bind($abstract, $concrete);
    }

    /**
     * Alias a type.
     *
     * @param   string $alias An alias for the specified class or interface
     * @param   string $concrete Concrete class or interface name
     *
     * @return  self    Self reference
     */
    public function alias(string $alias, string $concrete): self
    {
        $this->data->alias($alias, $concrete);
        return $this;
    }

    /**
     * Extends a registered type.
     *
     * @param   string $abstract
     * @param callable $extender
     * @return self
     */
    public function extend(string $abstract, callable $extender): self
    {
        $this->data->extend($abstract, $extender);
        return $this;
    }

    /**
     * Register a singleton binding with the container.
     *
     * @param   string $abstract Class name or interface name
     * @param   string|callable|null $concrete
     *
     * @return  Dependency
     */
    public function singleton(string $abstract, $concrete = null): Dependency
    {
        return $this->data->singleton($abstract, $concrete);
    }
}
