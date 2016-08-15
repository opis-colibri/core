<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

namespace Opis\Colibri\Helpers;

use Opis\Cache\Cache;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Opis\Colibri\Event;
use Opis\Colibri\Model;
use Opis\Colibri\Module;
use Opis\Colibri\Serializable\ControllerCallback;
use Opis\Colibri\View;
use Opis\Config\Config;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\Schema;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Session\Session;
use Opis\View\ViewableInterface;
use Psr\Log\LoggerInterface;

/**
 * @param string $storage
 * @return Cache
 */
function cache(string $storage = 'default'): Cache
{
    static $cache = [];

    return $cache[$storage] ?? (Application::getInstance()->getCache($storage));
}

/**
 * @param string $storage
 * @return Config
 */
function config(string $storage = 'default'): Config
{
    static $config = [];

    return $config[$storage] ?? (Application::getInstance()->getConfig($storage));
}

/**
 * @param string $abstract
 * @param array $arguments
 * @return mixed
 */
function make(string $abstract, array $arguments = [])
{
    static $container;

    if($container === null){
        $container = Application::getInstance()->getContainer();
    }

    return $container->make($abstract, $arguments);
}

/**
 * @return string
 */
function generateCSRFToken(): string
{
    return Application::getInstance()->getCSRFToken()->generate();
}

/**
 * @param string $token
 * @return bool
 */
function validateCSRFToken(string $token): bool
{
    return Application::getInstance()->getCSRFToken()->validate($token);
}

/**
 * @param string $name
 * @return Connection
 */
function connection(string $name = 'default'): Connection
{
    static $connection = [];

    return $connection[$name] ?? ($connection[$name] = Application::getInstance()->getConnection($name));
}

/**
 * @param string $connection
 * @return Database
 */
function db(string $connection = 'default'): Database
{
    static $db = [];

    return $db[$connection] ?? ($db[$connection] = Application::getInstance()->getDatabase($connection));
}

/**
 * @param string|null $connection
 * @return Schema
 */
function schema(string $connection = null): Schema
{
    static $schema = [];

    return $schema[$connection] ?? ($schema[$connection] = Application::getInstance()->getSchema($connection));
}

/**
 * @param string $class
 * @param string $connection
 * @return Model
 */
function model(string $class, string $connection = 'default'): Model
{
    static $orm = [];
    $instance = $orm[$connection] ?? ($orm[$connection] = Application::getInstance()->getORM($connection));
    return $instance->model($class);
}

/**
 * @param string $event
 * @param bool $cancelable
 * @return Event
 */
function emit(string $event, bool $cancelable = false): Event
{
    return dispatch(new Event(Application::getInstance(), $event, $cancelable));
}

/**
 * @param Event $event
 * @return Event
 */
function dispatch(Event $event): Event
{
    static $target;

    if($target === null){
        $target = Application::getInstance()->getEventTarget();
    }

    return $target->dispatch($event);
}

/**
 * @return Request
 */
function request(): Request
{
    static $request;

    if($request === null){
        $request = Application::getInstance()->getHttpRequest();
    }

    return $request;
}

/**
 * @return Response
 */
function response(): Response
{
    static $resonse;

    if($resonse === null){
        $resonse = Application::getInstance()->getHttpResponse();
    }

    return $resonse;
}

/**
 * @param string $location
 * @param int $code
 * @param array $query
 */
function redirect(string $location, int $code = 302, array $query = array())
{
    if (!empty($query)) {
        foreach ($query as $key => $value) {
            $query[$key] = $key . '=' . urlencode($value);
        }
        $location = rtrim($location) . '?' . implode('&', $query);
    }

    response()->redirect($location, $code);
}

/**
 * @return AppInfo
 */
function info(): AppInfo
{
    static $info;

    if($info === null){
        $info = Application::getInstance()->getAppInfo();
    }

    return $info;
}

/**
 * @param string $logger
 * @return LoggerInterface
 */
function log(string $logger = 'default'): LoggerInterface
{
    static $log = [];

    return $log[$logger] ?? ($log[$logger] = Application::getInstance()->getLog($logger));
}

/**
 * @param string|null $storage
 * @return Session
 */
function session(string $storage = null): Session
{
    static $session = [];

    return $session[$storage] ?? ($session[$storage] =  Application::getInstance()->getSession($storage));
}

/**
 * Get the value of the specified variable
 *
 * @param string $name
 * @param null $default
 * @return null
 */
function v(string $name, $default = null)
{
    static $var;

    if($var === null){
        $var = Application::getInstance()->getVariables();
    }

    return array_key_exists($name, $var) ? $var[$name] : $default;
}

/**
 * Replace
 *
 * @param string $text
 * @param array $placeholders
 * @return string
 */
function r(string $text, array $placeholders): string
{
    static $placehoder;

    if($placehoder === null){
        $placehoder = Application::getInstance()->getPlaceholder();
    }

    return $placehoder->replace($text, $placeholders);
}

/**
 * Translate
 *
 * @param string $sentence
 * @param array $placeholders
 * @param string|null $lang
 * @return string
 */
function t(string $sentence, array $placeholders = [], string $lang = null): string
{
    static $translator;

    if($translator === null){
        $translator = Application::getInstance()->getTranslator();
    }

    return $translator->translate($sentence, $placeholders, $lang);
}

/**
 * @param string $path
 * @param bool $full
 * @return string
 */
function getURL(string $path, bool $full = false): string
{
    return $full ? request()->uriForPath($path) : request()->baseUrl() . '/' . ltrim($path, '/');
}

/**
 * @param string $module
 * @param string $path
 * @param bool $full
 * @return string
 */
function getAsset(string $module, string $path, bool $full = false): string
{
    return getURL(info()->assetsPath() . '/' . $module . '/' . ltrim($path), $full);
}

/**
 * @param string $class
 * @param string $method
 * @param bool $static
 * @return ControllerCallback
 */
function controller(string $class, string $method, bool $static = false): ControllerCallback
{
    return new ControllerCallback($class, $method, $static);
}

/**
 * @param string $module
 * @return Module
 */
function module(string $module): Module
{
    return new Module(Application::getInstance(), $module);
}

/**
 * @param string $name
 * @param array $arguments
 * @return View
 */
function view(string $name, array $arguments = []): View
{
    return new View(Application::getInstance(), $name, $arguments);
}

/**
 * @param $view
 * @return string|ViewableInterface
 */
function render($view): string
{
    static $viewApp;

    if($viewApp === null){
        $viewApp = Application::getInstance()->getViewApp();
    }

    return $viewApp->render($view);
}
