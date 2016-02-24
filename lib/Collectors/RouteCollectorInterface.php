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

/**
 * Collects routes
 */
interface RouteCollectorInterface
{

    /**
     * Defines a global binding
     *
     * @param   string      $name       The name of the binding
     * @param   callable    $callback   A callback that will return the binding's value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    public function bind($name, $callback);

    /**
     * Defines a global filter
     *
     * @param   string      $name       The name of the filter
     * @param   callable    $callback   A callback that will return the filter's value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    public function filter($name, $callback);

    /**
     * Set a global wildcard
     *
     * @param   string  $name   The name of the wildcard
     * @param   string  $value  A regex expression
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    public function wildcard($name, $value);

    /**
     * Set a global implicit value for a wildcard
     *
     * @param   string  $name   The name of the wildcard
     * @param   mixed   $value  The implicit value
     *
     * @return  \Opis\Colibri\RouteCollectorInterface   Self reference
     */
    public function implicit($name, $value);

    /**
     * Defines a new route that will intercept all HTTP requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $action An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function all($path, $action, $name = null);

    /**
     * Defines a new route that will intercept all GET requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $action An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function get($path, $action, $name = null);

    /**
     * Defines a new route that will intercept all POST requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $value  An action that will be executed
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function post($path, $action, $name = null);

    /**
     * Defines a new route that will intercept all DELETE requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $action An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function delete($path, $action, $name = null);

    /**
     * Defines a new route that will intercept all PUT requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $action An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function put($path, $action, $name = null);

    /**
     * Defines a new route that will intercept all PATCH requests
     *
     * @param   string      $path   The path to match
     * @param   callable    $action An action that will be executed
     * @param   string      $name   (optional) Route name
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function patch($path, $action, $name = null);

    /**
     * Define a new route that will intercept  the specified methods
     *
     * @param   string|array    $path   The path to match
     * @param   callable        $action An action that will be executed
     * @param   string|array    $method (optional) Request method
     *
     * @return  \Opis\HttpRouter\Route
     */
    public function __invoke($path, $action, $method = null);
}
