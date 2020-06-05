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

use RuntimeException, Throwable;
use Composer\Package\CompletePackageInterface;
use Opis\Session\{Handlers\File as DefaultSessionHandler, SessionHandler, Containers\RequestContainer};
use Psr\Log\{NullLogger, LoggerInterface};
use Opis\Cache\{CacheDriver, Drivers\Memory as MemoryDriver};
use Opis\Events\{Event, EventDispatcher};
use Opis\I18n\Translator\{Driver as TranslatorDriver};
use Opis\View\{Renderer};
use Opis\Http\{Request as HttpRequest, Response as HttpResponse, Responses\HtmlResponse};
use Opis\DataStore\{DataStore, Drivers\Memory as MemoryConfig};
use Opis\Database\{Connection, Database, Schema};
use Opis\ORM\EntityManager;
use Opis\Colibri\Templates\{CallbackTemplateHandler, HttpErrors, TemplateStream};
use Opis\Colibri\Core\{Container, CSRFToken, ItemCollector, Module, ModuleManager, Router, Session, Translator, View};
use Opis\Colibri\Collectors\{AssetsHandlerCollector,
    CacheCollector,
    CommandCollector,
    ConfigCollector,
    ConnectionCollector,
    ContractCollector,
    EventHandlerCollector,
    LoggerCollector,
    RouteCollector,
    RouterGlobalsCollector,
    SessionCollector,
    TemplateStreamHandlerCollector,
    TranslationCollector,
    TranslationFilterCollector,
    ViewCollector,
    ViewEngineCollector};

class Application implements ApplicationContainer
{
    protected AppInfo $info;
    protected ?ModuleManager $moduleManager = null;
    protected ?ItemCollector $collector = null;
    protected ?Container $containerInstance = null;
    protected ?Translator $translatorInstance = null;
    protected ?string $defaultLanguage = null;
    protected ?CSRFToken $csrfTokenInstance = null;
    protected ?RequestContainer $sessionCookieContainer = null;
    protected ?Router $httpRouter = null;
    protected ?Renderer $viewRenderer = null;
    protected ?EventDispatcher $eventDispatcher = null;
    protected ?HttpRequest $httpRequest = null;
    protected ?Connection $defaultConnection = null;
    protected ?Session $defaultSession = null;
    protected ?SessionHandler $defaultSessionHandler = null;
    protected ?array $defaultSessionConfig = null;
    protected ?DataStore $defaultConfigDriver = null;
    protected ?CacheDriver $defaultCacheDriver = null;
    protected ?LoggerInterface $defaultLogger = null;
    protected ?TranslatorDriver $defaultTranslatorDriver = null;
    protected ?array $collectorList = null;
    protected bool $isReady = false;

    /** @var  CacheDriver[] */
    protected array $cache = [];

    /** @var  DataStore[] */
    protected array $config = [];

    /** @var  Connection[] */
    protected array $connection = [];

    /** @var  Database[] */
    protected array $database = [];

    /** @var  EntityManager[] */
    protected array $entityManager = [];

    /** @var  Session[] */
    protected array $session = [];

    /** @var LoggerInterface[] */
    protected array $loggers = [];

    /** @var callable[]|null */
    protected ?array $assets = null;

    protected static ?Application $instance = null;

    /**
     * Application constructor.
     * @param string $rootDir
     * @param array|null $info
     */
    public function __construct(string $rootDir, ?array $info = null)
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


    public static function getInstance(): ?Application
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
     * @return  Router
     */
    public function getHttpRouter(): Router
    {
        if ($this->httpRouter === null) {
            $this->httpRouter = new Router($this);
        }

        return $this->httpRouter;
    }

    /**
     * @return HttpRequest
     */
    public function getHttpRequest(): HttpRequest
    {
        return $this->httpRequest;
    }

