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

use Opis\Colibri\App;
use Opis\Colibri\Event;
use Opis\Colibri\View;
use Opis\Colibri\ModuleInfo;
use Opis\Colibri\Module;

function Using($contract, array $arguments = array())
{
    return App::contracts()->make($contract, $arguments);
}

function Database($connection = null)
{
    return $connection === null ? App::systemDatabase() : App::connections()->database($connection);
}

function Schema($connection = null)
{
    return $connection === null ? App::systemSchema() : App::connections()->schema($connection);
}

function Cache($storage = null)
{
    return $storage === null ? App::systemCache() : App::cache()->get($storage);
}

function Config($storage = null)
{
    return $storage === null ? App::systemConfig() : App::configs()->get($storage);
}

function Session($storage = null)
{
    return $storage === null ? App::systemSession() : App::session()->get($storage);
}

function Emit($name, $cancelable = false)
{
    return Dispatch(new Event($name, $cancelable));
}

function Dispatch(Event $event)
{
    return $event->dispatch();
}

function View($name, array $arguments = array())
{
    return new View($name, $arguments);
}

function Render($view)
{
    return App::view()->render($view);
}

function Asset($module, $path, $full = true)
{
    return UriForPath('/assets/module/' . strtolower($module) . '/' . trim($path), $full);
}

function HttpRequest()
{
    return App::systemRequest();
}

function HttpResponse()
{
    return App::systemRequest()->response();
}

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

function UriForPath($path, $full = true)
{
    return $full ? HttpRequest()->uriForPath($path) : HttpRequest()->baseUrl() . $path;
}

function CreateUrl($route, array $args = array())
{
    $routes = App::httpRoutes();
    if(!isset($routes[$route]))
    {
        return $route;
    }
    $route = $routes[$route];
    $args = $args + $route->getDefaults();
    $args = array_map('rawurlencode', $args);
    return $route->getCompiler()->build($route->getPattern(), $args);
}
