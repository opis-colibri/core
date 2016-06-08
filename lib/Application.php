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
use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackage;
use Doctrine\Common\Annotations\AnnotationReader;
use Opis\Cache\Cache;
use Opis\Cache\Storage\Memory as DefaultCacheStorage;
use Opis\Cache\StorageInterface as CacheStorageInterface;
use Opis\Colibri\Annotations\Collector as CollectorAnnotation;
use Opis\Colibri\Composer\CLI;
use Opis\Config\Config;
use Opis\Config\Storage\Memory as DefaultConfigStorage;
use Opis\Config\Storage\Memory as DefaultTranslateStorage;
use Opis\Config\StorageInterface as ConfigStorageInterface;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Events\EventTarget;
use Opis\Http\Request as HttpRequest;
use Opis\HttpRouting\HttpError;
use Opis\HttpRouting\Path;
use Opis\Session\Session;
use Opis\Utils\Placeholder;
use Opis\View\ViewableInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use SessionHandlerInterface;

class Application
{
    /** @var    array */
    protected $cache = array();

    /** @var    array */
    protected $instances = array();

    /** @var    array */
    protected $collectors = array();

    /** @var    Env */
    protected $env;

    /** @var    AppInfo */
    protected $info;

    /** @var    Composer */
    protected $composer;

    /** @var    CLI */
    protected $composerCLI;

    /** @var ClassLoader */
    protected $classLoader;

    /** @var    array|null */
    protected $packages;

    /** @var    array|null */
    protected $modules;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /**
     * Constructor
     *
     * @param  AppInfo $info Application info
     * @param ClassLoader $loader
     * @param Composer $composer (optional)
     */
    public function __construct(AppInfo $info, ClassLoader $loader, Composer $composer = null)
    {
        $this->info = $info;
        $this->composer = $composer;
        $this->classLoader = $loader;
        $this->info->setApplication($this);
    }

    /**
     * Get Composer instance
     *
     * @return  Composer
     */
    public function getComposer()
    {
        if ($this->composer === null) {
            $io = new NullIO();
            $config = Factory::createConfig($io, $this->info->rootDir());
            $this->composer = Factory::create($io, $config->raw());
        }

        return $this->composer;
    }

    /**
     * Get Composer CLI
     *
     * @return  CLI
     */
    public function getComposerCLI()
    {
        if ($this->composerCLI === null) {
            $this->composerCLI = new CLI($this);
        }

        return $this->composerCLI;
    }

    /**
     * @return  ClassLoader
     */
    public function getClassLoader()
    {
        return $this->classLoader;
    }

    /**
     * Get module packs
     *
     * @param   bool $clear (optional)
     *
     * @return  CompletePackage[]
     */
    public function getPackages($clear = false)
    {
        if (!$clear && $this->packages !== null) {
            return $this->packages;
        }

        $packages = array();
        $composer = $this->getComposer();
        $repository = $composer->getRepositoryManager()->getLocalRepository();

        foreach ($repository->getPackages() as $package) {
            if (!$package instanceof CompletePackage || $package->getType() !== 'opis-colibri-module') {
                continue;
            }
            $packages[$package->getName()] = $package;
        }

        return $this->packages = $packages;
    }

    /**
     * Get a list with available modules
     *
     * @param   bool $clear (optional)
     *
     * @return  Module[]
     */
    public function getModules($clear = false)
    {
        if (!$clear && $this->modules !== null) {
            return $this->modules;
        }

        $modules = array();

        foreach ($this->getPackages($clear) as $module => $package) {
            $modules[$module] = new Module($this, $module, $package);
        }

        return $this->modules = $modules;
    }

    /**
     * Install a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function install(Module $module, $recollect = true)
    {
        if (!$module->canBeInstalled()) {
            return false;
        }

        $config = $this->config();
        $modules = $config->read('app.modules.installed', array());
        $modules[] = $module->name();
        $config->write('app.modules.installed', $modules);

        $this->executeInstallerAction($module, 'install');

        if ($recollect) {
            $this->recollect();
        }

        $this->emit('module.installed.' . $module->name());

        return true;
    }

    /**
     * Uninstall a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function uninstall(Module $module, $recollect = true)
    {
        if (!$module->canBeUninstalled()) {
            return false;
        }

        $config = $this->config();
        $modules = $config->read('app.modules.installed', array());
        $config->write('app.modules.installed', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'uninstall');

        if ($recollect) {
            $this->recollect();
        }

        $this->emit('module.uninstalled.' . $module->name());

        return true;
    }

    /**
     * Enable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function enable(Module $module, $recollect = true)
    {
        if (!$module->canBeEnabled()) {
            return false;
        }

        $config = $this->config();
        $modules = $config->read('app.modules.enabled', array());
        $modules[] = $module->name();
        $config->write('app.modules.enabled', $modules);

        $this->executeInstallerAction($module, 'enable');

        if ($recollect) {
            $this->recollect();
        }

        $this->emit('module.enabled.' . $module->name());

        return true;
    }

    /**
     * Disable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function disable(Module $module, $recollect = true)
    {
        if (!$module->canBeDisabled()) {
            return false;
        }

        $config = $this->config();
        $modules = $config->read('app.modules.enabled', array());
        $config->write('app.modules.enabled', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'disable');

        if ($recollect) {
            $this->recollect();
        }

        $this->emit('module.disabled.' . $module->name());

        return true;
    }

    /**
     * Get environment
     *
     * @return  Env
     */
    public function getEnv()
    {
        if ($this->env === null) {
            $this->env = new Env($this);
        }

        return $this->env;
    }

