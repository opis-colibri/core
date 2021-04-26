<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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
use Opis\Colibri\IoC\Container;
use Opis\Colibri\Utils\CSRFToken;
use Composer\Package\CompletePackageInterface;
use Symfony\Component\Console\Application as ConsoleApplication;
use Opis\Colibri\Session\{CookieContainer, Session, SessionHandler};
use Psr\Log\{NullLogger, LoggerInterface};
use Opis\Colibri\Cache\CacheDriver;
use Opis\Colibri\Events\{Event, EventDispatcher};
use Opis\Colibri\I18n\Translator\Driver as TranslatorDriver;
use Opis\Colibri\Render\Renderer;
use Opis\Colibri\Http\{Request as HttpRequest, Response as HttpResponse, Responses\FileStream};
use Opis\Colibri\Config\ConfigDriver;
use Opis\Colibri\Routing\Router;
use Opis\Database\{Connection, DatabaseHandler, Schema, EntityManager};
use Opis\JsonSchema\Validator;
use Opis\Colibri\Templates\TemplateStream;
use Opis\Colibri\Collectors\{
    AssetsHandlerCollector,
    CacheCollector,
    CommandCollector,
    ConfigCollector,
    ConnectionCollector,
    ContractCollector,
    EventHandlerCollector,
    JsonSchemaResolversCollector,
    LoggerCollector,
    RouteCollector,
    SessionCollector,
    TemplateStreamHandlerCollector,
    TranslationCollector,
    TranslationFilterCollector,
    ViewCollector,
    RenderEngineCollector
};

class Application
{
    protected ApplicationInfo $info;
    protected ?ModuleManager $moduleManager = null;
    protected ?ItemCollector $collector = null;
    protected ?Container $containerInstance = null;
    protected ?Translator $translatorInstance = null;
    protected ?string $defaultLanguage = null;
    protected ?CSRFToken $csrfTokenInstance = null;
    protected ?CookieContainer $sessionCookieContainer = null;
    protected ?Router $httpRouter = null;
    protected ?Renderer $viewRenderer = null;
    protected ?EventDispatcher $eventDispatcher = null;
    protected ?HttpRequest $httpRequest = null;
    protected ?Connection $defaultConnection = null;
    protected ?Session $defaultSession = null;
    protected ?SessionHandler $defaultSessionHandler = null;
    protected ?array $defaultSessionConfig = null;
    protected ?ConfigDriver $defaultConfigDriver = null;
    protected ?CacheDriver $defaultCacheDriver = null;
    protected ?LoggerInterface $defaultLogger = null;
    protected ?TranslatorDriver $defaultTranslatorDriver = null;
    protected ?Validator $validator = null;
    protected ?array $collectorList = null;
    protected ?ApplicationInitializer $initializer = null;

    /** @var  CacheDriver[] */
    protected array $cache = [];

    /** @var  ConfigDriver[] */
    protected array $config = [];

    /** @var  Connection[] */
    protected array $connection = [];

    /** @var  Session[] */
    protected array $session = [];

    /** @var LoggerInterface[] */
    protected array $loggers = [];

    /** @var callable[]|null */
    protected ?array $assets = null;

    protected static ?Application $instance = null;

