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
use Opis\Colibri\Components\ContractTrait;
use Opis\Colibri\Components\EventTrait;
use Opis\Colibri\Composer\CLI;
use Opis\Colibri\Routing\HttpRouter;
use Opis\Colibri\Routing\ViewApp;
use Opis\Config\Config;
use Opis\Config\Storage\Memory as EphemeralConfigStorage;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Database\ORM;
use Opis\Database\Schema;
use Opis\Events\EventTarget;
use Opis\Http\Request as HttpRequest;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\HttpRouting\Path;
use Opis\Session\Session;
use Opis\Utils\Dir;
use Opis\Validation\Placeholder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use SessionHandlerInterface;

class Application
{
    use ContractTrait, EventTrait;

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

    /** @var  Connection[] */
    protected $connection = array();

    /** @var  Database[] */
    protected $database = array();

    /** @var  ORM[] */
    protected $orm = array();

    /** @var  Session */
    protected $session = array();

    /** @var  Config */
    protected $translations;

    /** @var  HttpRouter */
    protected $httpRouter;

    /** @var  ViewApp */
    protected $viewApp;

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

    /** @var  Validator */
    protected $validator;

    /** @var  AppHelper */
    protected $helper;

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
     * @return Application
     */
    protected function getApp(): Application
    {
        return $this;
    }

    /**
     * Get Composer instance
     *
     * @return  Composer
     */
    public function getComposer(): Composer
    {
        if ($this->composer === null) {
            $composerFile = $this->info->composerFile();
            $cwd = $this->info->rootDir();
            $this->composer = (new Factory())->createComposer(new NullIO(), $composerFile, false, $cwd);
         }

        return $this->composer;
    }

    /**
     * Get Composer CLI
     *
     * @return  CLI
     */
    public function getComposerCLI(): CLI
    {
        if ($this->composerCLI === null) {
            $this->composerCLI = new CLI($this);
        }

        return $this->composerCLI;
    }

    /**
     * @return  ClassLoader
     */
    public function getClassLoader(): ClassLoader
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
    public function getPackages(bool $clear = false): array
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
    public function getModules(bool $clear = false): array
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
     * Get environment
     *
     * @return  Env
     */
    public function getEnv(): Env
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
    public function getHttpRouter(): HttpRouter
    {
        if ($this->httpRouter === null) {
            $this->httpRouter = new HttpRouter($this);
        }
        return $this->httpRouter;
    }

    /**
     * Get the View router
     *
     * @return  ViewApp
     */
    public function getViewApp(): ViewApp
    {
        if ($this->viewApp === null) {
            $this->viewApp = new ViewApp($this);
        }
        return $this->viewApp;
    }

    /**
     * Return the dependency injection container
     *
     * @return  Container
     */
    public function getContainer(): Container
    {
        if ($this->containerInstance === null) {
            $container = $this->getCollector()->getContracts();
            $container->setApplication($this);
            $this->containerInstance = $container;
        }
        return $this->containerInstance;
    }

    /**
     * @return  Translator
     */
    public function getTranslator(): Translator
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
    public function getCSRFToken(): CSRFToken
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
    public function getPlaceholder(): Placeholder
    {
        if ($this->placeholderInstance === null) {
            $this->placeholderInstance = new Placeholder();
        }

        return $this->placeholderInstance;
    }

    /**
     * Returns validator instance
     *
     * @return  Validator
     */
    public function getValidator(): Validator
    {
        if ($this->validator === null){
            $this->validator = new Validator($this);
        }

        return $this->validator;
    }

    /**
     * Returns a caching storage
     *
     * @param   string|null $storage (optional) Storage name
     *
     * @return  Cache
     */
    public function getCache(string $storage = null): Cache
    {
        if ($storage === null && false === $storage = getenv(Env::CACHE_STORAGE)) {
            $this->cache[$storage = ''] = new Cache(new EphemeralCacheStorage());
        }

        if (!isset($this->cache[$storage])) {
            $this->cache[$storage] = new Cache($this->getCollector()->getCacheStorage($storage));
        }

        return $this->cache[$storage];
    }

    /**
     * Returns a session storage
     *
     * @param   string|null $storage (optional) Storage name
     *
     * @return  Session
     */
    public function getSession(string $storage = null): Session
    {
        if ($storage === null && false === $storage = getenv(Env::SESSION_STORAGE)) {
            $this->session[$storage = ''] = new Session();
        }

        if (!isset($this->config[$storage])) {
            $this->session[$storage] = new Session($this->getCollector()->getSessionStorage($storage));
        }

        return $this->session[$storage];
    }

    /**
     * Returns a config storage
     *
     * @param   string|null $storage (optional) Storage name
     *
     * @return  Config
     */
    public function getConfig(string $storage = null): Config
    {
        if ($storage === null && false === $storage = getenv(Env::CONFIG_STORAGE)) {
            $this->config[$storage = ''] = new Config(new EphemeralConfigStorage());
        }

        if (!isset($this->config[$storage])) {
            $this->config[$storage] = new Config($this->getCollector()->getConfigStorage($storage));
        }

        return $this->config[$storage];
    }

    /**
     * Returns a translation storage
     *
     * @return  Config
     */
    public function getTranslations(): Config
    {
        if ($this->translations === null) {
            if (false === $storage = getenv(Env::TRANSLATIONS_STORAGE)) {
                $storage = null;
            }
            $this->translations = $this->getConfig($storage);
        }

        return $this->translations;
    }

    /**
     *
     * @return  Console
     */
    public function getConsole(): Console
    {
        if ($this->consoleInstance === null) {
            $this->consoleInstance = new Console($this);
        }
        $this->consoleInstance;
    }

