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

use Opis\Events\EventTarget;
use InvalidArgumentException;
use Opis\Events\RouteCollection;
use Opis\Events\Event as BaseEvent;

class Collector extends EventTarget
{
    /** @var    \Opis\Colibri\Application */
    protected $app;

    /** @var \Opis\Colibri\Container */
    protected $container;

    public function __construct(Application $app, RouteCollection $collection = null)
    {
        $this->app = $app;
        parent::__construct($collection);
    }

    /**
     * Get the container
     *
     * @return  \Opis\Colibri\Container
     */
    public function container()
    {
        if ($this->container === null) {
            $app = $this->app;
            $container = new Container();

            foreach ($app->config()->read('collectors') as $type => $collector) {
                $container->alias($collector['interface'], $type);
                $container->singleton($collector['interface'], $collector['class']);
            }

            $this->container = $container;
        }

        return $this->container;
    }

    /**
     * Dispatch an event
     *
     * @param   \Opis\Colibri\CollectorEntry $event
     *
     * @return  \Opis\Colibri\Collectors\AbstractCollector
     *
     * @throws InvalidArgumentException
     */
    public function dispatch(BaseEvent $event)
    {
        if (!$event instanceof CollectorEntry) {
            throw new InvalidArgumentException('Invalid event type. Expected \Opis\Colibri\CollectorEntry');
        }

        $this->collection->sort();
        $handlers = $this->router->route($event);
        $collector = $event->getCollector();

        foreach ($handlers as $callback) {
            $callback($collector, $this->app);
        }

        return $collector;
    }

    /**
     * Collect items
     *
     * @param   string $type
     *
     * @return  @return  \Opis\Colibri\Collectors\AbstractCollector
     *
     * @throws \Exception
     */
    public function collect($type)
    {
        if (null === $this->app->config()->read("collectors.$type")) {
            throw new \Exception('Unknown collector type "' . $type . '"');
        }

        $collector = $this->container()->make($type, array($this->app));

        return $this->dispatch(new CollectorEntry($type, $collector));
    }
}