    /**
     * Application constructor.
     * @param ApplicationInfo $info
     */
    public function __construct(ApplicationInfo $info)
    {
        $this->info = $info;

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
     * @param bool $clear (optional)
     *
     * @return  CompletePackageInterface[]
     */
    public function getPackages(bool $clear = false): array
    {
        return $this->getModuleManager()->packages($clear);
    }

    /**
     * Get a list with available modules
     *
     * @param bool $clear (optional)
     *
     * @return  Module[]
     */
    public function getModules(bool $clear = false): array
    {
        return $this->getModuleManager()->modules($clear);
    }

    /**
     * @param string $name
     * @return Module
     */
    public function getModule(string $name): Module
    {
        return $this->getModuleManager()->module($name);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function getAllModuleDependencies(Module $module, ?callable $filter = null): array
    {
        return $this->getModuleManager()->recursiveDependencies($module, $filter);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function getAllModuleDependants(Module $module, ?callable $filter = null): array
    {
        return $this->getModuleManager()->recursiveDependants($module, $filter);
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
     * @return HttpRequest|null
     */
    public function getHttpRequest(): ?HttpRequest
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
            $this->containerInstance = $this->getCollector()->collect(ContractCollector::class);
        }

        return $this->containerInstance;
    }

    /**
     * @param ?string $language
     * @return $this
     */
    public function setDefaultLanguage(?string $language): self
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
            $this->cache[$storage] = $this->getCollector()->collect(CacheCollector::class)->getInstance($storage);
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
                $this->defaultSession = new Session(
                    $this->getSessionCookieContainer(),
                    $this->defaultSessionHandler,
                    $this->defaultSessionConfig ?? [],
                );
            }
            return $this->defaultSession;
        }

        if (!isset($this->session[$name])) {
            $this->session[$name] = $this->getCollector()
                ->collect(SessionCollector::class)
                ->getSession($name, $this->getSessionCookieContainer());
        }

