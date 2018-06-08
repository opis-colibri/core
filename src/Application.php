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

namespace Opis\Colibri;

use Opis\DataStore\IDataStore;
use SessionHandlerInterface;
use Composer\{
    Composer,
    Factory,
    Autoload\ClassLoader,
    IO\NullIO,
    Json\JsonFile,
    Package\CompletePackage,
    Repository\InstalledFilesystemRepository
};
use Psr\Log\{
    NullLogger,
    LoggerInterface
};
use Symfony\Component\Console\Input\ArrayInput;
use Opis\Cache\{
    CacheInterface,
    Drivers\Memory as MemoryDriver
};
use Opis\Events\{
    Event, EventDispatcher
};
use Opis\Http\{
    Request as HttpRequest, Response as HttpResponse, Uri
};
use Opis\DataStore\Drivers\Memory as MemoryConfig;
use Opis\Database\{
    Connection,
    Database,
    Schema
};
use Opis\ORM\EntityManager;
use Opis\Routing\Context;
use Opis\Session\Session;
use Opis\Validation\Placeholder;
use Opis\View\ViewRenderer;
use Opis\Intl\Translator\IDriver as TranslatorDriver;
use Opis\Colibri\{
    SPA\DataHandler,
    Rendering\TemplateStream,
    Rendering\ViewEngine,
    Util\Mutex,
    Util\CSRFToken,
    Validation\Validator,
    Validation\ValidatorCollection,
    Routing\HttpRouter,
    Composer\Plugin,
    Collector\Manager as CollectorManager
};

class Application implements ISettingsContainer
{
    const COMPOSER_TYPE = 'opis-colibri-module';

    /** @var AppInfo */
    protected $info;

    /** @var    Composer */
    protected $composer;

    /** @var ClassLoader */
    protected $classLoader;

    /** @var    array|null */
    protected $packages;

    /** @var    array|null */
    protected $modules;

    /** @var  CollectorManager */
    protected $collector;

    /** @var  Container */
    protected $containerInstance;

    /** @var TranslatorDriver */
    protected $translatorDriver;

    /** @var  Translator */
    protected $translatorInstance;

    /** @var string|null */
    protected $defaultLanguage = null;

    /** @var  CSRFToken */
    protected $csrfTokenInstance;

    /** @var  Placeholder */
    protected $placeholderInstance;

    /** @var  CacheInterface[] */
    protected $cache = [];

    /** @var  IDataStore[] */
    protected $config = [];

    /** @var  Connection[] */
    protected $connection = [];

    /** @var  Database[] */
    protected $database = [];

    /** @var  EntityManager[] */
    protected $entityManager = [];

    /** @var  Session */
    protected $session;

    /** @var  HttpRouter */
    protected $httpRouter;

    /** @var  ViewRenderer */
    protected $viewRenderer;

    /** @var \Psr\Log\LoggerInterface[] */
    protected $loggers = [];

    /** @var  EventDispatcher */
    protected $eventDispatcher;

    /** @var  Validator */
    protected $validator;

    /** @var array */
    protected $implicit = [];

    /** @var  array|null */
    protected $collectorList;

    /** @var Alerts|null */
    protected $alerts;

    /** @var  Application */
    protected static $instance;

    /**
     * Application constructor
     * @param string $rootDir
     * @param ClassLoader $loader
     * @param Composer|null $composer
     */
    public function __construct(string $rootDir, ClassLoader $loader, Composer $composer = null)
    {
        $json = json_decode(file_get_contents($rootDir . '/composer.json'), true);

        $this->composer = $composer;
        $this->classLoader = $loader;
        $this->info = new AppInfo($rootDir, $json['extra']['application'] ?? []);

        TemplateStream::register();

        if (static::$instance === null) {
            static::$instance = $this;
        }
    }

