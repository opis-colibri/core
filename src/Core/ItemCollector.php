<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\Core;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use RuntimeException;
use Opis\Colibri\{
    Application, Collector
};
use Opis\Colibri\Serializable\{AdvancedClassList, Collection, RouterGlobals, Translations};
use Opis\Database\{Database, Connection};
use Opis\Cache\CacheDriver;
use Opis\DataStore\DataStore;
use Opis\Events\EventDispatcher;
use Opis\Routing\RouteCollection;
use Opis\View\Renderer;
use Opis\Utils\SortableList;
use Psr\Log\LoggerInterface;
use Opis\Colibri\Collectors\{
    AssetsHandlerCollector,
    BaseCollector,
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
    ViewCollector,
    ViewEngineCollector
};

class ItemCollector
{
    private bool $collectorsIncluded =  false;
    private Application $app;
    private Container $container;
    /** @var SortableList[] */
    private array $entries = [];
    private array $cache = [];
    private array $collectedEntries = [];
    private array $invertedList = [];

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->setupContainer($this->app->getCollectorList());
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return CacheDriver
     */
    public function getCacheDriver(string $name, bool $fresh = false): CacheDriver
    {
        return $this->collect(CacheCollector::class, $fresh)->getInstance($name);
    }

    /**
     * @param bool $fresh
     * @return Container
     */
    public function getContracts(bool $fresh = false): Container
    {
        return $this->collect(ContractCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCommands(bool $fresh = false): array
    {
        return $this->collect(CommandCollector::class, $fresh)->getEntries();
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return DataStore
     */
    public function getConfigDriver(string $name, bool $fresh = false): DataStore
    {
        return $this->collect(ConfigCollector::class, $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Connection
     */
    public function getConnection(string $name, bool $fresh = false): Connection
    {
        return $this->collect(ConnectionCollector::class, $fresh)->getInstance($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Database
     */
    public function getDatabase(string $name, bool $fresh = false): Database
    {
        return $this->collect(ConnectionCollector::class, $fresh)->database($name);
    }

    /**
     * @param bool $fresh
     * @return EventDispatcher
     */
    public function getEventDispatcher(bool $fresh = false): EventDispatcher
    {
        return $this->collect(EventHandlerCollector::class, $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return LoggerInterface
     */
    public function getLogger(string $name, bool $fresh = false): LoggerInterface
    {
        return $this->collect(LoggerCollector::class, $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return RouteCollection
     */
    public function getRoutes(bool $fresh = false): RouteCollection
    {
        return $this->collect(RouteCollector::class, $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Session
     */
    public function getSession(string $name, bool $fresh = false): Session
    {
        return $this->collect(SessionCollector::class, $fresh)->getSession($name);
    }

    /**
     * @param bool $fresh
     * @return Renderer
     */
    public function getRenderer(bool $fresh = false): Renderer
    {
        return $this->collect(ViewCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return Collection
     */
    public function getViewEngineResolver(bool $fresh = false): Collection
    {
        return $this->collect(ViewEngineCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return Translations
     */
    public function getTranslations(bool $fresh = false)
    {
        return $this->collect(TranslationCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return RouterGlobals
     */
    public function getRouterGlobals(bool $fresh = false): RouterGlobals
    {
        return $this->collect(RouterGlobalsCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return Collection
     */
    public function getAssetHandlers(bool $fresh = false)
    {
        return $this->collect(AssetsHandlerCollector::class, $fresh);
    }

    /**
     * @param bool $fresh
     * @return AdvancedClassList
     */
    public function getTemplateStreamHandlers(bool $fresh = false)
    {
        return $this->collect(TemplateStreamHandlerCollector::class, $fresh);
    }

    public function collect(string $name, bool $fresh = false): object
    {
        $entry = $this->invertedList[strtolower($name)] ?? null;

        if ($entry === null) {
            throw new RuntimeException("Unknown collector ${$name}");
        }

        if (in_array($entry, $this->collectedEntries)) {
            return BaseCollector::getData($this->container->get($entry));
        }

        $this->collectedEntries[] = $entry;

        if ($fresh) {
            unset($this->cache[$entry]);
        }

        if (!isset($this->cache[$entry])) {
            $collectors = $this->app->getCollectorList($fresh);

            if (!isset($collectors[$entry])) {
                throw new RuntimeException("Unknown collector type '$entry'");
            }

            $hit = false;
            $this->cache[$entry] = $this->app->getCache()->load($entry, function ($entryName) use (&$hit) {
                $hit = true;
                $this->includeCollectors();
                /** @var BaseCollector $instance */
                $instance = $this->container->get($entryName);
                /** @var callable $callback */
                foreach ($this->entries[$entryName]->getValues() as $callback) {
                    $callback($instance);
                }
                return BaseCollector::getData($instance);
            });

            if ($hit) {
                $this->app->getEventDispatcher()->emit("system.collect." . $entry);
            }
        }

        array_pop($this->collectedEntries);

        return $this->cache[$entry];
    }

    public function recollect(bool $fresh = true): bool
    {
        if (!$this->app->getCache()->clear()) {
            return false;
        }

        $this->collectorsIncluded = false;
        $list = $this->app->getCollectorList($fresh);

        if ($fresh) {
            $this->cache = [];
            $this->entries = [];
            $this->app->clearCachedObjects();
            $this->setupContainer($list);
        }

        foreach ($list as $entry) {
            $this->collect($entry['class'], $fresh);
        }

        $this->app->getEventDispatcher()->emit("system.collect");

        return true;
    }

    /**
     * Register a new collector
     *
     * @param string $class
     * @param string $description
     * @param array $options
     *
     * @return self
     */
    public function register(string $class, string $description, array $options = []): self
    {
        $name = $this->classToCollectorName($class);

        $this->app->getConfig()->write('collectors.' . $name, [
            'class' => $class,
            'description' => $description,
            'options' => $options,
        ]);

        $this->container->singleton($class);
        $this->container->alias($name, $class);
        $this->invertedList[strtolower($class)] = $name;

        return $this;
    }

    /**
     * Unregister an existing collector
     *
     * @param string $class
     * @return self
     */
    public function unregister(string $class): self
    {
        $name = $this->classToCollectorName($class);
        $this->app->getConfig()->delete('collectors.' . $name);
        unset($this->invertedList[$class]);
        $this->container->alias($name, null);
        $this->container->unbind($class);

        return $this;
    }

    private function classToCollectorName(string $class): string
    {
        return strtolower(str_replace('\\', '-', trim($class, '\\')));
    }

    private function setupContainer(array $collectorList): void
    {
        $inverted = [];
        $container = new Container();

        foreach ($collectorList as $name => $collector) {
            $container->singleton($collector['class']);
            $container->alias($name, $collector['class']);
            $inverted[strtolower($collector['class'])] = $name;
        }

        $this->container = $container;
        $this->invertedList = $inverted;
    }

    private function includeCollectors()
    {
        if ($this->collectorsIncluded) {
            return;
        }

        $this->collectorsIncluded = true;


        $collectorList = $this->app->getCollectorList();
        $invertedList = $this->invertedList;

        foreach (array_keys($collectorList) as $entry) {
            if (!isset($this->entries[$entry])) {
                $this->entries[$entry] = new SortableList();
            }
        }

        foreach ($this->app->getModules() as $module) {
            if (!$module->isEnabled()) {
                continue;
            }
            if (($collector = $module->collector()) === null) {
                continue;
            }

            $instance = $this->container->make($collector);
            
            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf(Collector::class)) {
                continue;
            }

            /** @var Collector $instance */
            $this->doCollect($module, $instance, $reflection, $collectorList, $invertedList);
        }
    }

    private function doCollect(Module $module, Collector $instance, ReflectionClass $reflection, array $collectorList, array $invertedList)
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            $methodName = $method->getShortName();

            if (substr($methodName, 0, 2) === '__') {
                continue;
            }

            $params = $method->getParameters();

            if (empty($params) || !$params[0]->hasType()) {
                continue;
            }

            /** @var \ReflectionNamedType $type */
            $type = $params[0]->getType();
            $type = $type->getName();
            $name = $invertedList[strtolower($type)] ?? null;

            if ($name === null) {
                continue;
            }

            $priority = 0;
            $param = $params[1] ?? null;

            if (isset($param)) {
                if (!$param->isOptional()) {
                    continue;
                }

                if ('priority' === $param->getName() && $param->hasType()) {
                    /** @var $paramType \ReflectionNamedType */
                    $paramType = $param->getType();
                    if ($paramType->getName() === 'int') {
                        try {
                            $priority = (int) $param->getDefaultValue();
                        } catch (ReflectionException $exception) {
                            $priority = 0;
                        }
                    }
                }
            }

            if (isset($collectorList[$name])) {
                $options = $collectorList[$name]['options'] ?? [];
                $options += [
                    'invertedPriority' => true,
                ];
                if ($options['invertedPriority']) {
                    $priority *= -1;
                }
            }

            $callback = function (BaseCollector $collector) use (
                $instance,
                $methodName,
                $module,
                $name,
                $priority
            ) {
                BaseCollector::update($collector, $module, $name, $priority);
                $instance->{$methodName}($collector);
            };

            $this->entries[$name]->addItem($callback, $priority);
        }
    }
}