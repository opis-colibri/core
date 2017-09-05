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

namespace Opis\Colibri\Collector;

use Opis\Colibri\CollectingContainer;
use Opis\Events\RouteCollection;
use Opis\Routing\Context;
use Opis\Routing\Route;
use Opis\Routing\Router as BaseRouter;

/**
 * Class Router
 * @package Opis\Colibri\Collector
 *
 * @method CollectingContainer route(Context $context)
 */
class Router extends BaseRouter
{
    public function __construct()
    {
        parent::__construct(new RouteCollection(), new Dispatcher());
    }

    public function handle(string $name, callable $callback, int $priority = 0)
    {
        $route = new Route(strtolower($name), $callback);
        $route->set('priority', $priority);
        $this->getRouteCollection()->addRoute($route);
    }
}