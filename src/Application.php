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

namespace Opis\Colibri;

use Opis\Session\{Handlers\File as SessionHandler, ISessionHandler};
use Throwable;
use Opis\DataStore\IDataStore;
use Composer\Package\CompletePackageInterface;
use Psr\Log\{
    NullLogger,
    LoggerInterface
};
use Symfony\Component\Console\{Input\ArrayInput, Output\NullOutput};
use Opis\Cache\{
    CacheInterface,
    Drivers\Memory as MemoryDriver
};
use Opis\Events\{
    Event, EventDispatcher
};
use Opis\Http\{Request as HttpRequest, Request, Response as HttpResponse, Response};
use Opis\DataStore\Drivers\Memory as MemoryConfig;
use Opis\Database\{
    Connection,
    Database,
    Schema
};
use Opis\ORM\EntityManager;
use Opis\Routing\Context;
use Opis\Validation\Formatter;
use Opis\View\ViewRenderer;
use Opis\Intl\Translator\IDriver as TranslatorDriver;
use Opis\Colibri\Core\{Module, AppInfo, IApplicationInitializer, IApplicationContainer, ModuleManager, ModuleNotifier};
use Opis\Colibri\Rendering\{
    CallbackTemplateHandler,
    TemplateStream,
    ViewEngine
};
use Opis\Colibri\{Session\CookieContainer,
    Util\CSRFToken,
    Validation\Validator,
    Validation\RuleCollection,
    Routing\HttpRouter,
    Collector\Manager as CollectorManager};

class Application implements IApplicationContainer
{
    /** @var AppInfo */
    protected $info;

    /** @var null|ModuleManager */
    protected $moduleManager = null;

    /** @var null|ModuleNotifier */
    protected $moduleNotifier = null;

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

    /** @var  Formatter */
    protected $formatter;

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

    /** @var  Session[] */
    protected $session = [];

    /** @var CookieContainer */
    protected $sessionCookieContainer;

    /** @var  HttpRouter */
    protected $httpRouter;

    /** @var  ViewRenderer */
    protected $viewRenderer;

    /** @var LoggerInterface[] */
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

    /** @var null|callable[] */
    protected $assets = null;

    /** @var Request|null */
    protected $httpRequest;

    /** @var  Application */
    protected static $instance;

