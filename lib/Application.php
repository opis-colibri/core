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

use Composer\Autoload\ClassLoader;
use Composer\Composer;
use Composer\Factory;
use Composer\IO\NullIO;
use Composer\Package\CompletePackage;
use Opis\Cache\Cache;
use Opis\Cache\Storage\Memory as EphemeralCacheStorage;
use Opis\Colibri\Composer\CLI;
use Opis\Config\Config;
use Opis\Config\Storage\Memory as EphemeralConfigStorage;
use Opis\Container\Container;
use Opis\Events\EventTarget;
use Opis\Http\Request as HttpRequest;
use Opis\HttpRouting\HttpError;
use Opis\HttpRouting\Path;
use Opis\Session\Session;
use Opis\Utils\Dir;
use Opis\Utils\Placeholder;
use Opis\View\ViewableInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;

class Application
{
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

    /** @var  CollectorManager */
    protected $collector;

    /** @var  Container */
    protected $containerInstance;

    /** @var  Translator */
    protected $translatorInstance;

    /** @var  CSRFToken */
    protected $csrfTokenInstance;

    /** @var  Placeholder */
    protected $placeholderInstance;

    /** @var  HttpRequest */
    protected $httpRequestInstance;

    /** @var  \Opis\Http\Response */
    protected $httpResponseInstance;

    /** @var  \Opis\Cache\Cache[] */
    protected $cache = array();

    /** @var  Config */
    protected $config = array();

    /** @var  \Opis\Database\Connection[] */
    protected $connection = array();

    /** @var  \Opis\Database\Database[] */
    protected $database = array();

    /** @var  Session */
    protected $session = array();

    /** @var  Config */
    protected $translations;

    /** @var  HttpRouter */
    protected $httpRouter;

    /** @var  ViewRouter */
    protected $viewRouter;

    /** @var  callable[] */
    protected $coreMethods;

    /** @var  Console */
    protected $consoleInstance;

    /** @var \Psr\Log\LoggerInterface[] */
    protected $loggers = array();

    /** @var  EventTarget */
    protected $eventTarget;

    /** @var  array */
    protected $variables;

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
            $this->composer = Factory::create(new NullIO(), $this->info->composerFile());
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

