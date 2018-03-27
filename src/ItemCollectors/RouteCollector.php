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
use Opis\Colibri\Routing\HttpRoute;
use Opis\Colibri\ItemCollectors\Helpers\RouteGroup;
use Opis\HttpRouting\RouteCollection;

/**
 * Class RouteCollector
 *
 * @property RouteCollection $data
 */
class RouteCollector extends ItemCollector
{
    /** @var string */
    protected $prefix = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $factory = function (
            RouteCollection $collection,
            string $id,
            string $pattern,
            callable $action,
            string $name = null
        ) {
            return new HttpRoute($collection, $id, $pattern, $action, $name);
        };
        parent::__construct(new RouteCollection($factory));
    }

    /**
     * @param callable $callback
     * @param string $prefix
     * @return RouteGroup
     */
    public function group(callable $callback, string $prefix = ''): RouteGroup
    {
        $collector = new self();
        $collector->data = $this->data;
        $collector->prefix = $this->prefix . $prefix;


        $old_routes = $collector->data->getRoutes();
        $callback($collector);

        $routes = array_diff_key($collector->data->getRoutes(), $old_routes);
        return new RouteGroup($routes);
    }

    /**
     * Defines a global binding
     *
     * @param   string $name The name of the binding
     * @param   callable $callback A callback that will return the binding's value
     *
     * @return  self   Self reference
     */
    public function bind(string $name, callable $callback): self
    {
        $this->data->bind($name, $callback);
        return $this;
    }

    /**
     * Defines a global callback
     *
     * @param   string $name The name of the callback
     * @param   callable $callback A callback
     *
     * @return  self   Self reference
     */
    public function callback(string $name, callable $callback): self
    {
        $this->data->callback($name, $callback);
        return $this;
    }

    /**
     * Set a global implicit value for a wildcard
     *
     * @param   string $name The name of the wildcard
     * @param   mixed $value The implicit value
     *
     * @return  self   Self reference
     */
    public function implicit(string $name, $value): self
    {
        $this->data->implicit($name, $value);
        return $this;
    }

    /**
     * Set a global placeholder
     *
     * @param   string $name The name of the wildcard
     * @param   string $value A regex expression
     *
     * @return  self   Self reference
     */
    public function placeholder(string $name, string $value): self
    {
        $this->data->placeholder($name, $value);
        return $this;
    }

    /**
     * Define a new route that will intercept  the specified methods
     *
     * @param   string|array $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string|array $method (optional) Request method
     *
     * @return  HttpRoute
     */
    public function __invoke(string $path, callable $action, $method = null): HttpRoute
    {
        $name = null;

        if (is_array($path)) {
            $tmp = reset($path);
            $name = key($path);
            $path = $tmp;
        }

        if ($method === null) {
            $method = 'GET';
        }

        return $this->handle($path, $action, $method, $name);
    }

    /**
     * Defines a new route that will intercept all HTTP requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    public function all(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $name);
    }

    /**
     * Defines a new route that will intercept all GET requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    public function get(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, 'GET', $name);
    }

    /**
     * Defines a new route that will intercept all POST requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string|null $name
     *
     * @return  HttpRoute
     */
    public function post(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, 'POST', $name);
    }

    /**
     * Defines a new route that will intercept all DELETE requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    public function delete(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, 'DELETE', $name);
    }

    /**
     * Defines a new route that will intercept all PUT requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    public function put(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, 'PUT', $name);
    }

    /**
     * Defines a new route that will intercept all PATCH requests
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    public function patch(string $path, callable $action, string $name = null): HttpRoute
    {
        return $this->handle($path, $action, 'PATCH', $name);
    }

    /**
     * Defines a new route
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string|array $method Request's method
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    protected function handle(string $path, callable $action, $method, string $name = null): HttpRoute
    {
        if (!is_array($method)) {
            $method = [$method];
        }
        /** @var HttpRoute $route */
        $route = $this->data->createRoute($this->prefix . $path, $action, $name);
        $route->method(...$method);
        return $route;
    }
}
