<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

use Opis\Colibri\App;
use Opis\Colibri\Event;
use Opis\Colibri\View;
use Opis\Colibri\ModuleInfo;
use Opis\Colibri\Module;

/**
 * Returns an instance of the specified contract or class
 *
 * @param   string  $contract   Contract name or class name
 * @param   array   $arguments  (optional) Arguments that will be passed to the contract constructor
 *
 * @return  mixed
 */

function Using($contract, array $arguments = array())
{
    return App::loadFromSystemCache('Contracts')->make($contract, $arguments);
}

/**
 * Returns a database abstraction layer
 *
 * @param   string  $connection (optional) Connection name
 *
 * @return  \Opis\Database\Database
 */

function Database($connection = null)
{
    return $connection === null ? App::systemDatabase() : App::loadFromSystemCache('Connections')->database($connection);
}

/**
 * Returns a database shema abstraction layer
 *
 * @param   string  $connection (optional) Connection name
 *
 * @return  \Opis\Database\Schema
 */

function Schema($connection = null)
{
    return $connection === null ? App::systemDatabase()->schema() : App::loadFromSystemCache('Connections')->database($connection)->schema();
}

/**
 * Returns a caching storage
 *
 * @param   string  $storage (optional) Storage name
 *
 * @return  \Opis\Cache\Cache
 */

function Cache($storage = null)
{
    return $storage === null ? App::systemCache() : App::loadFromSystemCache('CacheStorages')->get($storage);
}

/**
 * Returns a config storage
 *
 * @param   string  $storage (optional) Storage name
 *
 * @return  \Opis\Config\Config
 */

function Config($storage = null)
{
    return $storage === null ? App::systemConfig() : App::loadFromSystemCache('ConfigStorages')->get($storage);
}

/**
 * Returns a session storage
 *
 * @param   string  $storage (optional) Storage name
 *
 * @return  \Opis\Session\Session
 */

function Session($storage = null)
{
    return $storage === null ? App::systemSession() : App::loadFromSystemCache('SessionStorages')->get($storage);
}

/**
 * Emit a new event
 *
 * @param   string  $name       Event name
 * @param   boolean $cancelable (optional) Cancelable flag
 *
 * @return  \Opis\Colibri\Event
 */

function Emit($name, $cancelable = false)
{
    return Dispatch(new Event($name, $cancelable));
}

/**
 * Dispatch an event
 *
 * @param   \Opis\Colibri\Event $event  An event to be dispatched
 *
 * @return  \Opis\Colibri\Event The dispatched event
 */

function Dispatch(Event $event)
{
    return $event->dispatch();
}

/**
 * Creates a new view
 *
 * @param   string  $name       View name
 * @param   array   $arguments  (optional) View's arguments
 *
 * @return  \Opis\Colibri\View
 */

function View($name, array $arguments = array())
{
    return new View($name, $arguments);
}

/**
 * Renders a view
 *
 * @param   string|\Opis\View\ViewInterface $view   The view that will be rendered
 *
 * @return  string
 */

function Render($view)
{
    return App::systemView()->render($view);
}

/**
 * Returns a path to a module's asset
 *
 * @param   string  $module Module name
 * @param   string  $path   Module's resource relative path
 * @param   boolean $full   Full path flag
 *
 * @return  string
 */

function Asset($module, $path, $full = true)
{
    return UriForPath('/assets/module/' . strtolower($module) . '/' . trim($path), $full);
}

/**
 * Return the underlying HTTP request object
 *
 * @return  \Opis\Http\Request
 */

function HttpRequest()
{
    return App::systemRequest();
}

/**
 * Return the underlying HTTP response object
 *
 * @return  \Opis\Http\Response
 */

function HttpResponse()
{
    return App::systemRequest()->response();
}

/**
 * Redirects to a new locations
 *
 * @param   string  $location   The new location
 * @param   int     $code       Redirect status code
 * @param   array   $query      (optional)  Query arguments
 */

function HttpRedirect($location, $code = 302, array $query = array())
{
    if(!empty($query))
    {
        foreach($query as $key => $value)
        {
            $query[$key] = $key . '=' . $value;
        }
        
        $location = rtrim($location) . '?' . implode('&', $query);
        
    }
    
    HttpResponse()->redirect($location, $code);
}

/**
 * Get informations about a module
 *
 * @param   string  $module Module name
 *
 * @return  \Opis\Colibri\ModuleInfo
 */

function Module($module)
{
    static $list = array();
    
    $module = strtolower($module);
    
    if(!isset($list[$module]))
    {
        $list[$module] = new ModuleInfo($module);
    }
    
    return $list[$module];
}

/**
 * Get the URI for a path
 *
 * @param   string  $path   The path
 * @param   boolean $full   Full URI flag
 *
 * @return  string
 */

function UriForPath($path, $full = true)
{
    return $full ? HttpRequest()->uriForPath($path) : HttpRequest()->baseUrl() . $path;
}

/**
 * Creates an URL from a named route
 *
 * @param   string  $route  Route name
 * @param   array   $args   (optional) Route wildecard's values
 *
 * @return  string
 */

function CreateUrl($route, array $args = array())
{
    $routes = App::loadFromSystemCache('Routes');
    if(!isset($routes[$route]))
    {
        return $route;
    }
    $route = $routes[$route];
    $args = $args + $route->getDefaults();
    $args = array_map('rawurlencode', $args);
    return $route->getCompiler()->build($route->getPattern(), $args);
}
