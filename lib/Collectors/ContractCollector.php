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

namespace Opis\Colibri\Collectors;

use Closure;
use Opis\Colibri\Collector;
use Opis\Colibri\Container;

/**
 * Class ContractCollector
 *
 * @package Opis\Colibri\Collectors
 *
 * @method Container    data()
 * @property Container $dataObject
 */
class ContractCollector extends Collector
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
     * @param   \Closure|string $concrete (optional) Concrete class or interface implementation
     *
     * @return  \Opis\Container\Dependency
     */
    public function bind($abstract, $concrete = null)
    {
        return $this->dataObject->bind($abstract, $concrete);
    }

    /**
     * Alias a type.
     *
     * @param   string $concrete Concrete class or interface name
     * @param   string $alias An alias for the specified class or interface
     *
     * @return  self    Self reference
     */
    public function alias($concrete, $alias)
    {
        $this->dataObject->alias($concrete, $alias);
        return $this;
    }

    /**
     * Extends a registered type.
     *
     * @param   string $abstract
     * @param Closure $extender
     * @return \Opis\Container\Extender
     */
    public function extend($abstract, Closure $extender)
    {
        return $this->dataObject->extend($abstract, $extender);
    }

    /**
     * Register a singleton binding with the container.
     *
     * @param   string $abstract Class name or interface name
     * @param   Closure|string $concrete (optional) Concrete class or interface implementation
     *
     * @return  \Opis\Container\Dependency
     */
    public function singleton($abstract, $concrete = null)
    {
        return $this->dataObject->singleton($abstract, $concrete);
    }
}
