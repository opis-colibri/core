<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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
use ReflectionClass;
use ReflectionMethod;
use Opis\Cache\Cache;
use Opis\Config\Config;
use Opis\Session\Session;
use Opis\HttpRouting\Path;
use Opis\Utils\Placeholder;
use Opis\Database\Database;
use Opis\Utils\ClassLoader;
use Opis\Events\EventTarget;
use SessionHandlerInterface;
use Psr\Log\LoggerInterface;
use Opis\Database\Connection;
use Opis\HttpRouting\HttpError;
use Opis\Http\Request as HttpRequest;
use Doctrine\Common\Annotations\AnnotationReader;
use Opis\Cache\Storage\Memory as DefaultCacheStorage;
use Opis\Config\Storage\Memory as DefaultConfigStorage;
use Opis\Cache\StorageInterface as CacheStorageInterface;
use Opis\Config\Storage\Memory as DefaultTranslateStorage;
use Opis\Config\StorageInterface as ConfigStorageInterface;
use Opis\Colibri\Annotations\Collector as CollectorAnnotation;

class Application
{
    /** @var    array */
    protected $cache = array();

    /** @var    array */
    protected $instances = array();

    /** @var    AppInfo */
    protected $info;

    /**
     * Constructor
     * 
     * @param   AppInfo $info   Application info
     */
    public function __construct(AppInfo $info)
    {
        $this->info = $info;
    }

    /**
     * Include modules
     */
    protected function includeCollectors()
    {
        if (isset($this->instances['includeCollectors'])) {
            return;
        }

        $loader = $this->getClassLoader();
        $manager = $this->getModuleManager();
        $reader = new AnnotationReader();

        foreach ($this->config()->read('modules.enabled') as $module => $status) {

            if (!$status || null === $collector = $this->config()->read("modules.list.$module.collector")) {
                continue;
            }

            $class = $manager->collectorClass($module);
            $loader->mapClass($class, $collector);

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if (!$reflection->isSubclassOf('\\Opis\\Colibri\\ModuleCollector')) {
                continue;
            }

            $instance = new $class();

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

                $name = $method->getShortName();

                if (substr($name, 0, 2) === '__') {
                    if ($name === '__invoke') {
                        $instance($this, $loader, $manager, $reader);
                    }
                    continue;
                }

                $annotation = $reader->getMethodAnnotation($method, 'Opis\\Colibri\\Annotations\\Collector');


                if ($annotation == null) {
                    $annotation = new CollectorAnnotation();
                }

                if ($annotation->name === null) {
                    $annotation->name = $name;
                }

                $callback = function($collector, $app) use($instance, $name) {
                    $instance->{$name}($collector, $app);
                };

                $this->define($annotation->name, $callback, $annotation->priority);
            }
        }

