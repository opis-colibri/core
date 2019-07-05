<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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
use Opis\Colibri\{
    Alerts, Application, Core\AppInfo, View, Validation\Validator, Serializable\ControllerCallback
};
use Opis\Database\{
    Connection as DBConnection,
    Database,
    Schema
};
use Opis\DataStore\IDataStore;
use Opis\Intl\Translator\{
    LanguageInfo,
    SubTranslator
};
use Opis\ORM\{
    EntityManager,
    Core\EntityQuery
};
use Opis\Events\Event;
use Opis\Stream\IStream;
use Opis\Http\{Request, Response as HttpResponse, Response};
use Opis\Http\Responses\{
    HtmlResponse, JsonResponse, RedirectResponse
};
use Opis\Session\ISession;
use Opis\View\IView;
use Psr\Log\LoggerInterface;
use Opis\Colibri\Core\Module;

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
 * @return IDataStore
 */
function config(string $storage = 'default'): IDataStore
{
    return Application::getInstance()->getConfig($storage);
}

/**
 * @param string $abstract
 * @return mixed
 */
function make(string $abstract)
{
    return Application::getInstance()->getContainer()->make($abstract);
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
 * @param bool $remove
 * @return bool
 */
function validateCSRFToken(string $token, bool $remove = true): bool
{
    return Application::getInstance()->getCSRFToken()->validate($token, $remove);
}

/**
 * @param string $token
 * @return bool
 */
function removeCSRFToken(string $token): bool
{
    return Application::getInstance()->getCSRFToken()->remove($token);
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
 * @param mixed $default
 * @param string $connection
 * @return mixed|false
 */
function transaction(callable $callback, $default = null, string $connection = 'default')
{
    return connection($connection)->transaction($callback, null, $default);
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
    return Application::getInstance()->getEventDispatcher()->dispatch(new Event($event, $cancelable));
}

/**
 * @param Event $event
 * @return Event
 */
function dispatch(Event $event): Event
{
    return Application::getInstance()->getEventDispatcher()->dispatch($event);
}

/**
 * @return Request
 */
function request(): Request
{
    return Application::getInstance()->getHttpRequest();
}

/**
 * @param string|IStream|array|\stdClass $body
 * @param int $status
 * @param array $headers
 * @return HttpResponse|JsonResponse|HtmlResponse
 */
function response($body, int $status = 200, array $headers = []): HttpResponse
{
    if (is_array($body) || $body instanceof \stdClass) {
        return new JsonResponse($body, $status, $headers);
    }

    return new HtmlResponse($body, $status, $headers);
}

/**
 * @param int $status
 * @param string|IStream|array|null $body
 * @param array $headers
 * @return HttpResponse|JsonResponse|HtmlResponse
 */
function httpError(int $status, $body = null, array $headers = []): HttpResponse
{
    if ($body === null && $status >= 400) {
        $body = view('error.' . $status, [
            'status' => $status,
            'message' => Response::HTTP_STATUS[$status] ?? 'HTTP Error',
        ]);
    }

    return response($body, $status, $headers);
}

/**
 * @param string $location
 * @param int $code
 * @return RedirectResponse
 */
function redirect(string $location, int $code = 301): RedirectResponse
{
    return new RedirectResponse($location, $code);
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
 * @return ISession
 */
function session(): ISession
{
    return Application::getInstance()->getSession();
}

/**
 * @return Alerts
 */
function alerts(): Alerts
{
    return Application::getInstance()->getAlerts();
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
    return Application::getInstance()->getFormatter()->replace($text, $placeholders);
}

/**
 * @param string $key
 * @param array|null $params
 * @param int $count
 * @param string|LanguageInfo|null $language
 * @return string
 */
function t(string $key, array $params = null, int $count = 1, $language = null): string
{
    return Application::getInstance()->getTranslator()->translateKey($key, $params ?? [], $count, $language);
}

/**
 * @param string $ns
 * @return SubTranslator
 */
function tns(string $ns): SubTranslator
{
    return Application::getInstance()->getTranslator()->subTranslator($ns);
}

/**
 * @param string $path
 * @return string
 */
function getURI(string $path): string
{
    return rtrim(info()->webPath(), '/') . '/' . ltrim($path, '/');
}

/**
 * @param string $module
 * @param string $path
 * @return string
 */
function asset(string $module, string $path): string
{
    return Application::getInstance()->resolveAsset($module, $path);
}

/**
 * @param string $class
 * @param string $method
 * @param bool $static
 * @return callable
 */
function controller(string $class, string $method, bool $static = false): callable
{
    return ControllerCallback::get($class, $method, $static);
}

/**
 * @param string $module
 * @return Module
 */
function module(string $module): Module
{
    return Application::getInstance()->getModule($module);
}

/**
 * @param string $name
 * @param array $vars
 * @return View
 */
function view(string $name, array $vars = []): View
{
    return new View($name, $vars);
}

/**
 * @param $view
 * @return string|IView
 */
function render($view): string
{
    return Application::getInstance()->getViewRenderer()->render($view);
}

/**
 * @param string $sep
 * @return string
 */
function uuid4(string $sep = '-'): string
{
    try {
        return sprintf("%08x$sep%04x$sep%04x$sep%04x$sep%012x",
            random_int(0, 0xffffffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffffffffffff)
        );
    } catch (\Exception $e) {
        return sprintf("%08x$sep%04x$sep%04x$sep%04x$sep%012x",
            rand(0, 0xffffffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffffffffffff)
        );
    }
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

    try {
        for ($i = 0; $i < $length; $i++) {
            $str .= $key[random_int(0, $limit)];
        }
    } catch (\Exception $e) {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $key[rand(0, $limit)];
        }
    }

    return $str;
}

function convertToCase(string $value, string  $to='snake_case', string $from = 'camelCase'): string
{
    $allowed = ['PascalCase', 'camelCase', 'snake_case', 'kebab-case'];

    if (!in_array($to, $allowed) || !in_array($from, $allowed)) {
        return $value;
    }

    switch ($from) {
        case 'camelCase':
        case 'PascalCase':
            if ($from[0] === 'P') {
                $value = lcfirst($value);
            }
            preg_match_all('~([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)~', $value, $matches);
            $words = [];
            foreach ($matches[0] as $match) {
                $words[] = strtolower($match);
            }
            break;
        case 'snake_case':
        case 'kebab-case':
            $words = explode($from[0] === 's' ? '_' : '-', strtolower($value));
            break;
        default:
            $words = [];
    }


    switch ($to) {
        case 'camelCase':
        case 'PascalCase':
            foreach ($words as &$word) {
                $word = ucfirst($word);
            }
            if ($to[0] === 'c') {
                $words[0] = lcfirst($words[0]);
            }
            $separator = '';
            break;
        case 'snake_case':
            $separator = '_';
            break;
        case 'kebab-case':
            $separator = '-';
            break;
        default:
            $separator = '';
    }

    return implode($separator, $words);
}

/**
 * @param string $type
 * @param bool $fresh
 * @return mixed
 */
function collect(string $type, bool $fresh = false)
{
    return Application::getInstance()->getCollector()->collect($type, $fresh);
}

/**
 * @param bool $fresh
 * @return bool
 */
function recollect(bool $fresh = true): bool
{
    return Application::getInstance()->getCollector()->recollect($fresh);
}