        foreach ($repository->getCanonicalPackages() as $package) {
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
        $modules = $config->read('modules.installed', array());
        $modules[] = $module->name();
        $config->write('modules.installed', $modules);

        $this->executeInstallerAction($module, 'install');

        if ($recollect) {
            $this->collector()->recollect();
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
        $modules = $config->read('modules.installed', array());
        $config->write('modules.installed', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'uninstall');

        if ($recollect) {
            $this->collector()->recollect();
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
        $config->write('modules.enabled', $modules);

        $this->executeInstallerAction($module, 'enable');
        $this->registerAssets($module);

        if ($recollect) {
            $this->collector()->recollect();
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
        $modules = $config->read('modules.enabled', array());
        $config->write('modules.enabled', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'disable');
        $this->unregisterAssets($module);

        if ($recollect) {
            $this->collector()->recollect();
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
     * Get the HTTP router
     *
     * @return  HttpRouter
     */
    public function getHttpRouter()
    {
        if ($this->httpRouter === null) {
            $this->httpRouter = new HttpRouter($this);
        }
        return $this->httpRouter;
    }

    /**
     * Get the View router
     *
     * @return  ViewRouter
     */
    public function getViewRouter()
    {
        if ($this->viewRouter === null) {
            $this->viewRouter = new ViewRouter($this);
        }
        return $this->viewRouter;
    }

    /**
     * Return the dependency injection container
     *
     * @return  Container
     */
    public function getContainer()
    {
        if ($this->containerInstance === null) {
            $container = $this->collector()->getContracts();
            $container->setApplication($this);
            $this->containerInstance = $container;
        }
        return $this->containerInstance;
    }

    /**
     * @return  Translator
     */
    public function getTranslator()
    {
        if ($this->translatorInstance === null) {
            $this->translatorInstance = new Translator($this);
        }
        return $this->translatorInstance;
    }

    /**
     *
     * @return  CSRFToken
     */
    public function getCSRFToken()
    {
        if ($this->csrfTokenInstance === null) {
            $this->csrfTokenInstance = new CSRFToken($this);
        }

        return $this->csrfTokenInstance;
    }

    /**
     * Get a placeholder object
     *
     * @return  Placeholder
     */
    public function getPlaceholder()
    {
        if ($this->placeholderInstance === null) {
            $this->placeholderInstance = new Placeholder();
        }

        return $this->placeholderInstance;
    }


    /**
     * Bootstrap method
     *
     * @return  $this
     */
    public function bootstrap()
    {
        if (!$this->info->installMode()) {
            $this->emit('system.init');
            return $this;
        }


        $composer = $this->getComposer();
        $generator = $composer->getAutoloadGenerator();
        $extra = $composer->getPackage()->getExtra();
        $enabled = array();
        $canonicalPacks = array();
        $modules = array();
        $installer = null;

        if (!isset($extra['installer-modules']) || !is_array($extra['installer-modules'])) {
            $extra['installer-modules'] = array();
        }

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {

            if ($package->getType() !== 'opis-colibri-module') {
                $canonicalPacks[] = $package;
                continue;
            }

            $modules[$package->getName()] = $package;
            $extra = $package->getExtra();

            if(isset($extra['opis-colibri-installer']) && $extra['opis-colibri-installer']){
                $installer = $package;
            }
        }

        if ($installer === null) {
            throw new \RuntimeException("No installer found");
        }

        $canonicalPacks[] = $installer;

        foreach ($installer->getRequires() as $require){
            $target = $require->getTarget();
            if (isset($modules[$target])) {
                $canonicalPacks[] = $modules[$target];
            }
        }

        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $canonicalPacks);
        $autoload = $generator->parseAutoloads($packMap, $composer->getPackage());
        $loader = $generator->createLoader($autoload);

        $this->classLoader->unregister();
        $this->classLoader = $loader;
        $this->classLoader->register();

        $this->config()->write('modules.installed', $enabled);
        $this->config()->write('modules.enabled', $enabled);

        $this->emit('system.init');
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

        $this->httpRequestInstance = $request;

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
        if ($this->coreMethods === null) {
            $this->coreMethods = $this->collector()->getCoreMethods();
        }

        if (!isset($this->coreMethods[$name])) {
            throw new \RuntimeException("Unknown core method `$name`");
        }

        $arguments[] = $this;

        return call_user_func_array($this->coreMethods[$name], $arguments);
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
     * Get collector
     *
     * @return CollectorManager
     */
    public function collector()
    {
        if ($this->collector === null) {
            $this->collector = new CollectorManager($this);
        }

        return $this->collector;
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
        if ($storage === null && false === $storage = getenv(Env::CACHE_STORAGE)) {
            $this->cache[$storage = ''] = new Cache(new EphemeralCacheStorage());
        }

        if (!isset($this->cache[$storage])) {
            $this->cache[$storage] = new Cache($this->collector()->getCacheStorage($storage));
        }

        return $this->cache[$storage];
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
        if ($storage === null && false === $storage = getenv(Env::SESSION_STORAGE)) {
            $this->session[$storage = ''] = new Session();
        }

        if (!isset($this->config[$storage])) {
            $this->session[$storage] = new Session($this->collector()->getSessionStorage($storage));
        }

        return $this->session[$storage];
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
        if ($storage === null && false === $storage = getenv(Env::CONFIG_STORAGE)) {
            $this->config[$storage = ''] = new Config(new EphemeralConfigStorage());
        }

        if (!isset($this->config[$storage])) {
            $this->config[$storage] = new Config($this->collector()->getConfigStorage($storage));
        }

        return $this->config[$storage];
    }

    /**
     * Returns a translation storage
     *
     * @return  \Opis\Config\Config
     */
    public function translations()
    {
        if ($this->translations === null) {
            if (false === $storage = getenv(Env::TRANSLATIONS_STORAGE)) {
                $storage = null;
            }
            $this->translations = $this->config($storage);
        }

        return $this->translations;
    }

    /**
     *
     * @return  \Opis\Colibri\Console
     */
    public function console()
    {
        if ($this->consoleInstance === null) {
            $this->consoleInstance = new Console($this);
        }
        $this->consoleInstance;
    }

    /**
     * @param string|null $name
     * @throws  \RuntimeException
     * @return  \Opis\Database\Connection
     */
    public function connection($name = null)
    {
        if ($name === null && false === $name = getenv(Env::DB_CONNECTION)) {
            throw new \RuntimeException("No database connection defined");
        }

        if (!isset($this->connection[$name])) {
            $this->connection[$name] = $this->collector()->getConnection($name);
        }

        return $this->connection[$name];
    }

    /**
     * Returns a database abstraction layer
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  \Opis\Database\Database
     */
    public function database($connection = null)
    {
        if (!isset($this->database[$connection])) {
            $this->database[$connection] = $this->collector()->getDatabase($connection);
        }

        return $this->database[$connection];
    }

    /**
     * Returns a database schema abstraction layer
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  \Opis\Database\Schema
     */
    public function schema($connection = null)
    {
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
        if ($logger === null && false === $logger = getenv(Env::LOGGER_STORAGE)) {
            $this->loggers[''] = new NullLogger();
        }

        if (!isset($this->loggers[$logger])) {
            $this->loggers[$logger] = $this->collector()->getLogger($logger);
        }

        return $this->cache[$logger];
    }

    /**
     * Return the underlying HTTP request object
     *
     * @return  \Opis\Http\Request
     */
    public function request()
    {
        if ($this->httpRequestInstance === null) {
            $this->httpRequestInstance = HttpRequest::fromGlobals();
        }

        return $this->httpRequestInstance;
    }

    /**
     * Return the underlying HTTP response object
     *
     * @return  \Opis\Http\Response
     */
    public function response()
    {
        if ($this->httpResponseInstance === null) {
            $this->httpResponseInstance = $this->request()->response();
        }

        return $this->httpResponseInstance;
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
        if ($this->eventTarget === null) {
            $this->eventTarget = new EventTarget($this->collector()->getEventHandlers());
        }
        return $this->eventTarget->dispatch($event);
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

        $routes = $this->collector()->getRoutes();

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
        if ($this->variables === null) {
            $this->variables = $this->collector()->getVariables();
        }
        return array_key_exists($name, $this->variables) ? $this->variables[$name] : $default;
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
        if (!$this->info->installMode()) {
            $this->getComposerCLI()->dumpAutoload();
            $this->getClassLoader()->unregister();
            $this->classLoader = require $this->info->vendorDir() . '/autoload.php';
        }

        if (null !== $installer = $module->installer()) {
            $this->make($installer)->{$action}($this);
        }
    }

    /**
     * @param Module $module
     * @return bool
     */
    protected function registerAssets(Module $module)
    {
        if (null === $assets = $module->assets()){
            return false;
        }

        list($dirname, $target) = explode('/', $module->name());
        $dirpath = $this->info->assetsDir() . '/' . $dirname;

        if(!file_exists($dirpath) || !is_dir($dirpath)){
            mkdir($dirpath, 0775);
        }

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            return Dir::copy($assets, $dirpath . '/' . $target);
        }

        return symlink($assets, $dirpath . '/' . $target);
    }

    /**
     * @param Module $module
     * @return bool
     */
    protected function unregisterAssets(Module $module)
    {
        $path = $this->info->assetsDir() . '/' . $module->name();

        if (!file_exists($path)){
            return false;
        }

        if (is_link($path)){
            return unlink($path);
        }

        return Dir::remove($path);
    }

}
