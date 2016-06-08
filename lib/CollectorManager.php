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
 * @method  CollectorEntry  getRoutes($fresh = false)
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
     * Get collector
     *
     * @param $name
     * @param $arguments
     * @throws RuntimeException
     * @return Collectors\AbstractCollector
     */
    public function __call($name, $arguments)
    {
        $entry = strtolower(substr($name, 3));

        if (!isset($this->collectorList[$entry])) {
            throw new RuntimeException("Invalid method `$name`");
        }

        $fresh = isset($arguments[0]) ? (bool) $arguments[0] : false;

        return $this->collect($entry, $fresh);
    }

    /**
     * @param $entry
     * @param bool $fresh
     * @return Collectors\AbstractCollector
     */
    public function collect($entry, $fresh = false)
    {
        if($fresh) {
            unset($this->cache[$entry]);
        }

        if(!isset($this->cache[$entry])) {
            $hit = false;
            $self = $this;
            $this->cache[$entry] = $this->app->cache('app')->load($entry, function ($entry) use ($self, &$hit) {
                $hit = true;
                $self->includeCollectors();
                $instance = $self->container->make($self->collectorList[$entry]);
                return $self->collectorTarget->dispatch(new CollectorEntry($entry, $instance))->data();
            });

            if ($hit) {
                $this->emit('system.collect.' . strtolower($entry));
            }
        }

        return $this->cache[$entry];
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

            $instance = $this->make($module->collector());

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