    /**
     * @return Application|null
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Get a Composer instance
     *
     * @param bool $new
     * @return  Composer
     */
    public function getComposer(bool $new = false): Composer
    {
        if ($this->composer !== null && $new === false) {
            return $this->composer;
        }
        $rootDir = $this->info->rootDir();
        $composerFile = $this->info->composerFile();
        return (new Factory())->createComposer(new NullIO(), $composerFile, false, $rootDir);
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

        $packages = [];
        $repository = new InstalledFilesystemRepository(new JsonFile($this->info->vendorDir() . '/composer/installed.json'));
        foreach ($repository->getCanonicalPackages() as $package) {
            if (!$package instanceof CompletePackage || $package->getType() !== static::COMPOSER_TYPE) {
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

        $modules = [];

        foreach ($this->getPackages($clear) as $module => $package) {
            $modules[$module] = new Module($module, $package);
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
     * Get the view renderer
     *
     * @return  ViewRenderer
     */
    public function getViewRenderer(): ViewRenderer
    {
        if ($this->viewRenderer === null) {
            $collector = $this->getCollector();
            $routes = $collector->getViews();
            $resolver = $collector->getViewEngineResolver();
            $this->viewRenderer = new ViewRenderer($routes, new ViewEngine());
            $resolver->copyEngines($this->viewRenderer->getEngineResolver());
            $this->viewRenderer->handle('error.{error}', function ($error) {
                return 'template://\Opis\Colibri\Rendering\Template::error' . $error;
            }, -100)->where('error', '401|403|404|405|500|503');
            $this->viewRenderer->handle('alerts', function () {
                return 'template://\Opis\Colibri\Rendering\Template::alerts';
            }, -100);
        }

        return $this->viewRenderer;
    }

    /**
     * Return the dependency injection container
     *
     * @return  Container
     */
    public function getContainer(): Container
    {
        if ($this->containerInstance === null) {
            $this->containerInstance = $this->getCollector()->getContracts();
        }

        return $this->containerInstance;
    }

    /**
     * @inheritDoc
     */
    public function setDefaultLanguage(string $language): ISettingsContainer
    {
        $this->defaultLanguage = $language;
        if ($this->translatorInstance !== null) {
            $this->translatorInstance->setDefaultLanguage($language);
        }
        return $this;
    }


    /**
     * @return  Translator
     */
    public function getTranslator(): Translator
    {
        if ($this->translatorInstance === null) {
            $this->translatorInstance = new Translator($this->translatorDriver, $this->defaultLanguage);
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
            $this->csrfTokenInstance = new CSRFToken();
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
        if ($this->validator === null) {
            $this->validator = new Validator(new ValidatorCollection(), $this->getPlaceholder());
        }

        return $this->validator;
    }

    /**
     * Returns a caching storage
     *
     * @param   string $storage (optional) Storage name
     *
     * @return  CacheInterface
     */
    public function getCache(string $storage = 'default'): CacheInterface
    {
        if (!isset($this->cache[$storage])) {
            if ($storage === 'default') {
                if (!isset($this->implicit['cache'])) {
                    throw new \RuntimeException('The default cache storage was not set');
                }
                $this->cache[$storage] = $this->implicit['cache'];
            } else {
                $this->cache[$storage] = $this->getCollector()->getCacheDriver($storage);
            }
        }

        return $this->cache[$storage];
    }

    /**
     * Returns a session storage
     *
     * @return  Session
     */
    public function getSession(): Session
    {
        if ($this->session === null) {
            if (!isset($this->implicit['session'])) {
                throw new \RuntimeException('The default session storage was not set');
            }
            $this->session = new Session($this->getCollector()->getSessionHandler($this->implicit['session']));
        }

        return $this->session;
    }

    /**
     * Returns a config storage
     *
     * @param   string $driver (optional) Driver's name
     *
     * @return  IDataStore
     */
    public function getConfig(string $driver = 'default'): IDataStore
    {
        if (!isset($this->config[$driver])) {
            if ($driver === 'default') {
                if (!isset($this->implicit['config'])) {
                    throw new \RuntimeException('The default config storage was not set');
                }
                $this->config[$driver] = $this->implicit['config'];
            } else {
                $this->config[$driver] = $this->getCollector()->getConfigDriver($driver);
            }
        }

        return $this->config[$driver];
    }

    /**
     *
     * @return  Console
     */
    public function getConsole(): Console
    {
        return new Console();
    }

    /**
     * @param string $name
     * @throws  \RuntimeException
     * @return  Connection
     */
    public function getConnection(string $name = 'default'): Connection
    {
        if (!isset($this->connection[$name])) {
            if ($name === 'default' && isset($this->implicit['connection'])) {
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
     * @param   string $connection (optional) Connection name
     *
     * @return  Database
     */
    public function getDatabase(string $connection = 'default'): Database
    {
        if (!isset($this->database[$connection])) {
            $this->database[$connection] = new Database($this->getConnection($connection));
        }

        return $this->database[$connection];
    }

    /**
     * Returns a database schema abstraction layer
     *
     * @param   string $connection (optional) Connection name
     *
     * @return  Schema
     */
    public function getSchema(string $connection = 'default'): Schema
    {
        return $this->getDatabase($connection)->schema();
    }

    /**
     * Returns an entity manager
     *
     * @param   string|null $connection (optional) Connection name
     *
     * @return  EntityManager
     */
    public function getEntityManager(string $connection = 'default'): EntityManager
    {
        if (!isset($this->entityManager[$connection])) {
            $this->entityManager[$connection] = new EntityManager($this->getConnection($connection));
        }

        return $this->entityManager[$connection];
    }

    /**
     * Returns a logger
     *
     * @param   string $logger Logger's name
     *
     * @return  LoggerInterface
     */
    public function getLog(string $logger = 'default'): LoggerInterface
    {
        if (!isset($this->loggers[$logger])) {
            if ($logger === 'default') {
                if (!isset($this->implicit['logger'])) {
                    throw new \RuntimeException('The default logger was not set');
                }
                $this->loggers[$logger] = $this->implicit['logger'];
            } else {
                $this->loggers[$logger] = $this->getCollector()->getLogger($logger);
            }
        }

        return $this->loggers[$logger];
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new EventDispatcher($this->getCollector()->getEventHandlers());
        }

        return $this->eventDispatcher;
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
     * @return Alerts
     */
    public function getAlerts(): Alerts
    {
        if ($this->alerts === null) {
            $this->alerts = new Alerts();
        }

        return $this->alerts;
    }

    /**
     * @param bool $fresh
     * @return array
     */
    public function getCollectorList(bool $fresh = false): array
    {
        if ($fresh) {
            $this->collectorList = null;
        }

        if ($this->collectorList === null) {
            $this->collectorList = $this->getConfig()->read('collectors', []) + $this->getDefaultCollectors();
        }

        return $this->collectorList;
    }

    /**
     * @param string $module
     * @param string $path
     * @return string
     */
    public function resolveAsset(string $module, string $path)
    {
        static $list = null;

        if ($list === null) {
            $list = $this->collector->getAssetHandlers()->getList();
        }

        if (isset($list[$module])) {
            return $list[$module]($module, $path);
        } elseif (isset($list['*'])) {
            return $list['*']($module, $path);
        }

        return implode('/', [
            $this->info->assetsPath(),
            trim(str_replace('/', '.', $module), '/'),
            ltrim($path, '/')
        ]);
    }

    /**
     * @param IDataStore $driver
     * @return ISettingsContainer
     */
    public function setConfigDriver(IDataStore $driver): ISettingsContainer
    {
        $this->implicit['config'] = $driver;

        return $this;
    }

    /**
     * @param CacheInterface $driver
     * @return ISettingsContainer
     */
    public function setCacheDriver(CacheInterface $driver): ISettingsContainer
    {
        $this->implicit['cache'] = $driver;

        return $this;
    }

    /**
     * @param TranslatorDriver $driver
     * @return ISettingsContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): ISettingsContainer
    {
        $this->translatorDriver = $driver;
        return $this;
    }

    /**
     * @param Connection $connection
     * @return ISettingsContainer
     */
    public function setDatabaseConnection(Connection $connection): ISettingsContainer
    {
        $this->implicit['connection'] = $connection;

        return $this;
    }

    /**
     * @param SessionHandlerInterface $session
     * @return ISettingsContainer
     */
    public function setSessionHandler(SessionHandlerInterface $session): ISettingsContainer
    {
        $this->implicit['session'] = $session;

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return ISettingsContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): ISettingsContainer
    {
        $this->implicit['logger'] = $logger;

        return $this;
    }

    /**
     * Clear cached objects
     */
    public function clearCachedObjects()
    {
        $this->containerInstance = null;
        $this->viewRenderer = null;
        $this->session = null;
        $this->httpRouter = null;
        $this->collectorList = null;
        $this->cache = [];
        $this->config = [];
        $this->connection = [];
        $this->database = [];
        $this->entityManager = [];
    }

    /**
     * Bootstrap method
     * @return Application
     */
    public function bootstrap(): self
    {
        if (!$this->info->installMode()) {
            $this->getBootstrapInstance()->bootstrap($this);
            $this->emit('system.init');

            return $this;
        }

        $composer = $this->getComposer(true);
        $generator = $composer->getAutoloadGenerator();
        $enabled = [];
        $canonicalPacks = [];
        /** @var CompletePackage[] $modules */
        $modules = [];
        /** @var CompletePackage|null $installer */
        $installer = null;

        foreach ($composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {

            if ($package->getType() !== static::COMPOSER_TYPE) {
                $canonicalPacks[] = $package;
                continue;
            }

            $modules[$package->getName()] = $package;
            $extra = $package->getExtra();
            if (isset($extra['module']['is-app-installer']) && $extra['module']['is-app-installer']) {
                if ($installer !== null) {
                    $formatText = '%s was defined as an application installer before %s';
                    $formatArgs = [$installer->getName(), $package->getName()];
                    throw new \RuntimeException(vsprintf($formatText, $formatArgs));
                }
                $installer = $package;
            }
        }

        if ($installer === null) {
            throw new \RuntimeException("No application installer was found");
        }

        $canonicalPacks[] = $installer;
        $enabled[] = $installer->getName();

        foreach ($installer->getRequires() as $require) {
            $target = $require->getTarget();
            if (isset($modules[$target])) {
                $canonicalPacks[] = $modules[$target];
                $enabled[] = $modules[$target]->getName();
            }
        }

        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(),
            $canonicalPacks);
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
            $request = new HttpRequest();
        }

        $context = new Context($request->getUri()->getPath(), $request);

        $response = $this->getHttpRouter()->route($context);

        if (!$response instanceof HttpResponse) {
            $response = new HttpResponse($response);
        }

        if (PHP_SAPI !== 'cli') {
            if (!headers_sent()) {
                header(implode(' ', [
                    false === stripos(PHP_SAPI, 'fcgi') ? $request->getProtocolVersion() : 'Status:',
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                ]));

                foreach ($response->getHeaders() as $name => $value) {
                    header(sprintf('%s: %s'), $name, $value);
                }

                foreach ($response->getCookies() as $cookie) {
                    setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'],
                        $cookie['secure'], $cookie['http_only']);
                }
            }

            if (null !== $body = $response->getBody()) {
                while (!$body->eof()) {
                    echo $body->read();
                }
            }
        }

        return $response;
    }

    /**
     * Install a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     * @throws \Exception
     */
    public function install(Module $module, bool $recollect = true): bool
    {
        $mutex = $this->getMutex();
        if (!$mutex->lock(false)) {
            return false;
        }

        if (!$module->canBeInstalled()) {
            $mutex->unlock();
            return false;
        }

        $config = $this->getConfig();

        $installed = $config->read('modules.installed', []);
        $installed[] = $module->name();
        $config->write('modules.installed', $installed);

        $this->dumpAutoload();
        $this->reloadClassLoader();

        if (false !== $installer = $module->installer()) {
            /** @var Installer $installer */
            $installer = new $installer();
            try {
                $installer->install();
            } catch (\Exception $e) {
                $installer->installError($e);
                // Revert
                array_pop($installed);
                $config->write('modules.installed', $installed);
                $this->dumpAutoload();
                $this->reloadClassLoader();
                $mutex->unlock();
                return false;
            }
        }

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.installed.' . $module->name());

        $mutex->unlock();
        return true;
    }

    /**
     * Uninstall a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     * @throws \Exception
     */
    public function uninstall(Module $module, bool $recollect = true): bool
    {
        $mutex = $this->getMutex();
        if (!$mutex->lock(false)) {
            return false;
        }

        if (!$module->canBeUninstalled()) {
            $mutex->unlock();
            return false;
        }

        if (false !== $installer = $module->installer()) {
            /** @var Installer $installer */
            $installer = new $installer();
            try {
                $installer->uninstall();
            } catch (\Exception $e) {
                $installer->uninstallError($e);
                $mutex->unlock();
                return false;
            }
        }

        $config = $this->getConfig();
        $installed = $config->read('modules.installed', []);
        $pos = array_search($module->name(), $installed);
        if ($pos === false) {
            $mutex->unlock();
            return false;
        }
        array_splice($installed, $pos, 1);
        $config->write('modules.installed', $installed);

        $this->dumpAutoload();
        $this->reloadClassLoader();

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.uninstalled.' . $module->name());

        $mutex->unlock();
        return true;
    }

    /**
     * Enable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     * @throws \Exception
     */
    public function enable(Module $module, bool $recollect = true): bool
    {
        $mutex = $this->getMutex();
        if (!$mutex->lock(false)) {
            return false;
        }

        if (!$module->canBeEnabled()) {
            $mutex->unlock();
            return false;
        }

        $config = $this->getConfig();
        $enabled = $config->read('modules.enabled', []);
        $enabled[] = $module->name();
        $config->write('modules.enabled', $enabled);

        $this->rebuildSPA($module);
        $this->dumpAutoload();
        $this->reloadClassLoader();

        if (false !== $installer = $module->installer()) {
            /** @var Installer $installer */
            $installer = new $installer();
            try {
                $installer->enable();
            } catch (\Exception $e) {
                $installer->enableError($e);
                // Revert
                array_pop($enabled);
                $config->write('modules.enabled', $enabled);
                $this->dumpAutoload();
                $this->reloadClassLoader();
                $mutex->unlock();
                return false;
            }
        }

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.enabled.' . $module->name());

        $mutex->unlock();
        return true;
    }

    /**
     * Disable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     *
     * @return  boolean
     * @throws \Exception
     */
    public function disable(Module $module, bool $recollect = true): bool
    {
        $mutex = $this->getMutex();
        if (!$mutex->lock(false)) {
            return false;
        }

        if (!$module->canBeDisabled()) {
            $mutex->unlock();
            return false;
        }

        if (false !== $installer = $module->installer()) {
            /** @var Installer $installer */
            $installer = new $installer();
            try {
                $installer->disable();
            } catch (\Exception $e) {
                $installer->disableError($e);
                $mutex->unlock();
                return false;
            }
        }

        $config = $this->getConfig();
        $enabled = $config->read('modules.enabled', []);
        $pos = array_search($module->name(), $enabled);
        if ($pos === false) {
            $mutex->unlock();
            return false;
        }
        array_splice($enabled, $pos, 1);
        $config->write('modules.enabled', $enabled);

        $this->rebuildSPA($module);
        $this->dumpAutoload();
        $this->reloadClassLoader();

        if ($recollect) {
            $this->getCollector()->recollect();
        }

        $this->emit('module.disabled.' . $module->name());

        $mutex->unlock();
        return true;
    }

    /**
     * @return IBootstrap
     */
    protected function getBootstrapInstance(): IBootstrap
    {
        if (!$this->info->installMode()) {
            /** @noinspection PhpIncludeInspection */
            return require $this->info->bootstrapFile();
        }

        return new class implements IBootstrap
        {
            public function bootstrap(ISettingsContainer $app)
            {
                $app->setCacheDriver(new MemoryDriver())
                    ->setConfigDriver(new MemoryConfig())
                    ->setDefaultLogger(new NullLogger())
                    ->setSessionHandler(new \SessionHandler());
            }
        };
    }

    /**
     * Reload class loader
     */
    protected function reloadClassLoader()
    {
        $loader = $this->generateClassLoader($this->getComposer(true));
        $this->classLoader->unregister();
        $this->classLoader = $loader;
        $this->classLoader->register();
    }

    /**
     * @param Composer $composer
     * @return ClassLoader
     */
    protected function generateClassLoader(Composer $composer): ClassLoader
    {
        $installMode = $this->info->installMode();
        $config = $this->getConfig();
        $installed = $config->read('modules.installed', []);
        $enabled = $config->read('modules.enabled', []);

        $plugin = new Plugin();
        $plugin->activate($composer, new NullIO());
        $packages = $plugin->preparePacks($installMode, $enabled, $installed);

        $generator = $composer->getAutoloadGenerator();
        $packMap = $generator->buildPackageMap($composer->getInstallationManager(), $composer->getPackage(), $packages);
        $autoload = $generator->parseAutoloads($packMap, $composer->getPackage());

        return $generator->createLoader($autoload);
    }

    /**
     * @param string $name
     * @param bool $cancelable
     * @return Event
     */
    protected function emit(string $name, bool $cancelable = false): Event
    {
        return $this->getEventDispatcher()->dispatch(new Event($name, $cancelable));
    }

    /**
     * @return Mutex
     */
    protected function getMutex(): Mutex
    {
        return new Mutex(__FILE__);
    }

    /**
     * @param Module $module
     * @throws \Exception
     */
    protected function rebuildSPA(Module $module)
    {
        try {
            $extra = $module->getPackage()->getExtra();
        } catch (\Exception $exception) {
            return;
        }

        if (!isset($extra['module']['spa'])) {
            return;
        }

        $spa = $extra['module']['spa'];
        $spa += ['register' => [], 'extend' => []];
        $data_handler = new DataHandler($this->info);
        $data = $data_handler->getData();
        $rebuild = &$data['rebuild'];
        $module_name = $module->name();
        $package_name = str_replace('/', '.', $module_name);

        foreach ($spa['register'] as $local_app_name => $app_data) {
            $app_name = $package_name . '.' . $local_app_name;
            if (!in_array($app_name, $rebuild)) {
                $rebuild[] = $app_name;
            }
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($module_name === $ext_module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        $data_handler->setData($data);
    }

    /**
     * Dump autoload
     */
    protected function dumpAutoload()
    {
        if (PHP_SAPI !== 'cli') {
            if (getenv('COMPOSER_HOME') === false) {
                $dir = posix_getpwuid(fileowner($this->info->rootDir()))['dir'];
                putenv('COMPOSER_HOME=' . $dir . DIRECTORY_SEPARATOR . '.composer');
            }
        }
        $cwd = getcwd();
        chdir($this->info->rootDir());
        $console = new \Composer\Console\Application();
        $console->setAutoExit(false);
        try {
            $console->run(new ArrayInput([
                'command' => 'dump-autoload',
            ]));
        } catch (\Exception $e) {
            $this->getLog()->error($e->getMessage());
        }
        chdir($cwd);
    }

    /**
     * @return array
     */
    protected function getDefaultCollectors(): array
    {
        return [
            'routes' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\RouteCollector',
                'description' => 'Collects web routes',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'middleware' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\MiddlewareCollector',
                'description' => 'Collects middleware items',
            ],
            'views' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ViewCollector',
                'description' => 'Collects views',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'contracts' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ContractCollector',
                'description' => 'Collects contracts',
            ],
            'connections' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ConnectionCollector',
                'description' => 'Collects database connections',
            ],
            'eventhandlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\EventHandlerCollector',
                'description' => 'Collects event handlers',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'viewengines' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ViewEngineCollector',
                'description' => 'Collects view engines',
            ],
            'cachedrivers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\CacheCollector',
                'description' => 'Collects cache drivers',
            ],
            'sessionhandlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\SessionCollector',
                'description' => 'Collects session handlers',
            ],
            'configdrivers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ConfigCollector',
                'description' => 'Collects config drivers',
            ],
            'validators' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ValidatorCollector',
                'description' => 'Collects validators',
            ],
            'translations' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationCollector',
                'description' => 'Collects translations',
            ],
            'translationfilters' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationFilterCollector',
                'description' => 'Collect translation filters',
            ],
            'commands' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\CommandCollector',
                'description' => 'Collects commands',
            ],
            'loggers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\LoggerCollector',
                'description' => 'Collects log storages',
            ],
            'assethandlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\AssetsHandlerCollector',
                'description' => 'Collects asset handlers',
            ],
        ];
    }
}