        return $this->session[$name];
    }

    /**
     * @return CookieContainer
     */
    public function getSessionCookieContainer(): CookieContainer
    {
        if ($this->sessionCookieContainer === null) {
            $this->sessionCookieContainer = new CookieContainer($this->httpRequest);
        }

        return $this->sessionCookieContainer;
    }

    /**
     * Get config driver
     * @param string|null $driver
     * @return ConfigDriver
     */
    public function getConfig(?string $driver = null): ConfigDriver
    {
        if ($driver === null) {
            if ($this->defaultConfigDriver === null) {
                throw new RuntimeException('The default config storage was not set');
            }
            return $this->defaultConfigDriver;
        }

        if (!isset($this->config[$driver])) {
            $this->config[$driver] = $this->getCollector()->collect(ConfigCollector::class)->getInstance($driver);
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
     * @return ConsoleApplication
     */
    public function getSetupConsole(): ConsoleApplication
    {
        $console = new ConsoleApplication("Opis Colibri Setup");
        $console->setAutoExit(true);

        $console->add(new Commands\Setup\Env());
        $console->add(new Commands\Setup\App());

        return $console;
    }

    /**
     * @param string|null $name
     * @return Connection
     */
    public function getConnection(?string $name = null): Connection
    {
        if ($name === null) {
            if ($this->defaultConnection === null) {
                throw new RuntimeException('The default database connection was not set');
            }
            return $this->defaultConnection;
        }

        if (!isset($this->connection[$name])) {
            $this->connection[$name] = $this->getCollector()
                ->collect(ConnectionCollector::class)
                ->getInstance($name);
        }

        return $this->connection[$name];
    }

    /**
     * @param string|null $connection
     * @return DatabaseHandler
     */
    public function getDatabaseHandler(?string $connection = null): DatabaseHandler
    {
        return $this->getConnection($connection)->getDatabaseHandler();
    }

    /**
     * @param string|null $connection
     * @return Schema
     */
    public function getSchema(?string $connection = null): Schema
    {
        return $this->getConnection($connection)->getSchema();
    }

    /**
     * @param string|null $connection
     * @return EntityManager
     */
    public function getEntityManager(?string $connection = null): EntityManager
    {
        return $this->getConnection($connection)->getEntityManager();
    }

    /**
     * Returns a logger
     *
     * @param string|null $logger Logger's name
     *
     * @return  LoggerInterface
     */
    public function getLogger(?string $logger = null): LoggerInterface
    {
        if ($logger === null) {
            if ($this->defaultLogger === null) {
                $this->defaultLogger = new NullLogger();
            }
            return $this->defaultLogger;
        }

        if (!isset($this->loggers[$logger])) {
            $this->loggers[$logger] = $this->getCollector()->collect(LoggerCollector::class)->getInstance($logger);
        }

        return $this->loggers[$logger];
    }

    /**
     * @return EventDispatcher
     */
    public function getEventDispatcher(): EventDispatcher
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = $this->getCollector()->collect(EventHandlerCollector::class);
        }

        return $this->eventDispatcher;
    }

    /**
     * Get information about this application
     *
     * @return  ApplicationInfo
     */
    public function getAppInfo(): ApplicationInfo
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
     * @return Validator
     */
    public function getValidator(): Validator
    {
        if ($this->validator === null) {
            $this->validator = $this->getCollector()->collect(JsonSchemaResolversCollector::class)->buildValidator();
        }

        return $this->validator;
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
    public function resolveAsset(string $module, string $path): string
    {
        if ($this->assets === null) {
            $this->assets = $this->getCollector()->collect(AssetsHandlerCollector::class)->getEntries();
        }

        if (isset($this->assets[$module])) {
            return ($this->assets[$module])($path, $module);
        } elseif (isset($this->assets['*'])) {
            return ($this->assets['*'])($path, $module);
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
     * @param ConfigDriver $driver
     * @return $this
     */
    public function setConfigDriver(ConfigDriver $driver): self
    {
        $this->defaultConfigDriver = $driver;
        return $this;
    }

    /**
     * @param CacheDriver $driver
     * @return $this
     */
    public function setCacheDriver(CacheDriver $driver): self
    {
        $this->defaultCacheDriver = $driver;
        return $this;
    }

    /**
     * @param TranslatorDriver $driver
     * @return $this
     */
    public function setTranslatorDriver(TranslatorDriver $driver): self
    {
        $this->defaultTranslatorDriver = $driver;
        return $this;
    }

    /**
     * @param Connection $connection
     * @return $this
     */
    public function setDatabaseConnection(Connection $connection): self
    {
        $this->defaultConnection = $connection;
        return $this;
    }

    /**
     * @param SessionHandler $handler
     * @param array $config
     * @return $this
     */
    public function setSessionHandler(SessionHandler $handler, array $config = []): self
    {
        $this->defaultSessionHandler = $handler;
        $this->defaultSessionConfig = $config;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setDefaultLogger(LoggerInterface $logger): self
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
        $this->loggers = [];
        TemplateStream::clearCache();
    }

    /**
     * Bootstrap method
     * @return Application
     */
    public function bootstrap(): self
    {
        $this->getApplicationInitializer()->bootstrap($this);
        return $this;
    }


    /**
     * @param HttpRequest|null $request
     * @param bool $flush
     * @return HttpResponse
     * @throws Throwable
     */
    public function run(?HttpRequest $request = null, bool $flush = true): HttpResponse
    {
        if ($request === null) {
            $request = HttpRequest::fromGlobals();
        }

        $prevRequest = $this->httpRequest;

        $this->httpRequest = $request;

        try {
            $response = $this->getHttpRouter()->route($request);
        } catch (Throwable $exception) {
            if (env('APP_PRODUCTION', false) === false) {
                throw $exception;
            }
            $response = $this->onError($request, $exception);
        } finally {
            $this->httpRequest = $prevRequest;
        }

        if ($flush) {
            $this->flushResponse($request, $response);
        }

        return $response;
    }

    /**
     * Unhandled exception response
     * @param HttpRequest $request
     * @param Throwable $exception
     * @return HttpResponse
     */
    protected function onError(HttpRequest $request, Throwable $exception): HttpResponse
    {
        $this->getLogger()->error('Internal server error', [
            'message' => $exception->getMessage(),
            'file' => $exception->getLine(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $body = null;

        if (stripos($request->getHeader('Content-Type', ''), 'application/json') !== false) {
            $body = (object)['message' => 'Application error'];
        }

        return httpError(500, $body);
    }

    /**
     * @param bool $flush
     * @return HttpResponse
     * @throws Throwable
     */
    public function serve(bool $flush = true): HttpResponse
    {
        $request = HttpRequest::fromGlobals();

        $path = $request->getUri()->path() ?: '';

        if ($path !== '' && $path !== '/') {
            $file = null;
            $path = rawurldecode($path);
            $info = $this->getAppInfo();

            // Check public and assets

            $public_file = $info->publicDir() . $path;
            if (is_file($public_file)) {
                $file = $public_file;
            } else {
                $assetsPath = rtrim($info->assetsPath(), '/') . '/';
                if (str_starts_with($path, $assetsPath)) {
                    $file = $info->assetsDir() . '/' . substr($path, strlen($assetsPath));
                    if (!is_file($file)) {
                        $file = null;
                    }
                }
            }

            if ($file !== null) {
                $response = new FileStream($file);
                if ($flush) {
                    $this->flushResponse($request, $response);
                }
                return $response;
            }
        }

        return $this->run($request, $flush);
    }

    /**
     * Install a module
     *
     * @param Module $module
     * @param boolean $recollect (optional)
     * @param boolean $recursive (optional)
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

        $manager = $this->getModuleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependencies($module) as $dependency) {
                if (!$dependency->isInstalled()) {
                    if (!$manager->install($dependency, $action, $callback)) {
                        return false;
                    }
                }
                if (!$dependency->isEnabled()) {
                    if (!$this->enable($dependency, $recollect)) {
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
     * @param Module $module
     * @param boolean $recollect (optional)
     * @param boolean $recursive (optional)
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

        $manager = $this->getModuleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependants($module) as $dependant) {
                if ($dependant->isEnabled()) {
                    if (!$this->disable($dependant, $recollect)) {
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
     * @param Module $module
     * @param boolean $recollect (optional)
     * @param boolean $recursive (optional)
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

        $manager = $this->getModuleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependencies($module) as $dependency) {
                if (!$dependency->isInstalled()) {
                    if (!$this->install($dependency, $recollect)) {
                        return false;
                    }
                }
                if (!$dependency->isEnabled()) {
                    if (!$manager->enable($dependency, $action, $callback)) {
                        return false;
                    }
                }
            }

            if (!$module->isInstalled() && !$this->install($module, $recollect)) {
                return false;
            }
        }

        return $manager->enable($module, $action, $callback);
    }

    /**
     * Disable a module
     *
     * @param Module $module
     * @param boolean $recollect (optional)
     * @param boolean $recursive (optional)
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

        $manager = $this->getModuleManager();

        if ($recursive) {
            foreach ($manager->recursiveDependants($module) as $dependant) {
                if ($dependant->isEnabled()) {
                    if (!$manager->disable($dependant, $action, $callback)) {
                        return false;
                    }
                }
                if ($dependant->isInstalled()) {
                    if (!$this->uninstall($dependant, $recollect)) {
                        return false;
                    }
                }
            }
        }

        return $manager->disable($module, $action, $callback);
    }

    /**
     * @return ModuleManager
     */
    public function getModuleManager(): ModuleManager
    {
        if ($this->moduleManager === null) {
            $this->moduleManager = new ModuleManager($this->info->vendorDir(),
                fn(): ConfigDriver => $this->getConfig());
        }

        return $this->moduleManager;
    }

    /**
     * @return ApplicationInitializer
     */
    public function getApplicationInitializer(): ApplicationInitializer
    {
        if ($this->initializer === null) {
            /** @noinspection PhpIncludeInspection */
            $this->initializer = require($this->info->initFile());
        }

        return $this->initializer;
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
     * @return array
     */
    protected function getDefaultCollectors(): array
    {
        return [
            'routes' => [
                'class' => RouteCollector::class,
                'description' => 'Collects web routes',
            ],
            'views' => [
                'class' => ViewCollector::class,
                'description' => 'Collects views',
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
            ],
            'view-engines' => [
                'class' => RenderEngineCollector::class,
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
            'json-schema-resolvers' => [
                'class' => JsonSchemaResolversCollector::class,
                'description' => 'Collects JSON Schema resolvers',
            ],
        ];
    }
}