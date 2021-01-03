<?php
/* ============================================================================
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

namespace Opis\Colibri\RestAPI;

use Opis\Colibri\Http\Request;
use Opis\Colibri\Routing\{Route, Mixin};
use function Opis\Colibri\controller;

abstract class ControllerResolver extends Mixin
{
    /**
     * @param Route $route
     * @param array|null $config
     *
     * Use $config['no-cors'] = true to disable the CORSMiddleware
     */
    public function __invoke(Route $route, ?array $config)
    {
        // Bind methods

        $class = static::class;

        $route
            // Resolves controller class
            ->bind('controllerClassName', "{$class}::bindControllerClassName")
            // Resolves controller method
            ->bind('controllerMethodName', "{$class}::bindControllerMethodName")
            // Resolves request body
            ->bind('data', "{$class}::bindData")
        ;

        if ($config['no-cors'] ?? false) {
            // Do not add CORSMiddleware
            return;
        }

        // Add cors middleware

        $middleware = $route->getProperties()['middleware'] ?? null;

        if ($middleware) {
            $route->middleware(CORSMiddleware::class, ...$middleware);
        } else {
            $route->middleware(CORSMiddleware::class);
        }
    }

    /**
     * This is a proxy to controller('@controllerClassName', '@controllerMethodName')
     * @return callable
     */
    final public static function controller(): callable
    {
        return controller('@controllerClassName', '@controllerMethodName');
    }

    /**
     * @param Request $request
     * @return object|array|null
     * @internal
     */
    public static function bindData(Request $request)
    {
        $body = $request->getBody();

        if (!$body) {
            return null;
        }

        $body = (string)$body;

        if ($body === '') {
            return null;
        }

        return json_decode($body, false);
    }

    /**
     * @param string|null $controller
     * @return string|null
     * @internal
     */
    public static function bindControllerClassName(?string $controller = null): ?string
    {
        if (!$controller) {
            return null;
        }

        return static::controllers()[$controller] ?? null;
    }

    /**
     * @param Request $request
     * @param string|null $controllerClassName Controller class name
     * @param string|null $action
     * @return string
     * @internal
     */
    public static function bindControllerMethodName(Request $request, ?string $controllerClassName, ?string $action = null): string
    {
        if (!$controllerClassName ||
            !class_exists($controllerClassName) ||
            !is_subclass_of($controllerClassName, RestController::class)) {
            return 'http404';
        }

        $config = $controllerClassName::actions()[$action ?? 'default'] ?? null;

        if (!$config) {
            return 'http404';
        }

        $httpMethod = strtolower($request->getMethod());

        return $config[$httpMethod] ?? 'http405';
    }

    /**
     * List of the controller names and their corresponding RestController class
     *
     * [
     *      'my-controller' => MyClassExtendingRestController::class,
     * ]
     *
     * @return string[]
     */
    abstract protected static function controllers(): array;
}