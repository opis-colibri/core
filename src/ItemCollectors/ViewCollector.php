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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\ItemCollector;
use Opis\Routing\Route;
use Opis\View\RouteCollection;

/**
 * Class ViewCollector
 *
 * @package Opis\Colibri\ItemCollectors
 *
 * @property  RouteCollection  $data
 */
class ViewCollector extends ItemCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new RouteCollection());
    }

    /**
     * Defines a new view route
     *
     * @param   string $pattern View's pattern
     * @param   callable $resolver A callback that will resolve a view route into a path
     *
     * @return  Route
     */
    public function handle(string $pattern, callable $resolver): Route
    {
        $route = new Route($pattern, $resolver);
        $route->set('priority', $this->crtPriority);
        $this->data->addRoute($route)->sort();
        return $route;
    }
}
