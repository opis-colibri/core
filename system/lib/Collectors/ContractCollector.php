<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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
use Opis\Container\Container;
use Opis\Colibri\ContractCollectorInterface;

class ContractCollector extends AbstractCollector implements ContractCollectorInterface
{
    
    public function __construct()
    {
        parent::__construct(new Container());
    }
    
    public function bind($abstract, $concrete = null)
    {
        return $this->dataObject->bind($abstract, $concrete);
    }
    
    public function alias($concrete, $alias)
    {
       $this->dataObject->alias($concrete, $alias);
       return $this;
    }
    
    public function extend($abstract, Closure $extender)
    {
        return $this->dataObject->extend($abstract, $extender);
    }
    
    public function singleton($abstract, $concrete = null)
    {
        return $this->dataObject->singleton($abstract, $concrete);
    }
    
}