    /**
     * @param string|null $name
     * @throws  \RuntimeException
     * @return  Connection
     */
    public function getConnection(string $name = null): Connection
    {
        if ($name === null && false === $name = getenv(Env::DB_CONNECTION)) {
            throw new \RuntimeException("No database connection defined");
        }

        if (!isset($this->connection[$name])) {
            $this->connection[$name] = $this->getCollector()->getConnection($name);
        }

        return $this->connection[$name];
    }

    /**
     * Returns a database abstraction layer
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  Database
     */
    public function getDatabase(string $connection = null): Database
    {
        if (!isset($this->database[$connection])) {
            $this->database[$connection] = $this->getCollector()->getDatabase($connection);
        }

        return $this->database[$connection];
    }

    /**
     * Returns a database schema abstraction layer
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  Schema
     */
    public function getSchema(string $connection = null): Schema
    {
        return $this->getDatabase($connection)->schema();
    }

    /**
     * Returns an ORM
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  ORM
     */
    public function getORM(string $connection = null): ORM
    {
        if(!isset($this->orm[$connection])){
            $this->orm[$connection] = new ORM($this->getConnection($connection));
        }
        return $this->orm[$connection];
    }

    /**
     * Returns a logger
     *
     * @param   string|null $logger Logger's name
     *
     * @return  LoggerInterface
     */
    public function getLog(string $logger = null): LoggerInterface
    {
        if ($logger === null && false === $logger = getenv(Env::LOGGER_STORAGE)) {
            $this->loggers[''] = new NullLogger();
        }

        if (!isset($this->loggers[$logger])) {
            $this->loggers[$logger] = $this->getCollector()->getLogger($logger);
        }

        return $this->cache[$logger];
    }

    /**
     * Return the underlying HTTP request object
     *
     * @return  Request
     */
    public function getHttpRequest(): Request
    {
        if ($this->httpRequestInstance === null) {
            $this->httpRequestInstance = HttpRequest::fromGlobals();
        }

        return $this->httpRequestInstance;
    }

    /**
     * Return the underlying HTTP response object
     *
     * @return  Response
     */
    public function getHttpResponse(): Response
    {
        if ($this->httpResponseInstance === null) {
            $this->httpResponseInstance = $this->getHttpRequest()->response();
        }

        return $this->httpResponseInstance;
    }

    /**
     * Get variables list
     *
     * @return array
     */
    public function getVariables(): array
    {
        if($this->variables === null){
            $this->variables = $this->getCollector()->getVariables();
        }
        return $this->variables;
    }

    /**
     * @return EventTarget
     */
    public function getEventTarget(): EventTarget
    {
        if($this->eventTarget === null){
            $this->eventTarget = new EventTarget($this->getCollector()->getEventHandlers());
        }
        return $this->eventTarget;
    }

    /**
     * Get information about this application
     *
     * @return  AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->info;
    }

    /**
     * Get collector
     *
     * @return CollectorManager
     */
    public function getCollector(): CollectorManager
    {
        if ($this->collector === null) {
            $this->collector = new CollectorManager($this);
        }
        return $this->collector;
    }

    /**
     * @return AppHelper
     */
    public function getHelper(): AppHelper
    {
        if ($this->helper === null){
            $this->helper = new AppHelper($this);
        }
        return $this->helper;
    }

    /**
     * Bootstrap method
     * @return $this|Application
     */
    public function bootstrap(): self
    {
        if(!is_writable($this->info->vendorDir())){
            throw new \RuntimeException('Vendor dir must be writable: ' . $this->info->vendorDir());
        }

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

        $this->getConfig()->write('modules.installed', $enabled);
        $this->getConfig()->write('modules.enabled', $enabled);

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
     * Install a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     */
    public function install(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeInstalled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.installed', array());
        $modules[] = $module->name();
        $config->write('modules.installed', $modules);

        $this->executeInstallerAction($module, 'install');

        if ($recollect) {
            $this->getCollector()->recollect();
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
    public function uninstall(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeUninstalled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.installed', array());
        $config->write('modules.installed', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'uninstall');

        if ($recollect) {
            $this->getCollector()->recollect();
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
    public function enable(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeEnabled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('app.modules.enabled', array());
        $modules[] = $module->name();
        $config->write('modules.enabled', $modules);

        $this->executeInstallerAction($module, 'enable');
        $this->registerAssets($module);

        if ($recollect) {
            $this->getCollector()->recollect();
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
    public function disable(Module $module, bool $recollect = true): bool
    {
        if (!$module->canBeDisabled()) {
            return false;
        }

        $config = $this->getConfig();
        $modules = $config->read('modules.enabled', array());
        $config->write('modules.enabled', array_diff($modules, array($module->name())));

        $this->executeInstallerAction($module, 'disable');
        $this->unregisterAssets($module);

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.disabled.' . $module->name());

        return true;
    }

    /**
     * Execute an action
     *
     * @param Module $module
     * @param string $action
     */
    protected function executeInstallerAction(Module $module, string $action)
    {
        if (!$this->info->installMode()) {
            $this->getComposerCLI()->dumpAutoload();
            $this->getClassLoader()->unregister();
            $this->classLoader = require $this->info->vendorDir() . '/autoload.php';
        }

        if (false !== $installer = $module->installer()) {
            $this->make($installer)->{$action}($this);
        }
    }

    /**
     * @param Module $module
     * @return bool
     */
    protected function registerAssets(Module $module): bool
    {
        if (false === $assets = $module->assets()){
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
    protected function unregisterAssets(Module $module): bool 
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