    /**
     * Application constructor.
     * @param string $rootDir
     * @param array|null $info
     */
    public function __construct(string $rootDir, array $info = null)
    {
        if ($info === null) {
            $json = $rootDir . DIRECTORY_SEPARATOR . 'composer.json';
            if (is_file($json)) {
                $json = json_decode(file_get_contents($json), true);
                $info = $json['extra']['application'] ?? null;
            }
            unset($json);
        }

        $this->info = new AppInfo($rootDir, $info ?? []);

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
     * Get module packs
     *
     * @param   bool $clear (optional)
     *
     * @return  CompletePackageInterface[]
     */
    public function getPackages(bool $clear = false): array
    {
        return $this->moduleManager()->packages($clear);
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
        return $this->moduleManager()->modules($clear);
    }

    /**
     * @param string $name
     * @return Module
     */
    public function getModule(string $name): Module
    {
        return $this->moduleManager()->module($name);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function getAllModuleDependencies(Module $module, ?callable $filter = null): array
    {
        return $this->moduleManager()->recursiveDependencies($module, $filter);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function getAllModuleDependants(Module $module, ?callable $filter = null): array
    {
        return $this->moduleManager()->recursiveDependants($module, $filter);
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
     * @return Request
     */
    public function getHttpRequest(): Request
    {
        return $this->httpRequest;
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
            $templateHandlers = $collector->getTemplateStreamHandlers();
            if (!$templateHandlers->has('callback')) {
                $templateHandlers->add('callback', CallbackTemplateHandler::class);
            }
            $this->viewRenderer = new ViewRenderer($routes, new ViewEngine());
            $resolver->copyEngines($this->viewRenderer->getEngineResolver());
            $this->viewRenderer->handle('error.{error}', function ($error) {
                return TemplateStream::url('callback', '\Opis\Colibri\Rendering\Template::error' . $error, 'php');
            }, -100)->where('error', '401|403|404|405|500|503');
            $this->viewRenderer->handle('alerts', function () {
                return TemplateStream::url('callback', '\Opis\Colibri\Rendering\Template::alerts', 'php');
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
    public function setDefaultLanguage(string $language): IApplicationContainer
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
     * Get a formatter object
     *
     * @return Formatter
     */
    public function getFormatter(): Formatter
    {
        if ($this->formatter === null) {
            $this->formatter = new Formatter();
        }

        return $this->formatter;
    }

    /**
     * Returns validator instance
     *
     * @return  Validator
     */
    public function getValidator(): Validator
    {
        if ($this->validator === null) {
            $this->validator = new Validator(new RuleCollection(), $this->getFormatter());
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
     * Get session
     *
     * @param string $name
     * @return Session
     */
    public function getSession(string $name = 'default'): Session
    {
        if (!isset($this->session[$name])) {
            if ($name === 'default') {
                if (!isset($this->implicit['session'])) {
                    throw new \RuntimeException('The default session handler was not set');
                }
                $session = $this->implicit['session'];
                $this->session[$name] = new Session($session['handler'], $session['config']);
            } else {
                $this->session[$name] = $this->getCollector()->getSessionHandler($name);
            }
        }

        return $this->session[$name];
    }

    /**
     * @return CookieContainer
     */
    public function getSessionCookieContainer(): CookieContainer
    {
        if ($this->sessionCookieContainer === null) {
            $this->sessionCookieContainer = new CookieContainer($this->getHttpRequest());
        }

        return $this->sessionCookieContainer;
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
        return new Console($this);
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
        if ($this->assets === null) {
            $this->assets = $this->collector->getAssetHandlers()->getList();
        }

        if (isset($this->assets[$module])) {
            return ($this->assets[$module])($module, $path);
        } elseif (isset($this->assets['*'])) {
            return ($this->assets['*'])($module, $path);
        }

        //npm @vendor/package
        if ($module[0] !== '@') {
            $module = str_replace('/', '.', $module);
        }

        return implode('/', [
            $this->info->assetsPath(),
            trim($module, '/'),
            ltrim($path, '/'),
        ]);
    }

    /**
     * @param IDataStore $driver
     * @return IApplicationContainer
     */
    public function setConfigDriver(IDataStore $driver): IApplicationContainer
    {
        $this->implicit['config'] = $driver;

        return $this;
    }

    /**
     * @param CacheInterface $driver
     * @return IApplicationContainer
     */
    public function setCacheDriver(CacheInterface $driver): IApplicationContainer
    {
        $this->implicit['cache'] = $driver;

        return $this;
    }

    /**
     * @param TranslatorDriver $driver
     * @return IApplicationContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): IApplicationContainer
    {
        $this->translatorDriver = $driver;
        return $this;
    }

    /**
     * @param Connection $connection
     * @return IApplicationContainer
     */
    public function setDatabaseConnection(Connection $connection): IApplicationContainer
    {
        $this->implicit['connection'] = $connection;

        return $this;
    }

    /**
     * @param ISessionHandler $handler
     * @param array $config
     * @return IApplicationContainer
     */
    public function setSessionHandler(ISessionHandler $handler, array $config = []): IApplicationContainer
    {
        $this->implicit['session'] = [
            'handler' => $handler,
            'config' => $config
        ];

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return IApplicationContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): IApplicationContainer
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
        $this->eventDispatcher = null;
        $this->viewRenderer = null;
        $this->session = null;
        $this->httpRouter = null;
        $this->collectorList = null;
        $this->cache = [];
        $this->config = [];
        $this->connection = [];
        $this->database = [];
        $this->entityManager = [];
        TemplateStream::clearCache();
    }

    /**
     * Bootstrap method
     * @return Application
     */
    public function bootstrap(): self
    {
        if (!$this->info->installMode()) {
            $this->getApplicationInitializer()->init($this);
            $this->emit('system.init');

            return $this;
        }

        $manager = $this->moduleManager();

        /** @var Module|null $installer */
        $installer = null;

        foreach ($manager->modules() as $module) {
            if ($module->isApplicationInstaller()) {
                if ($installer !== null) {
                    $formatText = '%s was defined as an application installer before %s';
                    $formatArgs = [$installer->name(), $module->name()];
                    throw new \RuntimeException(vsprintf($formatText, $formatArgs));
                }

                $installer = $module;
            }
        }

        if ($installer === null) {
            throw new \RuntimeException("No application installer was found");
        }

        $enabled = [$installer->name() => Module::ENABLED];

        foreach ($manager->recursiveDependencies($installer) as $module) {
            $enabled[$module->name()] = Module::ENABLED;
        }

        $this->getApplicationInitializer()->init($this);
        $manager->setStatusList($enabled);

        $this->emit('system.init');

        return $this;
    }

    /**
     * Execute
     *
     * @param   HttpRequest|null $request
     *
     * @return  Response
     */
    public function run(HttpRequest $request = null): Response
    {
        if ($request === null) {
            $request = HttpRequest::fromGlobals();
        }

        $this->httpRequest = $request;

        $context = new Context($request->getUri()->getPath(), $request);

        $response = $this->getHttpRouter()->route($context);

        if (!$response instanceof HttpResponse) {
            $response = new HttpResponse($response);
        }

        $this->flushResponse($request, $response);

        $this->httpRequest = null;

        return $response;
    }

    /**
     * Install a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     * @param   boolean $recursive (optional)
     *
     * @return  boolean
     */
    public function install(Module $module, bool $recollect = true, bool $recursive = false): bool
    {
        $action = function (Module $module) {
            $installer = $module->installer();
            if ($installer === null) {
                return true;
            }

            if (!class_exists($installer) || !is_subclass_of($installer, Installer::class, true)) {
                return false;
            }

            /** @var Installer $installer */
            $installer = new $installer($module);
            try {
                $installer->install();
                return true;
            } catch (Throwable $e) {
                $installer->installError($e);
                return false;
            }
        };

        $callback = function (Module $module) use ($recollect) {
            if ($recollect) {
                $this->getCollector()->recollect();
            }
            $this->emit('module.installed.' . $module->name());
        };

        $manager = $this->moduleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependencies($module) as $dependency) {
                if (!$dependency->isInstalled()) {
                    if (!$manager->install($dependency, $action, $callback)) {
                        return false;
                    }
                }
                if (!$dependency->isEnabled()) {
                    if (!$this->enable($dependency, $recollect, false)) {
                        return false;
                    }
                }
            }
        }

        return $manager->install($module, $action, $callback);
    }

    /**
     * Uninstall a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     * @param   boolean $recursive (optional)
     *
     * @return  boolean
     */
    public function uninstall(Module $module, bool $recollect = true, bool $recursive = false): bool
    {
        $action = function (Module $module) {
            $installer = $module->installer();
            if ($installer === null) {
                return true;
            }

            if (!class_exists($installer) || !is_subclass_of($installer, Installer::class, true)) {
                return false;
            }

            /** @var Installer $installer */
            $installer = new $installer($module);
            try {
                $installer->uninstall();
                return true;
            } catch (Throwable $e) {
                $installer->uninstallError($e);
                return false;
            }
        };

        $callback = function (Module $module) use ($recollect) {
            if ($recollect) {
                $this->getCollector()->recollect();
            }
            $this->emit('module.uninstalled.' . $module->name());
        };

        $manager = $this->moduleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependants($module) as $dependant) {
                if ($dependant->isEnabled()) {
                    if (!$this->disable($dependant, $recollect, false)) {
                        return false;
                    }
                }
                if ($dependant->isInstalled()) {
                    if (!$manager->uninstall($dependant, $action, $callback)) {
                        return false;
                    }
                }
            }

            if ($module->isEnabled() && !$this->disable($module, $recollect)) {
                return false;
            }
        }

        return $manager->uninstall($module, $action, $callback);
    }

    /**
     * Enable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     * @param   boolean $recursive (optional)
     *
     * @return  boolean
     */
    public function enable(Module $module, bool $recollect = true, bool $recursive = false): bool
    {
        $action = function (Module $module): bool {
            $installer = $module->installer();
            if ($installer === null) {
                return true;
            }

            if (!class_exists($installer) || !is_subclass_of($installer, Installer::class, true)) {
                return false;
            }

            /** @var Installer $installer */
            $installer = new $installer($module);
            try {
                $installer->enable();
                return true;
            } catch (Throwable $e) {
                $installer->enableError($e);
                return false;
            }
        };

        $callback = function (Module $module) use ($recollect) {
            $this->notify($module, 'enabled', true);

            if ($recollect) {
                $this->getCollector()->recollect();
            }

            $this->emit('module.enabled.' . $module->name());
        };

        $manager = $this->moduleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependencies($module) as $dependency) {
                if (!$dependency->isInstalled()) {
                    if (!$this->install($dependency, $recollect, false)) {
                        return false;
                    }
                }
                if (!$dependency->isEnabled()) {
                    if (!$manager->enable($dependency, $action, $callback)) {
                        return false;
                    }
                }
            }

            if (!$module->isInstalled() && !$this->install($module, $recollect, false)) {
                return false;
            }
        }

        return $manager->enable($module, $action, $callback);
    }

    /**
     * Disable a module
     *
     * @param   Module $module
     * @param   boolean $recollect (optional)
     * @param   boolean $recursive (optional)
     *
     * @return  boolean
     */
    public function disable(Module $module, bool $recollect = true, bool $recursive = false): bool
    {
        $action = function (Module $module) {
            $installer = $module->installer();
            if ($installer === null) {
                return true;
            }

            if (!class_exists($installer) || !is_subclass_of($installer, Installer::class, true)) {
                return false;
            }

            /** @var Installer $installer */
            $installer = new $installer($module);
            try {
                $installer->disable();
                return true;
            } catch (Throwable $e) {
                $installer->disableError($e);
                return false;
            }
        };

        $callback = function (Module $module) use ($recollect) {
            $this->notify($module, 'enabled', false);

            if ($recollect) {
                $this->getCollector()->recollect();
            }

            $this->emit('module.disabled.' . $module->name());
        };

        $manager = $this->moduleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependants($module) as $dependant) {
                if ($dependant->isEnabled()) {
                    if (!$manager->disable($dependant, $action, $callback)) {
                        return false;
                    }
                }
                if ($dependant->isInstalled()) {
                    if (!$this->uninstall($dependant, $recollect, false)) {
                        return false;
                    }
                }
            }
        }

        return $manager->disable($module, $action, $callback);
    }

