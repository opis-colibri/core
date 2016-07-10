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
use Opis\Cache\StorageInterface as CacheStorageInterface;
use Opis\Colibri\Components\ContractTrait;
use Opis\Colibri\Components\EventTrait;
use Opis\Colibri\Composer\CLI;
use Opis\Colibri\Routing\HttpRouter;
use Opis\Colibri\Routing\ViewApp;
use Opis\Config\Config;
use Opis\Config\Storage\Memory as EphemeralConfigStorage;
use Opis\Config\StorageInterface as ConfigStorageInterface;
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

class Application implements DefaultCollectorInterface
{
    use ContractTrait, EventTrait;

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

    /** @var array  */
    protected $implicit = [];

    /** @var  array */
    protected $specials;

    /** @var  ViewHelper */
    protected $viewHelper;

    /** @var  array|null */
    protected $collectorList;

    /**
     * Constructor
     *
     * @param ClassLoader $loader
     * @param Composer $composer (optional)
     */
    public function __construct(string $rootDir, ClassLoader $loader, Composer $composer = null)
    {
        if($composer === null){
            $composer = (new Factory())->createComposer(new NullIO(), $rootDir . '/composer.json', false, $rootDir);
        }

        if(getenv('APP_PRODUCTION') === false){
            $whoops = (new \Whoops\Run())->pushHandler(new \Whoops\Handler\PrettyPageHandler());
            if(\Whoops\Util\Misc::isAjaxRequest()){
                $whoops->pushHandler(new \Whoops\Handler\JsonResponseHandler());
            }
            $whoops->register();
        }

        $this->composer = $composer;
        $this->classLoader = $loader;
        $this->info = new AppInfo($composer, $rootDir);
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
        if ($storage === null) {
            $storage = 'default';
        }

        if (!isset($this->cache[$storage])) {
            if($storage === 'default'){
                if(!isset($this->implicit['cache'])){
                    throw new \RuntimeException('The default cache storage was not set');
                }
                $this->cache[$storage] = new Cache($this->implicit['cache']);
            } else {
                $this->cache[$storage] = new Cache($this->getCollector()->getCacheStorage($storage));
            }
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
        if ($storage === null) {
            $storage = 'default';
        }

        if (!isset($this->session[$storage])) {
            if($storage === 'default'){
                if(!isset($this->implicit['session'])){
                    throw new \RuntimeException('The default session storage was not set');
                }
                $this->session[$storage] = new Session($this->implicit['session']);
            } else {
                $this->session[$storage] = new Session($this->getCollector()->getSessionStorage($storage));
            }
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
        if ($storage === null) {
            $storage = 'default';
        }

        if (!isset($this->config[$storage])) {
            if($storage === 'default') {
                if(!isset($this->implicit['config'])){
                    throw new \RuntimeException('The default config storage was not set');
                }
                $this->config[$storage] = new Config($this->implicit['config']);
            } else {
                $this->config[$storage] = new Config($this->getCollector()->getConfigStorage($storage));
            }
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
            $this->translations = $this->getConfig();
        }

        return $this->translations;
    }

    /**
     *
     * @return  Console
     */
    public function getConsole(): Console
    {
        return new Console($this);
    }

    /**
     * @param string|null $name
     * @throws  \RuntimeException
     * @return  Connection
     */
    public function getConnection(string $name = null): Connection
    {
        if ($name === null) {
            $name = 'default';
        }

        if (!isset($this->connection[$name])) {
            if($name === 'default'){
                if(!isset($this->implicit['connection'])){
                    throw new \RuntimeException('The default database connection was not set');
                }
                $this->connection[$name] = $this->implicit['connection'];
            } else {
                $this->connection[$name] = $this->getCollector()->getConnection($name);
            }
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
        if ($logger === null) {
            $logger = 'default';
        }

        if (!isset($this->loggers[$logger])) {
            if($logger === 'default'){
                if(!isset($this->implicit['logger'])){
                    throw new \RuntimeException('The default logger was not set');
                }
                $this->loggers[$logger] = $this->implicit['logger'];
            } else{
                $this->loggers[$logger] = $this->getCollector()->getLogger($logger);
            }
        }

        return $this->loggers[$logger];
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
     * @return array
     */
    public function getSpecials(): array
    {
        if($this->specials === null){
            $this->specials = [
                'app' => $this->getHelper(),
                'lang' => $this->getTranslator()->getLanguage(),
            ];
        }
        return $this->specials;
    }

    /**
     * @return ViewHelper
     */
    public function getViewHelper(): ViewHelper
    {
        if($this->viewHelper === null){
            $this->viewHelper = new ViewHelper($this);
        }
       return $this->viewHelper;
    }

    /**
     * @return array
     */
    public function getCollectorList(): array
    {
        if($this->collectorList === null){
            $default = require __DIR__ . '/../collectors.php';
            $this->collectorList = $this->getConfig()->read('collectors', array()) + $default;
        }

        return $this->collectorList;
    }

    /**
     * @param ConfigStorageInterface $storage
     * @return DefaultCollectorInterface
     */
    public function setConfigStorage(ConfigStorageInterface $storage): DefaultCollectorInterface
    {
        $this->implicit['config'] = $storage;
        return $this;
    }

    /**
     * @param CacheStorageInterface $storage
     * @return DefaultCollectorInterface
     */
    public function setCacheStorage(CacheStorageInterface $storage): DefaultCollectorInterface
    {
        $this->implicit['cache'] = $storage;
        return $this;
    }

    /**
     * @param ConfigStorageInterface $storage
     * @return DefaultCollectorInterface
     */
    public function setTranslationsStorage(ConfigStorageInterface $storage): DefaultCollectorInterface
    {
        $this->translations = new Config($storage);
        return $this;
    }

    /**
     * @param Connection $connection
     * @return DefaultCollectorInterface
     */
    public function setDatabaseConnection(Connection $connection): DefaultCollectorInterface
    {
        $this->implicit['connection'] = $connection;
        return $this;
    }

    /**
     * @param SessionHandlerInterface $session
     * @return DefaultCollectorInterface
     */
    public function setSessionStorage(SessionHandlerInterface $session): DefaultCollectorInterface
    {
        $this->implicit['session'] = $session;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return DefaultCollectorInterface
     */
    public function setDefaultLogger(LoggerInterface $logger): DefaultCollectorInterface
    {
        $this->implicit['logger'] = $logger;
        return $this;
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
            $this->getBootstrapInstance()->bootstrap($this);
            $this->emit('system.init');
            return $this;
        }

        $composer = $this->getComposer();
        $generator = $composer->getAutoloadGenerator();
        $extra = $composer->getPackage()->getExtra();
        $enabled = array();
        $canonicalPacks = array();
        /** @var CompletePackage[] $modules */
        $modules = array();
        $installer = null;

        if(!isset($extra['application']['installer'])){
            throw new \RuntimeException('No installer defined');
        }

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {

            if ($package->getType() !== 'opis-colibri-module') {
                $canonicalPacks[] = $package;
                continue;
            }

            $modules[$package->getName()] = $package;
        }

        if(!isset($modules[$extra['application']['installer']])){
            throw new \RuntimeException("The specified installer was not found");
        }

        $installer = $modules[$extra['application']['installer']];
        $canonicalPacks[] = $installer;
        $enabled[] = $installer->getName();

        foreach ($installer->getRequires() as $require){
            $target = $require->getTarget();
            if (isset($modules[$target])) {
                $canonicalPacks[] = $modules[$target];
                $enabled[] = $modules[$target]->getName();
            }
        }

        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $canonicalPacks);
        $autoload = $generator->parseAutoloads($packMap, $composer->getPackage());
        $loader = $generator->createLoader($autoload);

        $this->classLoader->unregister();
        $this->classLoader = $loader;
        $this->classLoader->register();

        $this->getBootstrapInstance()->bootstrap($this);
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
        $modules = $config->read('modules.enabled', array());
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
     * @return BootstrapInterface
     */
    protected function getBootstrapInstance(): BootstrapInterface
    {
        if(!$this->info->installMode()){
            return require $this->info->bootstrapFile();
        }

        return new class implements BootstrapInterface
        {
            public function bootstrap(DefaultCollectorInterface $app)
            {
                $app->setCacheStorage(new EphemeralCacheStorage())
                    ->setConfigStorage(new EphemeralConfigStorage())
                    ->setDefaultLogger(new NullLogger())
                    ->setSessionStorage(new \SessionHandler());
            }
        };
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