    /**
     * Get the view renderer
     *
     * @return  Renderer
     */
    public function getViewRenderer(): Renderer
    {
        if ($this->viewRenderer === null) {
            $this->viewRenderer = $this->getCollector()->collect(ViewCollector::class);
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
    public function setDefaultLanguage(string $language): ApplicationContainer
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
            $this->translatorInstance = new Translator($this->defaultTranslatorDriver, $this->defaultLanguage);
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
     * @param string|null $storage
     * @return CacheDriver
     */
    public function getCache(?string $storage = null): CacheDriver
    {
        if ($storage === null) {
            if ($this->defaultCacheDriver === null) {
                throw new RuntimeException('The default cache storage was not set');
            }
            return $this->defaultCacheDriver;
        }

        if (!isset($this->cache[$storage])) {
            $this->cache[$storage] = $this->getCollector()->getCacheDriver($storage);
        }

        return $this->cache[$storage];
    }

    /**
     * Get session
     *
     * @param string|null $name
     * @return Session
     */
    public function getSession(?string $name = null): Session
    {
        if ($name === null) {
            if ($this->defaultSession === null) {
                if ($this->defaultSessionHandler !== null) {
                    $this->defaultSession = new Session($this->defaultSessionConfig, $this->defaultSessionHandler);
                } else {
                    $this->defaultSession = new Session();
                }
            }
            return $this->defaultSession;
        }

        if (!isset($this->session[$name])) {
            $this->session[$name] = $this->getCollector()->getSession($name);
        }

        return $this->session[$name];
    }

    /**
     * @return RequestContainer
     */
    public function getSessionCookieContainer(): RequestContainer
    {
        if ($this->sessionCookieContainer === null) {
            $this->sessionCookieContainer = new RequestContainer($this->info->cliMode() ? null : $this->getHttpRequest());
        }

        return $this->sessionCookieContainer;
    }

    /**
     * Get config driver
     * @param string|null $driver
     * @return DataStore
     */
    public function getConfig(?string $driver = null): DataStore
    {
        if ($driver === null) {
            if ($this->defaultConfigDriver === null) {
                throw new RuntimeException('The default config storage was not set');
            }
            return $this->defaultConfigDriver;
        }

        if (!isset($this->config[$driver])) {
            $this->config[$driver] = $this->getCollector()->getConfigDriver($driver);
        }

        return $this->config[$driver];
    }

    /**
     *
     * @return Console
     */
    public function getConsole(): Console
    {
        return new Console($this);
    }

    /**
     * @param string|null $name
     * @return Connection
     */
    public function getConnection(string $name = null): Connection
    {
        if ($name === null) {
            if ($this->defaultConnection === null) {
                throw new RuntimeException('The default database connection was not set');
            }
            return $this->defaultConnection;
        }

        if (!isset($this->connection[$name])) {
            $this->connection[$name] = $this->getCollector()->getConnection($name);
        }

        return $this->connection[$name];
    }

    /**
     * @param string|null $connection
     * @return Database
     */
    public function getDatabase(string $connection = null): Database
    {
        return $this->getConnection($connection)->getDatabase();
    }

    /**
     * @param string|null $connection
     * @return Schema
     */
    public function getSchema(string $connection = null): Schema
    {
        return $this->getConnection($connection)->getSchema();
    }

    /**
     * @param string|null $connection
     * @return EntityManager
     */
    public function getEntityManager(string $connection = null): EntityManager
    {
        $entry = $connection ?? '#default';

        if (!isset($this->entityManager[$entry])) {
            $this->entityManager[$entry] = new EntityManager($this->getConnection($connection));
        }

        return $this->entityManager[$entry];
    }

    /**
     * Returns a logger
     *
     * @param   string $logger Logger's name
     *
     * @return  LoggerInterface
     */
    public function getLogger(string $logger = 'default'): LoggerInterface
    {
        if ($logger === null) {
            if ($this->defaultLogger === null) {
                $this->defaultLogger = new NullLogger();
            }
            return $this->defaultLogger;
        }

        if (!isset($this->loggers[$logger])) {
            $this->loggers[$logger] = $this->getCollector()->getLogger($logger);
        }

        return $this->loggers[$logger];
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = $this->getCollector()->getEventDispatcher();
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
     * @return ItemCollector
     */
    public function getCollector(): ItemCollector
    {
        if ($this->collector === null) {
            $this->collector = new ItemCollector($this);
        }

        return $this->collector;
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
            $this->assets = $this->collector->getAssetHandlers()->getEntries();
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
     * @param DataStore $driver
     * @return ApplicationContainer
     */
    public function setConfigDriver(DataStore $driver): ApplicationContainer
    {
        $this->defaultConfigDriver = $driver;
        return $this;
    }

    /**
     * @param CacheDriver $driver
     * @return ApplicationContainer
     */
    public function setCacheDriver(CacheDriver $driver): ApplicationContainer
    {
        $this->defaultCacheDriver = $driver;
        return $this;
    }

    /**
     * @param TranslatorDriver $driver
     * @return ApplicationContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): ApplicationContainer
    {
        $this->defaultTranslatorDriver = $driver;
        return $this;
    }

    /**
     * @param Connection $connection
     * @return ApplicationContainer
     */
    public function setDatabaseConnection(Connection $connection): ApplicationContainer
    {
        $this->defaultConnection = $connection;
        return $this;
    }

    /**
     * @param SessionHandler $handler
     * @param array $config
     * @return ApplicationContainer
     */
    public function setSessionHandler(SessionHandler $handler, array $config = []): ApplicationContainer
    {
        $this->defaultSessionHandler = $handler;
        $this->defaultSessionConfig = $config;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return ApplicationContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): ApplicationContainer
    {
        $this->defaultLogger = $logger;
        return $this;
    }

    /**
     * Clear cached objects
     */
    public function clearCachedObjects(): void
    {
        $this->containerInstance = null;
        $this->eventDispatcher = null;
        $this->viewRenderer = null;
        $this->httpRouter = null;
        $this->collectorList = null;
        $this->session = [];
        $this->cache = [];
        $this->config = [];
        $this->connection = [];
        $this->database = [];
        $this->entityManager = [];
        $this->loggers = [];
        TemplateStream::clearCache();
    }

    /**
     * Bootstrap method
     * @return Application
     */
    public function bootstrap(): self
    {
        if (!$this->info->installMode()) {
            $this->isReady = true;
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
                    throw new RuntimeException(vsprintf($formatText, $formatArgs));
                }

                $installer = $module;
            }
        }

        $enabled = [];

        if ($installer !== null) {
            $this->isReady = true;
            $enabled[$installer->name()] = Module::ENABLED;

            foreach ($manager->recursiveDependencies($installer) as $module) {
                $enabled[$module->name()] = Module::ENABLED;
            }
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
     * @param   bool             $flush
     *
     * @return  HttpResponse
     */
    public function run(?HttpRequest $request = null, bool $flush = true): HttpResponse
    {
        if ($request === null) {
            $request = HttpRequest::fromGlobals();
        }

        $this->httpRequest = $request;

        if ($this->isReady) {
            $response = $this->getHttpRouter()->route($request);
            if (!$response instanceof HttpResponse) {
                $response = new HtmlResponse($response);
            }
        } else {
            $view = new View('error.500', [
                'status' => 500,
                'message' => HttpResponse::HTTP_STATUS[500] ?? 'HTTP Error',
            ]);
            $response = new HtmlResponse($view, 500);
        }

        foreach ($this->getSessionCookieContainer()->getAddedCookies() as $cookie) {
            $response->setCookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expires'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly'],
                $cookie['samesite'],
            );
        }

        if ($flush) {
            $this->flushResponse($request, $response);
        }

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
     * @return ApplicationInitializer
     */
    protected function getApplicationInitializer(): ApplicationInitializer
    {
        if (!$this->info->installMode()) {
            /** @noinspection PhpIncludeInspection */
            return require $this->info->initFile();
        }

        return new class implements ApplicationInitializer
        {
            public function init(ApplicationContainer $app)
            {
                $app->setCacheDriver(new MemoryDriver())
                    ->setConfigDriver(new MemoryConfig());
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
     * @param HttpRequest $request
     * @param HttpResponse $response
     * @param int $chunkSize
     */
    protected function flushResponse(HttpRequest $request, HttpResponse $response, int $chunkSize = 8192): void
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

            foreach ($response->getCookies() as $cookie) {
                $name = $cookie['name'];
                $value = $cookie['value'];

                unset($cookie['name'], $cookie['value']);

                if (!$cookie['samesite']) {
                    unset($cookie['samesite']);
                }

                setcookie($name, $value, $cookie);
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
     * @return ModuleManager
     */
    protected function moduleManager(): ModuleManager
    {
        if ($this->moduleManager === null) {
            $this->moduleManager = new ModuleManager($this->info->vendorDir(), function (): DataStore {
                return $this->getConfig();
            });
        }
        return $this->moduleManager;
    }

    /**
     * @return array
     */
    protected function getDefaultCollectors(): array
    {
        return [
            'routes' => [
                'class' => RouteCollector::class,
                'description' => 'Collects web routes',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'router-globals' => [
                'class' => RouterGlobalsCollector::class,
                'description' => 'Collects routing related global items',
            ],
            'views' => [
                'class' => ViewCollector::class,
                'description' => 'Collects views',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'contracts' => [
                'class' => ContractCollector::class,
                'description' => 'Collects contracts',
            ],
            'connections' => [
                'class' => ConnectionCollector::class,
                'description' => 'Collects database connections',
            ],
            'event-handlers' => [
                'class' => EventHandlerCollector::class,
                'description' => 'Collects event handlers',
                'options' => [
                    'invertedPriority' => false,
                ],
            ],
            'view-engines' => [
                'class' => ViewEngineCollector::class,
                'description' => 'Collects view engines',
            ],
            'cache-drivers' => [
                'class' => CacheCollector::class,
                'description' => 'Collects cache drivers',
            ],
            'session-handlers' => [
                'class' => SessionCollector::class,
                'description' => 'Collects session handlers',
            ],
            'config-drivers' => [
                'class' => ConfigCollector::class,
                'description' => 'Collects config drivers',
            ],
            'translations' => [
                'class' => TranslationCollector::class,
                'description' => 'Collects translations',
            ],
            'translation-filters' => [
                'class' => TranslationFilterCollector::class,
                'description' => 'Collect translation filters',
            ],
            'commands' => [
                'class' => CommandCollector::class,
                'description' => 'Collects commands',
            ],
            'loggers' => [
                'class' => LoggerCollector::class,
                'description' => 'Collects logging handlers',
            ],
            'asset-handlers' => [
                'class' => AssetsHandlerCollector::class,
                'description' => 'Collects asset handlers',
            ],
            'template-stream-handlers' => [
                'class' => TemplateStreamHandlerCollector::class,
                'description' => 'Collects template stream handlers',
            ],
        ];
    }
}