    /**
     * @return IApplicationInitializer
     */
    protected function getApplicationInitializer(): IApplicationInitializer
    {
        if (!$this->info->installMode()) {
            /** @noinspection PhpIncludeInspection */
            return require $this->info->initFile();
        }

        return new class implements IApplicationInitializer
        {
            public function init(IApplicationContainer $app)
            {
                $app->setCacheDriver(new MemoryDriver())
                    ->setConfigDriver(new MemoryConfig())
                    ->setDefaultLogger(new NullLogger())
                    ->setSessionHandler(new SessionHandler($app->getAppInfo()->writableDir() . DIRECTORY_SEPARATOR . 'session'));
            }
        };
    }

    /**
     * @param string $name
     * @param bool $cancelable
     * @return Event
     */
    protected function emit(string $name, bool $cancelable = false): Event
    {
        return $this->getEventDispatcher()->emit($name, $cancelable);
    }

    /**
     * @param Module $module
     * @param string $status
     * @param bool $value
     * @param bool $overwrite
     * @return bool
     */
    protected function notify(Module $module, string $status, bool $value, bool $overwrite = false): bool
    {
        if ($this->moduleNotifier === null) {
            $this->moduleNotifier = new ModuleNotifier($this->info->writableDir());
        }

        if ($this->moduleNotifier->write($module->name(), $status, $value, $overwrite)) {
            return $this->dumpAutoload() === 0;
        }

        return false;
    }

