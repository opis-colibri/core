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

/**
 * Collects routes
 */

interface RouteCollectorInterface
{
    /**
     * Defines a global binding
     *
     * @param   string      $name       The name of the binding
     * @param   \Closure    $callback   A callback that will return the binding's value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    
    public function bind($name, Closure $callback);
    
    /**
     * Defines a global filter
     *
     * @param   string      $name       The name of the filter
     * @param   \Closure    $callback   A callback that will return the filter's value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    
    public function filter($name, Closure $callback);
    
    
    /**
     * Set a global wildcard
     *
     * @param   string  $name   The name of the wildcard
     * @param   string  $value  A regex expression
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    
    public function wildcard($name, $value);
    
    /**
     * Set a global implicit value for a wildcard
     *
     * @param   string  $name   The name of the wildcard
     * @param   mixed   $value  The implicit value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    
    public function implicit($name, $value);
    
    /**
     * Defines a new route that will intercept all HTTP requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    
    public function all($path, Closure $action, $name = null);
    
    /**
     * Defines a new route that will intercept all GET requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    
    public function get($path, Closure $action, $name = null);
    
    /**
     * Defines a new route that will intercept all POST requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     *
     * @return  \Opis\HttpRouter\Route
     */
        
    public function post($path, Closure $action, $name = null);
    
    /**
     * Defines a new route that will intercept all DELETE requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    
    public function delete($path, Closure $action, $name = null);
    
    /**
     * Defines a new route that will intercept all PUT requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    
    public function put($path, Closure $action, $name = null);
    
    /**
     * Defines a new route that will intercept all PATCH requests
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    
    public function patch($path, Closure $action, $name = null);
}

/**
 * Collects routes aliases
 */

interface RouteAliasCollectorInterface
{
    /**
     * Defines an alias for a route or a group of routes
     *
     * @param   string      $path   The path to match
     * @param   \Closure    $value  An action that will be executed
     *
     * @return  \Opis\Routing\Route
     */
    
    public function alias($path, Closure $action);
}

/**
 * Collects dispatchers
 */

interface DispatcherCollectorInterface
{
    /**
     * Register a new dispatcher
     *
     * @param   string      $name       Dispatcher's name
     * @param   \Closure    $builder    A callback that will return an instance of \Opis\Routing\Contracts\DispatcherInterface
     *
     * @return  \Opis\Colibri\DispatcherCollectorInterface  Self reference
     */
    
    public function register($name, Closure $builder);
}

/**
 * Collects views
 */

interface ViewCollectorInterface
{
    /**
     * Defines a new view route
     *
     * @param   string      $pattern    View's pattern
     * @param   \Closure    $resolver   A callback that will resolve a view route into a path
     * @param   int         $priority   Route's priority
     *
     * @return  \Opis\View\Route
     */
    
    public function handle($pattern, Closure $resolver, $priority = 0);
}

/**
 * Collects view engines
 */

interface ViewEngineCollectorInterface
{
    /**
     * Defines a new view engine
     *
     * @param   \Closure    $constructor    A callback that will return an instance of \Opis\View\EngineInterface
     * @param   int         $priority       Engine's priority
     *
     * @return  \Opis\View\EngineEntry
     */
    
    public function register(Closure $constructor, $priority = 0);
}

/**
 * Collects contracts
 */

interface ContractCollectorInterface
{
    /**
     * Register a binding with the container.
     *
     * @param   string          $abstract   Class name or interface name
     * @param   \Closure|string $concrete   (optional) Concrete class or interface implementation
     *
     * @return  \Opis\Container\Dependency
     */
    
    public function bind($abstract, $concrete = null);
    
    /**
     * Alias a type.
     *
     * @param   string  $concrete   Concrete class or interface name
     * @param   string  $alias      An alias for the specified class or interface
     *
     * @return  \Opis\Colibri\ContractCollectorInterface    Self reference
     */
    
    public function alias($concrete, $alias);
    
    
    /**
     * Extends a registered type.
     *
     * @param   string      $abstract
     * @param   \Closure    $alias
     *
     * @return  \Opis\Container\Extender
     */
    
    public function extend($abstract, Closure $extender);
    
    
    /**
     * Register a singleton binding with the container.
     *
     * @param   string          $abstract   Class name or interface name
     * @param   \Closure|string $concrete   (optional) Concrete class or interface implementation
     *
     * @return  \Opis\Container\Dependency
     */
    
    public function singleton($abstract, $concrete = null);
}

/**
 * Collects event handlers
 */

interface EventHandlerCollectorInterface
{
    /**
     * Register a new event handler
     *
     * @param   string      $event      Event name
     * @param   \Closure    $callback   A callback that will be executed
     * @param   int         $priority   Event handler's priority
     *
     * @return  \Opis\Events\EventHandler
     */
    
    public function handle($event, Closure $callback, $priority = 0);
}

/**
 * Collects database connections
 */

interface ConnectionCollectorInterface
{
    /**
     * Defines a new database connection
     *
     * @param   string      $name       Connection name
     * @param   \Closure    $callback   Connection constructor callback
     * @param   boolean     $default    (optional) Default flag
     *
     * @return  \Opis\Colibri\ConnectionCollectorInterface  Self reference
     */
    
    public function create($name, Closure $callback, $default = false);
}

/**
 * Collects different kind of storages
 */

interface StorageCollectorInterface
{
    /**
     * Register a new storage
     *
     * @param   string      $storage        Storage name
     * @param   \Closure    $constructor    Storage constructor callback
     * @param   boolean     $default        (optional) Default flag
     *
     * @return  mixed
     */
    
    public function register($storage, Closure $constructor, $default = false);
}

/**
 * Collects cache storages
 */

interface CacheCollectorInterface extends StorageCollectorInterface
{
    
}

/**
 * Collects session storages
 */

interface SessionCollectorInterface extends StorageCollectorInterface
{
    
}

/**
 * Collects config storages
 */

interface ConfigCollectorInterface extends StorageCollectorInterface
{
    
}
