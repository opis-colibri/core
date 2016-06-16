<?php

namespace Opis\Colibri;
use Opis\Cache\Cache;
use Opis\Config\Config;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\Schema;
use Opis\Session\Session;
use Psr\Log\LoggerInterface;

/**
 * @param string $name
 * @param array $arguments
 * @return View
 */
function view(string $name, array $arguments = array()) : View
{
    return Application::getGlobal()->getViewRouter()->view($name, $arguments);
}

/**
 * @param string|null $storage
 * @return \Opis\Cache\Cache
 */
function cache(string $storage = null): Cache
{
    return Application::getGlobal()->cache($storage);
}

/**
 * @param string|null $storage
 * @return Config
 */
function config(string $storage = null): Config
{
    return Application::getGlobal()->config($storage);
}

/**
 * @param string|null $storage
 * @return Session
 */
function session(string $storage = null): Session
{
    return Application::getGlobal()->session($storage);
}

/**
 * @param string|null $name
 * @return Connection
 */
function connection(string $name = null): Connection
{
    return Application::getGlobal()->connection($name);
}

/**
 * @param string|null $connection
 * @return Database
 */
function db(string $connection = null): Database
{
    return Application::getGlobal()->database($connection);
}

/**
 * @param string|null $connection
 * @return Schema
 */
function schema(string $connection = null): Schema
{
    return Application::getGlobal()->schema($connection);
}

/**
 * @param string|null $logger
 * @return LoggerInterface
 */
function log(string $logger = null): LoggerInterface
{
    return Application::getGlobal()->log($logger);
}

/**
 * @param string $contract
 * @param array $arguments
 * @return mixed
 */
function make(string $contract, array $arguments = array())
{
    return Application::getGlobal()->make($contract, $arguments);
}

/**
 * @param string $event
 * @param bool $cancelable
 * @return Event
 */
function emit(string $event, bool $cancelable = false): Event
{
    return Application::getGlobal()->emit($event, $cancelable);
}

/**
 * @param Event $event
 * @return Event
 */
function dispatch(Event $event): Event
{
    return Application::getGlobal()->dispatch($event);
}

/**
 * @param string $sentence
 * @param array $placehoders
 * @param string|null $lang
 * @return string
 */
function t(string $sentence, array $placehoders = array(), string $lang = null): string
{
    return Application::getGlobal()->getTranslator()->translate($sentence, $placehoders, $lang);
}

/**
 * @return string
 */
function csrf() : string
{
    return Application::getGlobal()->getCSRFToken()->generate();
}

