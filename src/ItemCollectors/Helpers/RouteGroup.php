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

namespace Opis\Colibri\ItemCollectors\Helpers;

use Opis\Colibri\Routing\HttpRoute;

/**
 *
 * @method RouteGroup mixin(string $name, array $config = null)
 * @method RouteGroup bind(string $name, callable $callback)
 * @method RouteGroup filter(string ...$callbacks)
 * @method RouteGroup implicit(string $name, $value)
 * @method RouteGroup where(string $name, $value)
 * @method RouteGroup whereIn(string $name, string[] $value)
 * @method RouteGroup guard(string ...$callbacks)
 * @method RouteGroup callback(string $name, callable $callback)
 * @method RouteGroup middleware(string ...$middleware)
 * @method RouteGroup domain(string $value)
 * @method RouteGroup method(string ...$value)
 * @method RouteGroup secure(bool $value = true)
 *
 */
class RouteGroup
{
    /** @var  HttpRoute[] */
    protected $routes;

    /**
     * RouteGroup constructor.
     * @param HttpRoute[] $routes
     */
    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    /**
     * @param $name
     * @param $arguments
     * @return self|RouteGroup
     */
    public function __call($name, $arguments)
    {
        foreach ($this->routes as $route) {
            // Signal to route that this values are inherited
            $route->setIsInheriting(true);
            $route->{$name}(...$arguments);
            $route->setIsInheriting(false);
        }

        return $this;
    }
}