        $this->instances['includeCollectors'] = true;
    }

    /**
     * Init method
     */
    public function init()
    {
        $list = $this->config()->read('modules.list');

        foreach ($this->config()->read('modules.enabled') as $module => $status) {
            if ($status) {
                $this->loadModule($module, $list);
            }
        }

        $this->emit('system.init');
    }

    /**
     * Load an existing module
     * 
     * @param   string      $module Module's name
     * @param   array|null  $list   (optional) A list of available modules
     */
    public function loadModule($module, $list = null)
    {
        if ($list === null) {
            $list = $this->config()->read('modules.list');
        }

        $module = $list[$module];

        $this->getClassLoader()->registerNamespace($module['namespace'], $module['source']);

        if ($module['include'] !== null) {
            include_once($module['include']);
        }
    }

    /**
     * Define a new collector
     * 
     * @param   string  $entry
     * @param   Closure $callback
     * @param   int     $priority
     */
    public function define($entry, Closure $callback, $priority = 0)
    {
        $this->getCollector()->handle(strtolower($entry), $callback, $priority);
    }

    /**
     * Get informations about this application
     * 
     * @return  AppInfo
     */
    public function info()
    {
        return $this->info;
    }

    /**
     * Set the default database connection
     * 
     * @param   Connection  $connection
     * 
     * @return  $this
     */
    public function setDefaultDatabaseConnection(Connection $connection)
    {
        $this->instances['connection'] = $connection;
        return $this;
    }

    /**
     * Set the default cache storage
     * 
     * @param   \Opis\Cache\StorageInterface    $storage
     * 
     * @return  $this
     */
    public function setDefaultCacheStorage(CacheStorageInterface $storage)
    {
        $this->instances['cacheStorage'] = $storage;
        return $this;
    }

    /**
     * Set the default config storage
     * 
     * @param   \Opis\Config\StorageInterface   $storage
     * 
     * @return  $this;
     */
    public function setDefaultConfigStorage(ConfigStorageInterface $storage)
    {
        $this->instances['configStorage'] = $storage;
        return $this;
    }

    /**
     * Set the default session storage
     * 
     * @param   \SessionHandlerInterface    $storage
     * 
     * @return  $this
     */
    public function setDefaultSessionStorage(SessionHandlerInterface $storage)
    {
        $this->instances['sessionStorage'] = $storage;
        return $this;
    }

    /**
     * Set the storage were the translations are kept
     * 
     * @param   \Opis\Config\StorageInterface   $storage
     * 
     * @return  $this
     */
    public function setDefaultTranslateStorage(ConfigStorageInterface $storage)
    {
        $this->instances['translateStorage'] = $storage;
        return $this;
    }

    /**
     * Set the default loggger
     * 
     * @param   \Psr\Log\LoggerInterface
     * 
     * @return  $this
     */
    public function setDefaultLogger(LoggerInterface $logger)
    {
        $this->instances['logger'] = $logger;
        return $this;
    }

    /**
     * Set the HTTP request object
     * 
     * @param   HttpRequest|null    $request    (optional)
     * 
     * @return  $this
     */
    public function setHttpRequestObject(HttpRequest $request)
    {
        $this->instances['request'] = $request;
        return $this;
    }

    /**
     * Get the HTTP router
     * 
     * @return  \Opis\Colibri\Router
     */
    public function getHttpRouter()
    {
        if (!isset($this->instances['httpRouter'])) {
            $this->instances['httpRouter'] = new HttpRouter($this);
        }
        return $this->instances['httpRouter'];
    }

    /**
     * Get the View router
     * 
     * @return  \Opis\Colibri\ViewRouter
     */
    public function getViewRouter()
    {
        if (!isset($this->instances['viewRouter'])) {
            $this->instances['viewRouter'] = new ViewRouter($this);
        }
        return $this->instances['viewRouter'];
    }

    /**
     * Return the dependency injection container
     * 
     * @return  \Opis\Colibri\Container
     */
    public function getContainer()
    {
        if (!isset($this->instances['container'])) {
            $container = $this->collect('Contracts');
            $container->setApplication($this);
            $this->instances['container'] = $container;
        }
        return $this->instances['container'];
    }

    /**
     * @return  \Opis\Colibri\Collectors\EventTarget
     */
    public function getCollector()
    {
        if (!isset($this->instances['collector'])) {
            $this->instances['collector'] = new Collector($this);
        }
        return $this->instances['collector'];
    }

    /**
     * @return  \Opis\Colibri\CollectorManager
     */
    public function getCollectorManager()
    {
        if (!isset($this->instances['collectorManager'])) {
            $this->instances['collectorManager'] = new CollectorManager($this);
        }
        return $this->instances['collectorManager'];
    }

    /**
     * @return  \Opis\Colibri\ModuleManager
     */
    public function getModuleManager()
    {
        if (!isset($this->instances['moduleManager'])) {
            $this->instances['moduleManager'] = new ModuleManager($this);
        }
        return $this->instances['moduleManager'];
    }

    /**
     * @return  \Opis\Utils\ClassLoader
     */
    public function getClassLoader()
    {
        if (!isset($this->instances['classLoader'])) {
            $this->instances['classLoader'] = new ClassLoader(array(), true);
        }
        return $this->instances['classLoader'];
    }

    /**
     * @return  \Opis\Colibri\Translator
     */
    public function getTranslator()
    {
        if (!isset($this->instances['translator'])) {
            $this->instances['translator'] = new Translator($this);
        }
        return $this->instances['translator'];
    }

    /**
     * 
     * @return  \Opis\Colibri\CSRFToken
     */
    public function getCSRFToken()
    {
        if (!isset($this->instances['csrf'])) {
            $this->instances['csrf'] = new CSRFToken($this);
        }
        return $this->instances['csrf'];
    }

    /**
     * Get a placeholder object
     * 
     * @return  \Opis\Utils\Placeholder
     */
    public function getPlaceholder()
    {
        if (!isset($this->instances['placeholder'])) {
            $this->instances['placeholder'] = new Placeholder();
        }
        return $this->instances['placeholder'];
    }

    /**
     * Collect items
     * 
     * @param   string  $entry  Item type
     * @param   bool    $fresh  (optional)
     * 
     * @return  mixed
     */
    public function collect($entry, $fresh = false)
    {
        $entry = strtolower($entry);

        if ($fresh) {
            unset($this->cache[$entry]);
        }

        if (!isset($this->cache[$entry])) {
            $self = $this;
            $hit = false;
            $this->cache[$entry] = $this->cache()->load($entry, function ($entry) use ($self, &$hit) {
                $self->includeCollectors();
                $hit = true;
                return $self->getCollector()->collect($entry)->data();
            });
            if ($hit) {
                $this->emit('system.collect.' . strtolower($entry));
            }
        }

        return $this->cache[$entry];
    }

    /**
     * Recollect all items
     * 
     * @param   bool    $fresh  (optional)
     * 
     * @retunr  boolean
     */
    public function recollect($fresh = true)
    {
        if ($this->cache()->clear()) {

            foreach (array_keys($this->config()->read('collectors')) as $entry) {
                $this->collect($entry, $fresh);
            }
            $this->emit('system.collect');
            return true;
        }
        return false;
    }

    /**
     * Bootstrap method
     * 
     * @param   Closure $callback   Custom bootstrap
     * 
     * @return  mixed|$this
     */
    public function bootstrap(Closure $callback = null)
    {
        if ($callback !== null) {
            return $callback($this);
        }

        $info = $this->info;
        $storagesPath = $info->storagesPath();
        $rootPath = $info->rootPath();

        if ($info->cliMode() &&
            file_exists($storagesPath . '/config') &&
            !is_writable($storagesPath . '/config')) {
            die('Try running command with sudo' . PHP_EOL);
        }

        if ($info->installMode()) {
            $enabled_modules = array();
            $composer_json = $rootPath . '/composer.json';
            $manager = $this->getModuleManager();

            if (file_exists($composer_json) &&
                null !== $composerContent = json_decode(file_get_contents($composer_json), true)) {
                foreach ($composerContent['extra']['installer-modules'] as $module) {
                    $enabled_modules[$module] = true;
                }
            }

            $filter = function (&$value) use ($enabled_modules) {
                return isset($enabled_modules[$value['name']]);
            };

            $modules = array_filter($this->getModuleManager()->findAll(), $filter);

            $this->config()->write('modules', array(
                'enabled' => $enabled_modules,
                'list' => $modules,
            ));

            $this->config()->write('collectors', $this->getCollectorManager()->getCollectors());

            return;
        }

        Model::setApplication($this);

        $file = $info->userAppFile();
        if (!file_exists($file)) {
            $file = $info->mainAppFile();
        }

        $class = $info->appClass();
        $this->getClassLoader()->mapClass($class, $file);

        if (!class_exists($class)) {
            throw new \RuntimeException("Unknown class '$class'");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isSubclassOf('\\Opis\\Colibri\\Bootstrap')) {
            throw new \RuntimeException("'$class' must inherit from '\\Opis\\Colibri\\Bootstrap'");
        }

        $instance = new $class();
        $instance->boot($this);

        return $this;
    }

    /**
     * Execute
     * 
     * @param   HttpRequest|null    $request
     * 
     * @return  mixed
     */
    public function run(HttpRequest $request = null)
    {
        if ($request === null) {
            $request = HttpRequest::fromGlobals();
        }

        $this->setHttpRequestObject($request);

        $path = new Path(
            $request->path(), $request->host(), $request->method(), $request->isSecure(), $request
        );

        return $this->getHttpRouter()->route($path);
    }

    /**
     * Returns an instance of the specified contract or class
     *
     * @param   string  $contract   Contract name or class name
     * @param   array   $arguments  (optional) Arguments that will be passed to the contract constructor
     *
     * @return  mixed
     */
    public function __invoke($contract, array $arguments = array())
    {
        return $this->getContainer()->make($contract, $arguments);
    }

    /**
     * Call a user defined method
     * 
     * @param   string  $name       Method's name
     * @param   array   $arguments  Method's arguments
     * 
     * @return  mixed
     * 
     * @throws \RuntimeException
     */
    public function __call($name, $arguments)
    {
        if (!isset($this->instances['methods'])) {
            $this->instances['methods'] = $this->collect('CoreMethods')->getList();
        }

        if (!isset($this->instances['methods'][$name])) {
            throw new \RuntimeException("Unknown method " . $name);
        }

        array_unshift($arguments, $this);

        return call_user_func_array($this->instances['methods'][$name], $arguments);
    }

    /**
     * Returns a caching storage
     * 
     * @param   string|null $storage    (optional) Storage name
     *    
     * @return  \Opis\Cache\Cache
     */
    public function cache($storage = null)
    {
        if ($storage === null) {
            if (!isset($this->instances['cache'])) {
                if (!isset($this->instances['cacheStorage'])) {
                    $this->instances['cacheStorage'] = new DefaultCacheStorage();
                }
                $this->instances['cache'] = new Cache($this->instances['cacheStorage']);
            }
            return $this->instances['cache'];
        }

        return $this->collect('CacheStorages')->get($this, $storage);
    }

    /**
     * Returns a session storage
     *
     * @param   string|null $storage    (optional) Storage name
     *
     * @return  \Opis\Session\Session
     */
    public function session($storage = null)
    {
        if ($storage === null) {
            if (!isset($this->instances['session'])) {
                if (!isset($this->instances['sessionStorage'])) {
                    $this->instances['sessionStorage'] = null;
                }
                $this->instances['session'] = new Session($this->instances['sessionStorage']);
            }
            return $this->instances['session'];
        }

        return $this->collect('SessionStorages')->get($this, $storage);
    }

    /**
     * Returns a config storage
     *
     * @param   string|null $storage    (optional) Storage name
     *
     * @return  \Opis\Config\Config
     */
    public function config($storage = null)
    {
        if ($storage === null) {
            if (!isset($this->instances['config'])) {
                if (!isset($this->instances['configStorage'])) {
                    $this->instances['configStorage'] = new DefaultConfigStorage();
                }
                $this->instances['config'] = new Config($this->instances['configStorage']);
            }
            return $this->instances['config'];
        }

        return $this->collect('ConfigStorages')->get($this, $storage);
    }

    /**
     * Returns a translate storage
     *
     * @return  \Opis\Config\Config
     */
    public function translations()
    {
        if (!isset($this->instances['translations'])) {
            if (!isset($this->instances['translateStorage'])) {
                $this->instances['translateStorage'] = new DefaultTranslateStorage();
            }
            $this->instances['translations'] = new Config($this->instances['translateStorage']);
        }
        return $this->instances['translations'];
    }

    /**
     * 
     * @return  \Opis\Colibri\Console
     */
    public function console()
    {
        if (!isset($this->instances['console'])) {
            $this->instances['console'] = new Console($this);
        }
        return $this->instances['console'];
    }

    /**
     * @throws  \RuntimeException
     * 
     * @return  \Opis\Database\Connection
     */
    public function connection()
    {
        if (!isset($this->instances['connection'])) {
            $this->instances['connection'] = $this->collect('Connections')->get();
            if (is_null($this->instances['connection'])) {
                throw new \RuntimeException("No database connection was defined");
            }
        }
        return $this->instances['connection'];
    }

    /**
     * Returns a database abstraction layer
     *
     * @param   string  $connection (optional) Connection name
     *
     * @return  \Opis\Database\Database
     */
    public function database($connection = null)
    {
        if ($connection === null) {
            if (!isset($this->instances['database'])) {
                $connection = $this->connection();
                $this->instances['database'] = new Database($connection);
            }
            return $this->instances['database'];
        }

        return $this->collect('Connections')->database($connection);
    }

    /**
     * Returns a database shema abstraction layer
     *
     * @param   string  $connection (optional) Connection name
     *
     * @return  \Opis\Database\Schema
     */
    public function schema($connection = null)
    {
        if ($connection === null) {
            if (!isset($this->instances['schema'])) {
                $this->instances['schema'] = $this->database()->schema();
            }
            return $this->instances['schema'];
        }

        return $this->database($connection)->schema();
    }

    /**
     * Returns a logger
     * 
     * @param   string|null $logger Logger's name
     * 
     * @return  \Psr\Log\LoggerInterface
     */
    public function log($logger = null)
    {
        if ($logger === null) {
            if (!isset($this->instances['logger'])) {
                $this->instances['logger'] = $this->collect('Loggers')->get($this);
            }
            return $this->instances['logger'];
        }
        return $this->collect('Loggers')->get($this, $logger);
    }

    /**
     * Return the underlying HTTP request object
     *
     * @return  \Opis\Http\Request
     */
    public function request()
    {
        if (!isset($this->instances['request'])) {
            $this->instances['request'] = HttpRequest::fromGlobals();
        }
        return $this->instances['request'];
    }

    /**
     * Return the underlying HTTP response object
     *
     * @return  \Opis\Http\Response
     */
    public function response()
    {
        if (!isset($this->instances['response'])) {
            $this->instances['response'] = $this->request()->response();
        }
        return $this->instances['response'];
    }

    /**
     * Redirects to a new locations
     *
     * @param   string  $location   The new location
     * @param   int     $code       Redirect status code
     * @param   array   $query      (optional)  Query arguments
     */
    public function redirect($location, $code = 302, array $query = array())
    {
        if (!empty($query)) {
            foreach ($query as $key => $value) {
                $query[$key] = $key . '=' . $value;
            }
            $location = rtrim($location) . '?' . implode('&', $query);
        }
        $this->response()->redirect($location, $code);
    }

    /**
     * Returns an instance of the specified contract or class
     *
     * @param   string  $contract   Contract name or class name
     * @param   array   $arguments  (optional) Arguments that will be passed to the contract constructor
     *
     * @return  mixed
     */
    public function make($contract, array $arguments = array())
    {
        return $this->getContainer()->make($contract, $arguments);
    }

    /**
     * Emit a new event
     *
     * @param   string  $name       Event name
     * @param   boolean $cancelable (optional) Cancelable flag
     *
     * @return  \Opis\Events\Event
     */
    public function emit($name, $cancelable = false)
    {
        return $this->dispatch(new Event($this, $name, $cancelable));
    }

    /**
     * Dispatch an event
     *
     * @param   \Opis\Events\Event $event  An event to be dispatched
     *
     * @return  \Opis\Events\Event The dispatched event
     */
    public function dispatch(Event $event)
    {
        if (!isset($this->instances['eventTarget'])) {
            $this->instances['eventTarget'] = new EventTarget($this->collect('EventHandlers'));
        }
        return $this->instances['eventTarget']->dispatch($event);
    }

    /**
     * Creates a new view
     *
     * @param   string  $name       View name
     * @param   array   $arguments  (optional) View's arguments
     *
     * @return  \Opis\Colibri\View
     */
    public function view($name, array $arguments = array())
    {
        return $this->getViewRouter()->view($name, $arguments);
    }

    /**
     * Renders a view
     *
     * @param   string|\Opis\View\ViewInterface $view   The view that will be rendered
     *
     * @return  string
     */
    public function render($view)
    {
        return $this->getViewRouter()->render($view);
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
    public function asset($module, $path, $full = false)
    {
        return $this->getURL('/assets/module/' . strtolower($module) . '/' . ltrim($path, '/'), $full);
    }

    /**
     * Module info
     * 
     * @param   string  $module
     * 
     * @return  \Opis\Colibri\ModuleInfo
     */
    public function module($module)
    {
        return new ModuleInfo($this, $module);
    }

    /**
     * Get the URI for a path
     *
     * @param   string  $path   The path
     * @param   boolean $full   (optional) Full URI flag
     *
     * @return  string
     */
    public function getURL($path, $full = false)
    {
        $req = $this->request();
        return $full ? $req->uriForPath($path) : $req->baseUrl() . $path;
    }

    /**
     * Creates an path from a named route
     *
     * @param   string  $route  Route name
     * @param   array   $args   (optional) Route wildecard's values
     *
     * @return  string
     */
    public function getPath($route, array $args = array())
    {
        $routes = $this->collect('Routes');
        if (!isset($routes[$route])) {
            return $route;
        }
        $route = $routes[$route];
        $args = $args + $route->getDefaults();
        return $route->getCompiler()->build($route->getPattern(), $args);
    }

    /**
     * Return a variable's value
     *
     * @param   string  $name       Variable's name
     * @param   mixed   $default    (optional) The value that will be returned if the variable doesn't exist
     *
     * @return  mixed
     */
    public function variable($name, $default = null)
    {
        return $this->collect('Variables')->get($name, $default);
    }

    /**
     * Translate a text
     *
     * @param   string  $sentence       The text that will be translated
     * @param   array   $placeholders   (optional) An array of placeholders
     * @param   string  $lang           (optional) Translation language
     *
     * @return  string  Translated text
     */
    public function t($sentence, $placeholders = array(), $lang = null)
    {
        return $this->getTranslator()->translate($sentence, $placeholders, $lang);
    }

    /**
     * Generates a CSRF token
     *
     * @return  string
     */
    public function csrfToken()
    {
        return $this->getCSRFToken()->generate();
    }

    /**
     * Validates a CSRF token
     *
     * @param   string  $token  Token
     *
     * @return  boolean
     */
    public function csrfValidate($token)
    {
        return $this->getCSRFToken()->validate($token);
    }

    /**
     * Crates a new validator
     * 
     * @return  \Opis\Colibri\Validator
     */
    public function validator()
    {
        return new Validator($this);
    }

    /**
     * Creates a new controller
     * 
     * @param   string  $class
     * @param   string  $method
     * @param   boolean $static (optional)
     * 
     * @return  \Opis\Colibri\Controller
     */
    public function controller($class, $method, $static = false)
    {
        if (strpos($class, ':') !== false) {
            $info = explode(':', $class);
            $class = $this->resolveClass($info[0], $info[1]);
        }

        return new Controller($class, $method, $static);
    }

    /**
     * Resolve a class
     * 
     * @param   string  $module
     * @param   string  $class
     * 
     * @return  string
     */
    public function resolveClass($module, $class)
    {
        return $this->module($module)->nspace() . '\\' . trim($class, '\\');
    }

    /**
     * Replace placeholders
     * 
     * @param   string  $text
     * @param   array   $placeholders
     * 
     * @return  string
     */
    public function replace($text, array $placeholders)
    {
        return $this->getPlaceholder()->replace($text, $placeholders);
    }

    /**
     * Page not found
     * 
     * @return  \Opis\HttpRouting\HttpError
     */
    public function pageNotFound()
    {
        return HttpError::pageNotFound();
    }

    /**
     * Access denied
     * 
     * @return  \Opis\HttpRouting\HttpError
     */
    public function accessDenied()
    {
        return HttpError::accessDenied();
    }

    /**
     * Generic http error
     * 
     * @param   int Error code
     * 
     * @return  \Opis\HttpRouting\HttpError
     */
    public function httpError($code)
    {
        return new HttpError($code);
    }
}
