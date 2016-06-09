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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Application;
use Opis\Colibri\Collector;
use Opis\Events\EventHandler;
use Opis\Events\RouteCollection;
use Opis\Routing\Pattern;

/**
 * Class EventHandlerCollector
 * @package Opis\Colibri\Collectors
 * @method RouteCollection data()
 */
class EventHandlerCollector extends Collector
{

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new RouteCollection());
    }

    /**
     * Register a new event handler
     *
     * @param   string $event Event name
     * @param   callable $callback A callback that will be executed
     * @param   int $priority Event handler's priority
     *
     * @return  \Opis\Events\EventHandler
     */
    public function handle($event, callable $callback, $priority = 0)
    {
        $handler = new EventHandler(new Pattern($event), $callback);
        $this->dataObject[] = $handler;
        $this->dataObject->sort();
        return $handler->set('priority', $priority);
    }
}
