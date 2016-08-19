<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Doctrine\Common\Annotations\AnnotationReader;
use Opis\Cache\StorageInterface as CacheStorageInterface;
use Opis\Colibri\Annotations\Collector as CollectorAnnotation;
use Opis\Config\ConfigInterface;
use Opis\Database\Connection;
use Opis\Database\Database;
use Opis\Events\RouteCollection as EventsRouteCollection;
use Opis\HttpRouting\RouteCollection as HttpRouteCollection;
use Opis\Routing\DispatcherCollection;
use Opis\Routing\RouteCollection as AliasRouteCollection;
use Opis\View\RouteCollection as ViewRouteCollection;
use Opis\View\EngineResolver;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use function Opis\Colibri\Helpers\{app, cache, emit, config};

/**
 * Description of CollectorManager
 *
 * @author mari
 *
 */
class CollectorManager
{
    /** @var array */
    protected $cache = array();

    /** @var   Container */
    protected $container;

    /** @var  CollectorTarget */
    protected $collectorTarget;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->container = $container = new Container();
        $this->collectorTarget = new CollectorTarget();

        foreach (app()->getCollectorList() as $name => $collector) {
            $container->alias($collector['class'], $name);
            $container->singleton($collector['class']);
        }
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return CacheStorageInterface
     */
    public function getCacheStorages(string $name, bool $fresh = false): CacheStorageInterface
    {
        return $this->collect('CacheStorages', $fresh)->gat($name);
    }

    /**
     * @param bool $fresh
     * @return Container
     */
    public function getContracts(bool $fresh = false): Container
    {
        return $this->collect('Contracts', $fresh);
    }


    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCommands(bool $fresh = false): array
    {
        return $this->collect('Commands', $fresh)->getList();
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return ConfigInterface
     */
    public function getConfigDriver(string $name, bool $fresh = false): ConfigInterface
    {
        return $this->collect('ConfigDrivers', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Connection
     */
    public function getConnection(string $name, bool $fresh = false): Connection
    {
        return $this->collect('Connections', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return Database
     */
    public function getDatabase(string $name, bool $fresh = false): Database
    {
        return $this->collect('Connections', $fresh)->database($name);
    }

    /**
     * @param bool $fresh
     * @return DispatcherCollection
     */
    public function getDispatcherCollection(bool $fresh = false): DispatcherCollection
    {
        return $this->collect('Dispatchers', $fresh);
    }

    /**
     * @param bool $fresh
     * @return EventsRouteCollection
     */
    public function getEventHandlers(bool $fresh = false): EventsRouteCollection
    {
        return $this->collect('EventHandlers', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return LoggerInterface
     */
    public function getLogger(string $name, bool $fresh = false): LoggerInterface
    {
        return $this->collect('Loggers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return AliasRouteCollection
     */
    public function getRouteAliases(bool $fresh = false): AliasRouteCollection
    {
        return $this->collect('RouteAliases', $fresh);
    }

    /**
     * @param bool $fresh
     * @return HttpRouteCollection
     */
    public function getRoutes(bool $fresh = false): HttpRouteCollection
    {
        return $this->collect('Routes', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \SessionHandlerInterface
     */
    public function getSessionStorage(string $name, bool $fresh = false): \SessionHandlerInterface
    {
        return $this->collect('SessionStorages', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return string[]
     */
    public function getValidators(bool $fresh = false): array
    {
        return $this->collect('Validators', $fresh);
    }

    /**
     * @param bool $fresh
     * @return array
     */
    public function getVariables(bool $fresh = false): array
    {
        return $this->collect('Variables', $fresh)->getList();
    }

    /**
     * @param bool $fresh
     * @return ViewRouteCollection
     */
    public function getViews(bool $fresh = false): ViewRouteCollection
    {
        return $this->collect('Views', $fresh);
    }

    /**
     * @param bool $fresh
     * @return EngineResolver
     */
    public function getViewEngineResolver(bool $fresh = false): EngineResolver
    {
        return $this->collect('ViewEngines', $fresh);
    }

    /**
     * @param bool $fresh
     * @return mixed
     */
    public function getTranslations(bool $fresh = true)
    {
        return $this->collect('Translations', $fresh);
    }

    /**
     * @param string $type
     * @param bool $fresh
     * @return mixed
     */
    public function collect(string $type, bool $fresh = false)
    {
        $entry = strtolower($type);

        if ($fresh) {
            unset($this->cache[$entry]);
        }

        if (!isset($this->cache[$entry])) {

            $collectors = app()->getCollectorList($fresh);

            if (!isset($collectors[$entry])) {
                throw new RuntimeException("Unknown collector type '$entry'");
            }

            $hit = false;
            $this->cache[$entry] = cache()->load($entry, function ($entry) use (&$hit) {
                $hit = true;
                $this->includeCollectors();
                $instance = $this->container->make($entry);
                return $this->collectorTarget->dispatch(new CollectorEntry($entry, $instance))->getCollector()->data();
            });

            if ($hit) {
                emit('system.collect.' . $entry);
            }
        }

        return $this->cache[$entry];
    }

    /**
     * Recollect all items
     *
     * @param bool $fresh (optional)
     *
     * @return bool
     */
    public function recollect(bool $fresh = true): bool
    {
        if (!cache()->clear()) {
            return false;
        }

        $this->collectorsIncluded = false;

        foreach (array_keys(app()->getCollectorList($fresh)) as $entry) {
            $this->collect($entry, $fresh);
        }

        emit('system.collect');

        return true;
    }

    /**
     * Register a new collector
     *
     * @param string $name
     * @param string $class
     * @param string $description
     */
    public function register(string $name, string $class, string $description)
    {
        $name = strtolower($name);

        config()->write('collectors.' . $name, array(
            'class' => $class,
            'description' => $description,
        ));
        $this->container->singleton($class);
        $this->container->alias($class, $name);
    }

    /**
     * Unregister an existing collector
     *
     * @param string $name
     */
    public function unregister(string $name)
    {
        config()->delete('collectors.' . strtolower($name));
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

        foreach (app()->getModules() as $module) {

            if (!$module->isEnabled() || $module->collector() === false) {
                continue;
            }

            $instance = $this->container->make($module->collector());

            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf(ModuleCollector::class)) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

                $name = $method->getShortName();

                if (substr($name, 0, 2) === '__') {
                    if ($name === '__invoke') {
                        $instance($this->collectorTarget);
                    }
                    continue;
                }

                $annotation = $reader->getMethodAnnotation($method, CollectorAnnotation::class);

                if ($annotation == null) {
                    $annotation = new CollectorAnnotation();
                }

                if ($annotation->name === null) {
                    $annotation->name = $name;
                }

                $callback = function ($collector) use ($instance, $name) {
                    $instance->{$name}($collector);
                };

                $this->collectorTarget->handle(strtolower($annotation->name), $callback, $annotation->priority);
            }
        }
    }

}
