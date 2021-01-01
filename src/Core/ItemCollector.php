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

use ReflectionObject;
use ReflectionMethod;
use RuntimeException;
use Opis\Colibri\{Application, Collector, Internal\Collector as InternalCollector, Attributes\Priority};
use Opis\Utils\SortableList;
use Opis\Colibri\Collectors\BaseCollector;

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
     *
     * @return self
     */
    public function register(string $class, string $description): self
    {
        $name = $this->classToCollectorName($class);

        $this->app->getConfig()->write('collectors.' . $name, [
            'class' => $class,
            'description' => $description,
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

    private function includeCollectors(): void
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

        $this->collectFromCore($collectorList, $invertedList);

        foreach ($this->app->getModules() as $module) {
            if (!$module->isEnabled()) {
                continue;
            }
            $this->doCollect($module, $module->collector(), $collectorList, $invertedList);
        }
    }

    private function collectFromCore(array $collectorList, array $invertedList): void
    {
        $fakeModule = $this->app->getModule('opis-colibri/core');

        $instance = new InternalCollector();

        $this->doCollect($fakeModule, $instance, $collectorList, $invertedList);
    }

    private function doCollect(Module $module, Collector $instance,
                               array $collectorList, array $invertedList): void
    {
        $reflection = new ReflectionObject($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            $methodName = $method->getShortName();

            if (substr($methodName, 0, 2) === '__') {
                continue;
            }

            $params = $method->getParameters();

            if (empty($params) || count($params) > 1 || !$params[0]->hasType()) {
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

            $attributes = $method->getAttributes(Priority::class);

            if (!empty($attributes)) {
                $priority = end($attributes)->getArguments()[0];
            }

            $callback = static function (BaseCollector $collector) use (
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