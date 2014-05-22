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
use Opis\HttpRouting\Route;
use Opis\HttpRouting\RouteCollection;
use Opis\Colibri\RouteCollectorInterface;

class RouteCollector extends AbstractCollector implements RouteCollectorInterface
{
    
    public function __construct()
    {
        parent::__construct(new RouteCollection());
    }
    
    public function bind($name, Closure $callback)
    {
        $this->dataObject->bind($name, $callback);
        return $this;
    }
    
    public function filter($name, Closure $callback)
    {
        $this->dataObject->filter($name, $callback);
        return $this;
    }
    
    public function implicit($name, $value)
    {
        $this->dataObject->implicit($name, $value);
        return $this;
    }
    
    public function wildcard($name, $value)
    {
        $this->dataObject->wildcard($name, $value);
        return $this;
    }
    
    protected function handle($path, Closure $action, $name = null)
    {
        $route = new Route($path, $action);
        $this->dataObject[$name] = $route;
        return $route;
    }
    
    public function all($path, Closure $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method(array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'));
    }
    
    public function get($path, Closure $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('GET');
    }
    
    public function post($path, Closure $action)
    {
        return $this->handle($path, $action, null)->method('POST');
    }
    
    public function delete($path, Closure $action)
    {
        return $this->handle($path, $action, null)->method('DELETE');
    }
    
    public function put($path, Closure $action)
    {
        return $this->handle($path, $action, null)->method('PUT');
    }
    
    public function patch($path, Closure $action)
    {
        return $this->handle($path, $action, null)->method('PATCH');
    }
}
