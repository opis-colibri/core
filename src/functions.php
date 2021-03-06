<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

use stdClass;
use Throwable;
use Stringable;
use JsonSerializable;
use Opis\Colibri\Cache\CacheDriver;
use Opis\JsonSchema\Validator;
use Opis\Database\{
    Connection as DBConnection,
    DatabaseHandler,
    Schema,
    EntityManager,
    ORM\Internal\EntityQuery
};
use Opis\Colibri\Config\ConfigDriver;
use Opis\Colibri\I18n\Translator\{
    LanguageInfo,
    SubTranslator
};
use Opis\Colibri\Routing\ControllerCallback;
use Opis\Colibri\Http\{Request, Response};
use Opis\Colibri\Http\Responses\{
    HtmlResponse, JSONResponse, RedirectResponse
};
use Opis\Colibri\Serializable\RelativePath;
use Opis\Colibri\Render\Renderable;
use Psr\Log\LoggerInterface;
use Opis\Colibri\Events\Event;
use Opis\Colibri\Render\View;
use Opis\Colibri\Session\Session;

/**
 * @return Application
 */
function app(): Application
{
    return Application::getInstance();
}

/**
 * @param string|null $storage
 * @return CacheDriver
 */
function cache(?string $storage = null): CacheDriver
{
    return Application::getInstance()->getCache($storage);
}


/**
 * @param string|null $storage
 * @return ConfigDriver
 */
function config(?string $storage = null): ConfigDriver
{
    return Application::getInstance()->getConfig($storage);
}

/**
 * @param string $abstract
 * @return mixed
 */
function make(string $abstract): object
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
 * @param string|null $name
 * @return DBConnection
 */
function connection(?string $name = null): DBConnection
{
    return Application::getInstance()->getConnection($name);
}

/**
 * @param string|null $connection
 * @return DatabaseHandler
 */
function db(?string $connection = null): DatabaseHandler
{
    return Application::getInstance()->getDatabaseHandler($connection);
}

/**
 * @param string|null $connection
 * @return Schema
 */
function schema(?string $connection = null): Schema
{
    return Application::getInstance()->getSchema($connection);
}

/**
 * @param callable $callback
 * @param mixed|null $default
 * @param string|null $connection
 * @return mixed
 */
function transaction(callable $callback, mixed $default = null, ?string $connection = null): mixed
{
    return connection($connection)->transaction($callback, null, $default);
}

/**
 * @param string $class
 * @param string|null $connection
 * @return EntityQuery
 */
function entity(string $class, ?string $connection = null): EntityQuery
{
    return Application::getInstance()->getEntityManager($connection)->query($class);
}

/**
 * @param string|null $connection
 * @return EntityManager
 */
function entityManager(?string $connection = null): EntityManager
{
    return Application::getInstance()->getEntityManager($connection);
}

/**
 * @param string $event
 * @param mixed $data
 * @param bool $cancelable
 * @return Event
 */
