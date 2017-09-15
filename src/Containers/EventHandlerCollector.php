<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri\Containers;

use Opis\Colibri\ItemCollector;
use Opis\Events\RouteCollection;
use Opis\Routing\Route;

/**
 * Class EventHandlerCollector
 * @package Opis\Colibri\Containers
 * @method RouteCollection data()
 * @property RouteCollection $dataObject
 */
class EventHandlerCollector extends ItemCollector
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new RouteCollection());
    }

    /**
     * Register a new event handler
     *
     * @param   string $event Event name
     * @param   callable $callback A callback that will be executed
     * @param   int $priority Event handler's priority
     *
     * @return  Route
     */
    public function handle(string $event, callable $callback, int $priority = 0): Route
    {
        $handler = new Route($event, $callback);
        $this->dataObject->addRoute($handler)->sort();
        return $handler->set('priority', $priority);
    }
}
