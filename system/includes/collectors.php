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

namespace Opis\Colibri;

use Closure;

interface RouteCollectorInterface
{
    public function bind($name, Closure $callback);
    
    public function filter($name, Closure $callback);
    
    public function implicit($name, $value);
    
    public function wildcard($name, $value);
    
    public function all($path, Closure $action, $name = null);
    
    public function get($path, Closure $action, $name = null);
    
    public function post($path, Closure $action);
    
    public function delete($path, Closure $action);
    
    public function put($path, Closure $action);
    
    public function patch($path, Closure $action);
}

interface RouteAliasCollectorInterface
{
    public function alias($path, Closure $action);
}

interface DispatcherCollectorInterface
{
    public function register($name, Closure $builder);
}

interface ViewCollectorInterface
{
    public function handle($pattern, Closure $resolver, $priority = 0);
}

interface ViewEngineCollectorInterface
{
    public function register(Closure $constructor, $priority = 0);
}

interface ContractCollectorInterface
{
    public function bind($abstract, $concrete = null);
    
    public function alias($concrete, $alias);
    
    public function extend($abstract, Closure $extender);
    
    public function singleton($abstract, $concrete = null);
}

interface EventCollectorInterface
{
    public function handle($event, Closure $callback, $priority = 0);
}

interface ConnectionCollectorInterface
{
    public function create($name, $default = false);
}

interface StorageCollectorInterface
{
    public function register($storage, Closure $constructor, $default = false);
}

interface CacheCollectorInterface extends StorageCollectorInterface
{
    
}

interface SessionCollectorInterface extends StorageCollectorInterface
{
    
}

interface ConfigCollectorInterface extends StorageCollectorInterface
{
    
}
