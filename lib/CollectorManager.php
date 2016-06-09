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

use Doctrine\Common\Annotations\AnnotationReader;
use Opis\Container\Container;
use Opis\Colibri\Annotations\Collector as CollectorAnnotation;
use RuntimeException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Description of CollectorManager
 *
 * @author mari
 *
 */
class CollectorManager
{
    /** @var    Application */
    protected $app;

    /** @var array */
    protected $cache = array();

    /** @var array */
    protected $collectors = array();

    /** @var   Container */
    protected $container;

    /** @var  array|null */
    protected $collectorList;

    /** @var  CollectorTarget */
    protected $collectorTarget;

    /** @var    boolean */
    protected $collectorsIncluded = false;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->container = $container = new Container();
        $this->collectorTarget =new CollectorTarget($app);

        $default = require __DIR__ . '../bin/collectors.php';
        $this->collectorList = $this->app->config('app')->read('collectors', array()) + $default;

        foreach ($this->collectorList as $name => $collector) {
            $container->singleton($collector['class']);
            $container->alias($collector['class'], $name);
        }
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \Opis\Cache\StorageInterface
     */
    public function getCacheStorage($name, $fresh = false)
    {
        return $this->collect('CacheStorages', $fresh)->get($this->app, $name);
    }

    /**
     * @param bool $fresh
     * @return \Opis\Colibri\Container
     */
    public function getContracts($fresh = false)
    {
        return $this->collect('Contracts', $fresh);
    }


    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCommands($fresh = false)
    {
        return $this->collect('Commands', $fresh)->getList();
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \Opis\Config\StorageInterface
     */
    public function getConfigStorage($name, $fresh = false)
    {
        return $this->collect('ConfigStorages', $fresh)->get($this->app, $name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \Opis\Database\Connection
     */
    public function getConnection($name, $fresh = false)
    {
        return $this->collect('Connections', $fresh)->get($name);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \Opis\Database\Database
     */
    public function getDatabase($name, $fresh = false)
    {
        return $this->collect('Connections', $fresh)->database($name);
    }


    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getCoreMethods($fresh = false)
    {
        return $this->collect('CoreMethods', $fresh)->getList();
    }

    /**
     * @param bool $fresh
     * @return \Opis\HttpRouting\DispatcherResolver
     */
    public function getDispatcherResolver($fresh = false)
    {
        return $this->collect('Dispatchers', $fresh);
    }

    /**
     * @param bool $fresh
     * @return \Opis\Events\RouteCollection
     */
    public function getEventHandlers($fresh = false)
    {
        return $this->collect('EventHandlers', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \Psr\Log\LoggerInterface
     */
    public function getLogger($name, $fresh = false)
    {
        return $this->collect('Loggers', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return \Opis\Routing\Collections\RouteCollection
     */
    public function getRouteAliases($fresh = false)
    {
        return $this->collect('RouteAliases', $fresh);
    }

    /**
     * @param bool $fresh
     * @return HttpRouteCollection
     */
    public function getRoutes($fresh = false)
    {
        return $this->collect('Routes', $fresh);
    }

    /**
     * @param string $name
     * @param bool $fresh
     * @return \SessionHandlerInterface
     */
    public function getSessionStorage($name, $fresh = false)
    {
        return $this->collect('SessionStorages', $fresh)->get($name);
    }

    /**
     * @param bool $fresh
     * @return callable[]
     */
    public function getValidators($fresh = false)
    {
        return $this->collect('Validators', $fresh)->getList();
    }

    /**
     * @param bool $fresh
     * @return array
     */
    public function getVariables($fresh = false)
    {
        return $this->collect('Variables', $fresh)->getList();
    }

    /**
     * @param bool $fresh
     * @return \Opis\View\RouteCollection
     */
    public function getViews($fresh = false)
    {
        return $this->collect('Views', $fresh);
    }

    /**
     * @param bool $fresh
     * @return \Opis\View\EngineResolver
     */
    public function getViewEngineResolver($fresh = false)
    {
        return $this->collect('ViewEngines', $fresh);
    }

    /**
     * @param bool $fresh
     * @return mixed
     */
    public function getTranslations($fresh = true)
    {
        return $this->collect('Translations', $fresh);
    }

    /**
     * @param string $type
     * @param bool   $fresh
     * @return mixed
     */
    public function collect($type, $fresh = false)
    {
        $entry = strtolower($type);

        if($fresh) {
            unset($this->cache[$entry]);
        }

        if(!isset($this->cache[$entry])) {
            $hit = false;
            $self = $this;
            if (!isset($this->collectorList[$entry])) {
                throw new RuntimeException("Unknown collector type `$type`");
            }
            $this->cache[$entry] = $this->app->cache('app')->load($entry, function ($entry) use ($self, &$hit) {
                $hit = true;
                $self->includeCollectors();
                $instance = $self->container->make($self->collectorList[$entry]);
                return $self->collectorTarget->dispatch(new CollectorEntry($entry, $instance))->data();
            });

            if ($hit) {
                $this->app->emit('system.collect.' . $entry);
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
        if (!$this->app->cache('app')->clear()) {
            return false;
        }

        $this->collectorsIncluded = false;

        foreach (array_keys($this->app->config('app')->read('collectors')) as $entry) {
            $this->collect($entry, $fresh);
        }

        $this->app->emit('system.collect');

        return true;
    }

    /**
     * Register a new collector
     *
     * @param string    $name
     * @param string    $class
     * @param string    $description
     */
    public function register($name, $class, $description)
    {
        $this->app->config('app')->write('collectors.' . $name, array(
            'class' => $class,
            'description' => $description,
        ));

        $this->container->singleton($class);
        $this->container->alias($class, $name);
    }

    /**
     * Unregister an existing collector
     *
     * @param string    $name
     */
    public function unregister($name)
    {
        $this->app->config('app')->delete('collectors.' . $name);
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

        foreach ($this->app->getModules() as $module) {

            if (isset($this->collectors[$module->name()]) || !$module->isEnabled()) {
                continue;
            }

            $this->collectors[$module->name()] = true;

            if ($module->collector() === null) {
                continue;
            }

            $instance = $this->app->make($module->collector());

            $reflection = new ReflectionClass($instance);

            if (!$reflection->isSubclassOf(ModuleCollector::class)) {
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

                $annotation = $reader->getMethodAnnotation($method, CollectorAnnotation::class);

                if ($annotation == null) {
                    $annotation = new CollectorAnnotation();
                }

                if ($annotation->name === null) {
                    $annotation->name = $name;
                }

                $callback = function ($collector, $app) use ($instance, $name) {
                    $instance->{$name}($collector, $app);
                };

                $this->collectorTarget->handle($annotation->name, $callback, $annotation->priority);
            }
        }
    }

}