function emit(string $event, mixed $data = null, bool $cancelable = false): Event
{
    return Application::getInstance()->getEventDispatcher()->dispatch(new Event($event, $cancelable, $data));
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
 * @return Request|null
 */
function request(): ?Request
{
    return Application::getInstance()->getHttpRequest();
}

/**
 * @param string|array|stdClass|Stringable $body
 * @param int $status
 * @param array $headers
 * @return HtmlResponse|JSONResponse
 */
function response(string|array|stdClass|Stringable $body, int $status = 200, array $headers = []): HtmlResponse|JSONResponse
{
    if (is_array($body) || $body instanceof stdClass || $body instanceof JsonSerializable) {
        return new JSONResponse($body, $status, $headers);
    }

    return new HtmlResponse($body, $status, $headers);
}

/**
 * @param int $status
 * @param string|array|stdClass|Stringable|null $body
 * @param array $headers
 * @return HtmlResponse|JSONResponse
 */
function httpError(int $status, string|array|stdClass|Stringable $body = null, array $headers = []): HtmlResponse|JSONResponse
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
 * @param array $headers
 * @return RedirectResponse
 */
function redirect(string $location, int $code = 302, array $headers = []): RedirectResponse
{
    return new RedirectResponse($location, $code, $headers);
}

/**
 * @return ApplicationInfo
 */
function info(): ApplicationInfo
{
    return Application::getInstance()->getAppInfo();
}

/**
 * @param string|null $logger
 * @return LoggerInterface
 */
function logger(?string $logger = null): LoggerInterface
{
    return Application::getInstance()->getLogger($logger);
}

function logError(Throwable $error, ?string $message = null, ?string $logger = null): void {
    Application::getInstance()->getLogger($logger)->error($message ?? $error->getMessage(), [
        'error' => $error->getMessage(),
        'file' => $error->getFile(),
        'line' => $error->getLine(),
        'code' => $error->getCode(),
        'trace' => $error->getTraceAsString(),
    ]);
}

/**
 * @param string|null $name
 * @return Session
 */
function session(?string $name = null): Session
{
    return Application::getInstance()->getSession($name);
}

/**
 * @param string $key
 * @param array|null $params
 * @param int $count
 * @param string|LanguageInfo|null $language
 * @return string
 */
function t(string $key, array $params = null, int $count = 1, string|LanguageInfo|null $language = null): string
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

function lang(?string $name = null): LanguageInfo
{
    return Application::getInstance()->getTranslator()->language($name);
}

/**
 * @param string $path
 * @return string
 */
function getURI(string $path): string
{
    return rtrim(info()->webPath(), '/') . '/' . ltrim($path, '/');
}

function asset(string $module, string $path): string
{
    return Application::getInstance()->resolveAsset($module, $path);
}

function controller(string $class, string $method, bool $static = false): callable
{
    return ControllerCallback::get($class, $method, $static);
}

function module(string $module): Module
{
    return Application::getInstance()->getModule($module);
}

function module_path(string $module, string $path = ''): RelativePath
{
    return new RelativePath($module, $path);
}

function view(string $name, array $vars = []): View
{
    return new View($name, $vars);
}

function render(string|Stringable $view): string
{
    if ($view instanceof Renderable) {
        return Application::getInstance()->getViewRenderer()->render($view);
    }
    return $view;
}

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
    } catch (Throwable) {
        return sprintf("%08x$sep%04x$sep%04x$sep%04x$sep%012x",
            rand(0, 0xffffffff),
            rand(0, 0xffff),
            rand(0, 0x0fff) | 0x4000,
            rand(0, 0x3fff) | 0x8000,
            rand(0, 0xffffffffffff)
        );
    }
}

function random_str(int $length): string
{
    static $key = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    static $limit = 61;

    $str = '';

    try {
        for ($i = 0; $i < $length; $i++) {
            $str .= $key[random_int(0, $limit)];
        }
    } catch (Throwable) {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $key[rand(0, $limit)];
        }
    }

    return $str;
}

function convertToCase(string $value, string $to='snake_case', string $from = 'camelCase'): string
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

function collect(string $type, bool $fresh = false): object
{
    return Application::getInstance()->getCollector()->collect($type, $fresh);
}

function registerCollector(string $class, string $description): void
{
    Application::getInstance()->getCollector()->register($class, $description);
}

function unregisterCollector(string $class): void
{
    Application::getInstance()->getCollector()->unregister($class);
}

function validator(): Validator
{
    return Application::getInstance()->getValidator();
}

function recollect(bool $fresh = true): bool
{
    return Application::getInstance()->getCollector()->recollect($fresh);
}

function install(Module $module, bool $recollect = true, bool $recursive = false): bool
{
    return Application::getInstance()->install($module, $recollect, $recursive);
}

function uninstall(Module $module, bool $recollect = true, bool $recursive = false): bool
{
    return Application::getInstance()->uninstall($module, $recollect, $recursive);
}

function enable(Module $module, bool $recollect = true, bool $recursive = false): bool
{
    return Application::getInstance()->enable($module, $recollect, $recursive);
}

function disable(Module $module, bool $recollect = true, bool $recursive = false): bool
{
    return Application::getInstance()->disable($module, $recollect, $recursive);
}

/**
 * @param bool $clear
 * @return Module[]
 */
function getModules(bool $clear = false): array
{
    return Application::getInstance()->getModules($clear);
}

function env(string $key, mixed $default = null): mixed
{
    if (!array_key_exists($key, $_ENV)) {
        return $default;
    }

    $value = $_ENV[$key];

    if (!is_string($value)) {
        return $value;
    } elseif (is_numeric($value)) {
        if (ctype_digit($value)) {
            return (int) $value;
        }
        return (float) $value;
    }

    return match (strtolower($value)) {
        "true", "on", "1", "yes" => true,
        "false", "off", "0", "no" => false,
        "null" => null,
        default => $value
    };
}