    /**
     * Get information about this application
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
     * @param   Connection $connection
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
     * @param   \Opis\Cache\StorageInterface $storage
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
     * @param   \Opis\Config\StorageInterface $storage
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
     * @param   SessionHandlerInterface $storage
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
     * @param   \Opis\Config\StorageInterface $storage
     *
     * @return  $this
     */
    public function setDefaultTranslateStorage(ConfigStorageInterface $storage)
    {
        $this->instances['translateStorage'] = $storage;
        return $this;
    }

    /**
     * Set the default logger
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
     * @param   HttpRequest|null $request (optional)
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
     * @return  HttpRouter
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
     * @return  ViewRouter
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
     * @return  Collector
     */
    public function getCollector()
    {
        if (!isset($this->instances['collector'])) {
            $this->instances['collector'] = new Collector($this);
        }
        return $this->instances['collector'];
    }

    /**
     * @return  CollectorManager
     */
    public function getCollectorManager()
    {
        if (!isset($this->instances['collectorManager'])) {
            $this->instances['collectorManager'] = new CollectorManager($this);
        }
        return $this->instances['collectorManager'];
    }

    /**
     * @return  Translator
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
     * @return  CSRFToken
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
     * @return  Placeholder
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
     * @param   string $entry Item type
     * @param   bool $fresh (optional)
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
     * @param bool $fresh (optional)
     *
     * @return boolean
     */
    public function recollect($fresh = true)
    {
        if (!$this->cache()->clear()) {
            return false;
        }
        $this->collectorsIncluded = false;

        foreach (array_keys($this->config()->read('app.collectors')) as $entry) {
            $this->collect($entry, $fresh);
        }

        $this->emit('system.collect');
        return true;
    }

    /**
     * Bootstrap method
     *
     * @param   Closure $callback Custom bootstrap
     *
     * @return  $this
     */
    public function bootstrap(Closure $callback = null)
    {
        if ($callback !== null) {
            $callback($this);
            return $this;
        }

        return $this;
    }

    /**
     * Execute
     *
     * @param   HttpRequest|null $request
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
     * @param   string $contract Contract name or class name
     * @param   array $arguments (optional) Arguments that will be passed to the contract constructor
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
     * @param   string $name Method's name
     * @param   array $arguments Method's arguments
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
     * @param   string|null $storage (optional) Storage name
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
     * @param   string|null $storage (optional) Storage name
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
     * @param   string|null $storage (optional) Storage name
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
     * @param   string $connection (optional) Connection name
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
     * Returns a database schema abstraction layer
     *
     * @param   string $connection (optional) Connection name
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
     * @param   string $location The new location
     * @param   int $code Redirect status code
     * @param   array $query (optional)  Query arguments
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
     * @param   string $contract Contract name or class name
     * @param   array $arguments (optional) Arguments that will be passed to the contract constructor
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
     * @param   string $name Event name
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
     * @param   Event $event An event to be dispatched
     *
     * @return  Event The dispatched event
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
     * @param   string $name View name
     * @param   array $arguments (optional) View's arguments
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
     * @param   string|ViewableInterface $view The view that will be rendered
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
     * @param   string $module Module name
     * @param   string $path Module's resource relative path
     * @param   boolean $full Full path flag
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
     * @param   string $module
     *
     * @return  \Opis\Colibri\Module
     */
    public function module($module)
    {
        return new Module($this, $module);
    }

    /**
     * Get the URI for a path
     *
     * @param   string $path The path
     * @param   boolean $full (optional) Full URI flag
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
     * @param   string $route Route name
     * @param   array $args (optional) Route wildcard's values
     *
     * @return  string
     */
    public function getPath($route, array $args = array())
    {
        /** @var HttpRouteCollection $routes */
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
     * @param   string $name Variable's name
     * @param   mixed $default (optional) The value that will be returned if the variable doesn't exist
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
     * @param   string $sentence The text that will be translated
     * @param   array $placeholders (optional) An array of placeholders
     * @param   string $lang (optional) Translation language
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
     * @param   string $token Token
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
     * @param   string $class
     * @param   string $method
     * @param   boolean $static (optional)
     *
     * @return  Controller
     */
    public function controller($class, $method, $static = false)
    {
        return new Controller($class, $method, $static);
    }

    /**
     * Replace placeholders
     *
     * @param   string $text
     * @param   array $placeholders
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
     * @param   int $code Error code
     *
     * @return  HttpError
     */
    public function httpError($code)
    {
        return new HttpError($code);
    }

    /**
     * Execute an action
     *
     * @param Module $module
     * @param string $action
     */
    protected function executeInstallerAction(Module $module, $action)
    {
        $this->getComposerCLI()->dumpAutoload();
        $this->getClassLoader()->unregister();
        $this->classLoader = require $this->info->vendorDir() . '/autoload.php';

        if (null !== $installer = $module->installer()) {
            $this->make($installer)->{$action}($this);
        }
    }

    /**
     * Include modules
     */
    protected function includeCollectors()
    {
        if ($this->collectorsIncluded) {
            return;
        }

        $this->collectorsIncluded = true;
        $reader = new AnnotationReader();

        foreach ($this->getModules() as $module) {

            if (isset($this->collectors[$module->name()]) || !$module->isEnabled()) {
                continue;
            }

            $this->collectors[$module->name()] = true;

            if ($module->collector() === null) {
                continue;
            }

            $instance = $this->make($module->collector());

            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf('\\Opis\\Colibri\\ModuleCollector')) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

                $name = $method->getShortName();

                if (substr($name, 0, 2) === '__') {
                    if ($name === '__invoke') {
                        $instance($this, $reader);
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

                $callback = function ($collector, $app) use ($instance, $name) {
                    $instance->{$name}($collector, $app);
                };

                $this->getCollector()->handle(strtolower($annotation->name), $callback, $annotation->priority);
            }
        }
    }
}
