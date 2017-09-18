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

namespace Opis\Colibri\Functions;

use Opis\Cache\CacheInterface;
use Opis\Colibri\{Application, AppInfo, Module, View, Validation\Validator, Serializable\ControllerCallback};
use Opis\Colibri\HttpResponse\{
    Redirect as RedirectResponse,
    PageNotFound as PageNotFoundResponse,
    AccessDenied as AccessDeniedResponse
};
use Opis\Config\ConfigInterface;
use Opis\Database\{Connection as DBConnection, Database, Schema};
use Opis\ORM\{EntityManager, Core\EntityQuery};
use Opis\Events\Event;
use Opis\Http\{Request};
use Opis\Session\Session;
use Opis\View\IView;
use Psr\Log\LoggerInterface;

/**
 * @return Application
 */
function app(): Application
{
    return Application::getInstance();
}

/**
 * @param string $storage
 * @return CacheInterface
 */
function cache(string $storage = 'default'): CacheInterface
{
    return Application::getInstance()->getCache($storage);
}

/**
 * @param string $storage
 * @return ConfigInterface
 */
function config(string $storage = 'default'): ConfigInterface
{
    return Application::getInstance()->getConfig($storage);
}

/**
 * @param string $abstract
 * @param array $arguments
 * @return mixed
 */
function make(string $abstract, array $arguments = [])
{
    return Application::getInstance()->getContainer()->make($abstract, $arguments);
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
 * @return DBConnection
 */
function connection(string $name = 'default'): DBConnection
{
    return Application::getInstance()->getConnection($name);
}

/**
 * @param string $connection
 * @return Database
 */
function db(string $connection = 'default'): Database
{
    return Application::getInstance()->getDatabase($connection);
}

/**
 * @param string|null $connection
 * @return Schema
 */
function schema(string $connection = 'default'): Schema
{
    return Application::getInstance()->getSchema($connection);
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
        'return' => null,
        'throw' => false,
        'error' => null,
        'success' => null,
    ];

    $pdo = connection($options['connection'])->getPDO();

    if($pdo->inTransaction()){
        return $callback();
    }

    $result = null;
    try{
        $pdo->beginTransaction();
        $result = $callback();
        $pdo->commit();
        if(isset($options['success']) && is_callable($options['success'])){
            $options['success']();
        }
    } catch (\Exception $exception){
        $pdo->rollBack();
        if($options['throw']){
            throw  $exception;
        }
        if(isset($options['error']) && is_callable($options['error'])){
            $options['error']($exception);
        }
        if(isset($options['return'])){
            $result = $options['return'];
        }
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
    return Application::getInstance()->getEntityManager($connection)->query($class);
}

/**
 * @param string $connection
 * @return EntityManager
 */
function entityManager(string $connection = 'default'): EntityManager
{
    return Application::getInstance()->getEntityManager($connection);
}

/**
 * @param string $event
 * @param bool $cancelable
 * @return Event
 */
function emit(string $event, bool $cancelable = false): Event
{
    return Application::getInstance()->getEventTarget()->dispatch(new Event($event, $cancelable));
}

/**
 * @param Event $event
 * @return Event
 */
function dispatch(Event $event): Event
{
    return Application::getInstance()->getEventTarget()->dispatch($event);
}

/**
 * @return Request
 */
function request(): Request
{
    return Application::getInstance()->getHttpRequest();
}

/**
 * @param string $location
 * @param int $code
 * @param array $query
 * @return RedirectResponse
 */
function redirect(string $location, int $code = 302, array $query = array()): RedirectResponse
{
    if (!empty($query)) {
        foreach ($query as $key => $value) {
            $query[$key] = $key . '=' . urlencode($value);
        }
        $location = rtrim($location) . '?' . implode('&', $query);
    }

    return new RedirectResponse($location, $code);
}

/**
 * @param null|mixed|string $body
 * @return PageNotFoundResponse
 */
function pageNotFound($body = null): PageNotFoundResponse
{
    if($body === null){
        $body = view('error.404', ['path' => request()->path()]);
    }

    return new PageNotFoundResponse($body);
}

/**
 * @param null|string|mixed $body
 * @return AccessDeniedResponse
 */
function accessDenied($body = null): AccessDeniedResponse
{
    if($body === null){
        $body = view('error.403', ['path' => request()->path()]);
    }

    return new AccessDeniedResponse($body);
}

/**
 * @return Validator
 */
function validator(): Validator
{
    return Application::getInstance()->getValidator();
}

/**
 * @return AppInfo
 */
function info(): AppInfo
{
    return Application::getInstance()->getAppInfo();
}

/**
 * @param string $logger
 * @return LoggerInterface
 */
function logger(string $logger = 'default'): LoggerInterface
{
    return Application::getInstance()->getLog($logger);
}

/**
 * @param string|null $storage
 * @return Session
 */
function session(string $storage = 'default'): Session
{
    return Application::getInstance()->getSession($storage);
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
    $var = Application::getInstance()->getVariables();
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
    return Application::getInstance()->getPlaceholder()->replace($text, $placeholders);
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
    return Application::getInstance()->getTranslator()->translate($sentence, $placeholders, $lang);
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

    if($module === '*'){
        return getURL($assetsPath . '/' . ltrim($path, '/'), $full);
    }

    $module = str_replace('/', '.', $module);

    return getURL($assetsPath . '/' . $module . '/' . ltrim($path, '/'), $full);
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
 * @return string|IView
 */
function render($view): string
{
    return Application::getInstance()->getViewApp()->render($view);
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

/**
 * @param int $length
 * @return string
 */
function random_str(int $length): string
{
    static $key = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    static $limit = 61;

    $str = '';

    for ($i = 0; $i < $length; ++$i) {
        $str .= $key[random_int(0, $limit)];
    }

    return $str;
}