    /**
     * @param bool $quiet
     * @return int
     */
    protected function dumpAutoload(bool $quiet = false): int
    {
        if (PHP_SAPI !== 'cli') {
            if (getenv('COMPOSER_HOME') === false && function_exists('posix_getpwuid')) {
                /** @noinspection PhpComposerExtensionStubsInspection */
                $dir = posix_getpwuid(fileowner($this->info->rootDir()))['dir'];
                putenv('COMPOSER_HOME=' . $dir . DIRECTORY_SEPARATOR . '.composer');
            }
        }

        $cwd = getcwd();
        chdir($this->info->rootDir());
        $console = new \Composer\Console\Application();
        $console->setAutoExit(false);

        try {
            $command = ['command' => 'dump-autoload'];
            if ($quiet) {
                $command[] = '--quiet';
            }

            return $console->run(new ArrayInput($command), $quiet ? new NullOutput() : null);
        } catch (Throwable $e) {
            $this->getLog()->error($e->getMessage());
        } finally {
            chdir($cwd);
        }

        return -1;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $chunkSize
     */
    protected function flushResponse(Request $request, Response $response, int $chunkSize = 8192)
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        if (!headers_sent()) {
            header(implode(' ', [
                false === stripos(PHP_SAPI, 'fcgi') ? $request->getProtocolVersion() : 'Status:',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ]));

            foreach ($response->getHeaders() as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }

            foreach ($this->getSessionCookieContainer()->getAddedCookies() as $cookie) {
                setCookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'],
                    $cookie['secure'], $cookie['http_only']);
            }

            foreach ($response->getCookies() as $cookie) {
                setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'],
                    $cookie['secure'], $cookie['http_only']);
            }
        }

        $body = $response->getBody();

        if ($body === null || $body->isClosed()) {
            return;
        }

        while (!$body->isEOF()) {
            echo $body->read($chunkSize);
        }

        flush();
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
            'router-globals' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\RouterGlobalsCollector',
                'description' => 'Collects routing related global items',
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
            'event-handlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\EventHandlerCollector',
                'description' => 'Collects event handlers',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'view-engines' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\ViewEngineCollector',
                'description' => 'Collects view engines',
            ],
            'cache-drivers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\CacheCollector',
                'description' => 'Collects cache drivers',
            ],
            'session-handlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\SessionCollector',
                'description' => 'Collects session handlers',
            ],
            'config-drivers' => [
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
            'translation-filters' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\TranslationFilterCollector',
                'description' => 'Collect translation filters',
            ],
            'commands' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\CommandCollector',
                'description' => 'Collects commands',
            ],
            'loggers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\LoggerCollector',
                'description' => 'Collects logging handlers',
            ],
            'asset-handlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\AssetsHandlerCollector',
                'description' => 'Collects asset handlers',
            ],
            'template-stream-handlers' => [
                'class' => 'Opis\\Colibri\\ItemCollectors\\TemplateStreamHandlerCollector',
                'description' => 'Collects template stream handlers',
            ],
        ];
    }

    /**
     * @return ModuleManager
     */
    protected function moduleManager(): ModuleManager
    {
        if ($this->moduleManager === null) {
            $this->moduleManager = new ModuleManager($this->info->vendorDir(), function (): IDataStore {
                return $this->getConfig();
            });
        }
        return $this->moduleManager;
    }
}