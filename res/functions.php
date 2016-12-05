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

namespace Opis\Colibri\Functions;

use Opis\Cache\CacheInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Opis\Colibri\Validator;
use Opis\Colibri\View;
use Opis\Colibri\Module;
use Opis\Colibri\Serializable\ControllerCallback;
use Opis\Config\ConfigInterface;
use Opis\Database\Connection as DBConnection;
use Opis\Database\Database;
use Opis\Database\EntityManager;
use Opis\Database\ORM\EntityQuery;
use Opis\Database\Schema;
use Opis\Events\Event;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\HttpRouting\HttpError;
use Opis\Session\Session;
use Opis\View\ViewableInterface;
use Psr\Log\LoggerInterface;

/**
 * @return Application
 */
function app(): Application
{
    static $app;

    if($app === null){
        $app = Application::getInstance();
    }

    return $app;
}

/**
 * @param string $storage
 * @return CacheInterface
 */
function cache(string $storage = 'default'): CacheInterface
{
    static $cache = [];

    return $cache[$storage] ?? (app()->getCache($storage));
}

/**
 * @param string $storage
 * @return ConfigInterface
 */
function config(string $storage = 'default'): ConfigInterface
{
    static $config = [];

    return $config[$storage] ?? (app()->getConfig($storage));
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
        $container = app()->getContainer();
    }

    return $container->make($abstract, $arguments);
}

/**
 * @return string
 */
function generateCSRFToken(): string
{
    return app()->getCSRFToken()->generate();
}

/**
 * @param string $token
 * @return bool
 */
function validateCSRFToken(string $token): bool
{
    return app()->getCSRFToken()->validate($token);
}

/**
 * @param string $name
 * @return DBConnection
 */
function connection(string $name = 'default'): DBConnection
{
    static $connection = [];

    return $connection[$name] ?? ($connection[$name] = app()->getConnection($name));
}

/**
 * @param string $connection
 * @return Database
 */
function db(string $connection = 'default'): Database
{
    static $db = [];

    return $db[$connection] ?? ($db[$connection] = app()->getDatabase($connection));
}

/**
 * @param string|null $connection
 * @return Schema
 */
function schema(string $connection = 'default'): Schema
{
    static $schema = [];

    return $schema[$connection] ?? ($schema[$connection] = app()->getSchema($connection));
}

/**
 * @param callable $callback
 * @param array $options
 * @return mixed
 * @throws \Exception
 */
function transaction(callable $callback, array $options = [])
{
    $options += [
        'connection' => 'default',
        'return' => false,
        'throw' => false,
    ];

    $pdo = connection($options['connection'])->getPDO();

    if($pdo->inTransaction()){
        return $callback();
    }

    try{
        $pdo->beginTransaction();
        $result = $callback();
        $pdo->commit();
    } catch (\Exception $exception){
        if($options['throw']){
            throw  $exception;
        }
        $pdo->rollBack();
        $result = $options['return'];
    }

    return $result;
}

/**
 * @param string $class
 * @param string $connection
 * @return EntityQuery
 */
function entity(string $class, string $connection = 'default'): EntityQuery
{
    return entityManger($connection)->query($class);
}

/**
 * @param string $connection
 * @return EntityManager
 */
function entityManger(string $connection = 'default'): EntityManager
{
    static $em = [];
    return $em[$connection] ?? ($em[$connection] = app()->getEntityManager($connection));
}

/**
 * @param string $event
 * @param bool $cancelable
 * @return Event
 */
function emit(string $event, bool $cancelable = false): Event
{
    return dispatch(new Event($event, $cancelable));
}

/**
 * @param Event $event
 * @return Event
 */
function dispatch(Event $event): Event
{
    static $target;

    if($target === null){
        $target = app()->getEventTarget();
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
        $request = app()->getHttpRequest();
    }

    return $request;
}

/**
 * @return Response
 */
function response(): Response
{
    static $response;

    if($response === null){
        $response = app()->getHttpResponse();
    }

    return $response;
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
 * @return HttpError
 */
function pageNotFound(): HttpError
{
    return HttpError::pageNotFound();
}

/**
 * @return HttpError
 */
function accessDenied(): HttpError
{
    return HttpError::accessDenied();
}

/**
 * @return Validator
 */
function validator(): Validator
{
    return app()->getValidator();
}

/**
 * @return AppInfo
 */
function info(): AppInfo
{
    static $info;

    if($info === null){
        $info = app()->getAppInfo();
    }

    return $info;
}

/**
 * @param string $logger
 * @return LoggerInterface
 */
function logger(string $logger = 'default'): LoggerInterface
{
    static $log = [];

    return $log[$logger] ?? ($log[$logger] = app()->getLog($logger));
}

/**
 * @param string|null $storage
 * @return Session
 */
function session(string $storage = 'default'): Session
{
    static $session = [];

    return $session[$storage] ?? ($session[$storage] =  app()->getSession($storage));
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
        $var = app()->getVariables();
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
    static $placeholder;

    if($placeholder === null){
        $placeholder = app()->getPlaceholder();
    }

    return $placeholder->replace($text, $placeholders);
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
        $translator = app()->getTranslator();
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
function asset(string $module, string $path, bool $full = false): string
{
    static $assetsPath;

    if($assetsPath === null){
        $assetsPath = info()->assetsPath();
    }
    $base = strpos($module, '/') === false ? $assetsPath : $assetsPath . '/module';
    return getURL($base . '/' . $module . '/' . ltrim($path, '/'), $full);
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
    return new Module($module);
}

/**
 * @param string $name
 * @param array $arguments
 * @return View
 */
function view(string $name, array $arguments = []): View
{
    return new View($name, $arguments);
}

/**
 * @param $view
 * @return string|ViewableInterface
 */
function render($view): string
{
    static $viewApp;

    if($viewApp === null){
        $viewApp = app()->getViewApp();
    }

    return $viewApp->render($view);
}

/**
 * @param string $sep
 * @return string
 */
function uuid4(string $sep = '-'): string
{
    return sprintf("%08x$sep%04x$sep%04x$sep%04x$sep%012x",
        random_int(0, 0xffffffff),
        random_int(0, 0xffff),
        random_int(0, 0x0fff) | 0x4000,
        random_int(0, 0x3fff) | 0x8000,
        random_int(0, 0xffffffffffff)
    );
}
