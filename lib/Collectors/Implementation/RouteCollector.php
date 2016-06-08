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

namespace Opis\Colibri\Collectors\Implementation;

use Opis\Colibri\HttpRoute;
use Opis\Colibri\Application;
use Opis\Colibri\HttpRouteCollection;
use Opis\Colibri\Collectors\AbstractCollector;
use Opis\Colibri\Collectors\RouteCollectorInterface;

class RouteCollector extends AbstractCollector implements RouteCollectorInterface
{

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new HttpRouteCollection());
    }

    /**
     * Defines a global binding
     *
     * @param   string $name The name of the binding
     * @param   callable $callback A callback that will return the binding's value
     *
     * @return  self   Self reference
     */
    public function bind($name, $callback)
    {
        $this->dataObject->bind($name, $callback);
        return $this;
    }

    /**
     * Defines a global filter
     *
     * @param   string $name The name of the filter
     * @param   callable $callback A callback that will return the filter's value
     *
     * @return  self   Self reference
     */
    public function filter($name, $callback)
    {
        $this->dataObject->filter($name, $callback);
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
    public function implicit($name, $value)
    {
        $this->dataObject->implicit($name, $value);
        return $this;
    }

    /**
     * Set a global wildcard
     *
     * @param   string $name The name of the wildcard
     * @param   string $value A regex expression
     *
     * @return  self   Self reference
     */
    public function wildcard($name, $value)
    {
        $this->dataObject->wildcard($name, $value);
        return $this;
    }

    /**
     * Defines a new route
     *
     * @param   string $path The path to match
     * @param   callable $action An action that will be executed
     * @param   string $name (optional) Route name
     *
     * @return  HttpRoute
     */
    protected function handle($path, $action, $name = null)
    {
        $route = new HttpRoute($path, $action);
        $this->dataObject[$name] = $route;
        return $route;
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
    public function __invoke($path, $action, $method = null)
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

        return $this->handle($path, $action, $name)->method($method);
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
    public function all($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method(array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'));
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
    public function get($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('GET');
    }

    /**
     * Defines a new route that will intercept all POST requests
     *
     * @param   string $path The path to match
     * @param   callable $value An action that will be executed
     *
     * @return  HttpRoute
     */
    public function post($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('POST');
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
    public function delete($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('DELETE');
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
    public function put($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('PUT');
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
    public function patch($path, $action, $name = null)
    {
        return $this->handle($path, $action, $name)->method('PATCH');
    }
}
