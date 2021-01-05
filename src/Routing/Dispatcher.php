<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\Routing;

use SplQueue;
use Generator;
use Opis\Colibri\Http\{Request, Responses\HtmlResponse, Response};
use function Opis\Colibri\httpError;

class Dispatcher
{
    /** @var callable|null */
    private $customHttpError;

    public function __construct(?callable $httpError = null)
    {
        $this->customHttpError = $httpError;
    }

    public function dispatch(Router $router, Request $request): Response
    {
        $route = $this->findRoute($router, $request);

        if ($route === null) {
            return $this->httpError(404);
        }

        $invoker = $router->resolveInvoker($route, $request);
        $guards = $route->getRouteCollection()->getGuards();

        /**
         * @var string $name
         * @var callable|null $callback
         */
        foreach ($route->getGuards() as $name => $callback) {
            if ($callback === null) {
                if (!isset($guards[$name])) {
                    continue;
                }
                $callback = $guards[$name];
            }

            $args = $invoker->getArgumentResolver()->resolve($callback);

            if (false === $callback(...$args)) {
                return $this->httpError(404);
            }
        }

        $list = $route->getProperties()['middleware'] ?? [];

        if (empty($list)) {
            $result = $invoker->invokeAction();
            if (!$result instanceof Response) {
                $result = new HtmlResponse($result);
            }
            return $result;
        }

        $queue = new SplQueue();
        $next = static function () use ($queue, $invoker) {
            do {
                if ($queue->isEmpty()) {
                    $result = $invoker->invokeAction();
                    if (!$result instanceof Response) {
                        $result = new HtmlResponse($result);
                    }
                    return $result;
                }
                /** @var Middleware $middleware */
                $middleware = $queue->dequeue();
            } while (!is_callable($middleware));

            $args = $invoker->getArgumentResolver()->resolve($middleware);
            $result = $middleware(...$args);
            if (!$result instanceof Response) {
                $result = new HtmlResponse($result);
            }
            return $result;
        };

        foreach ($list as $item) {
            if (is_subclass_of($item, Middleware::class, true)) {
                $queue->enqueue(new $item($next));
            }
        }

        return $next();
    }

    /**
     * Http error
     * @param int $code
     * @return Response
     */
    private function httpError(int $code): Response
    {
        return $this->customHttpError ? ($this->customHttpError)($code) : httpError($code);
    }

    /**
     * @param Router $router
     * @param Request $request
     * @return null|Route
     */
    private function findRoute(Router $router, Request $request): ?Route
    {
        $global = $router->getGlobalValues();
        $global['router'] = $router;
        /** @var Route $route */
        foreach ($this->match($router, $request->getUri()->path() ?? '') as $route) {
            $global['route'] = $route;
            if (!$this->filter($router, $route, $request)) {
                continue;
            }
            return $route;
        }
        $global['route'] = null;
        return null;
    }

    /**
     * @param Router $router
     * @param string $path
     * @return Generator
     */
    private function match(Router $router, string $path): Generator
    {
        $routes = $router->getRouteCollection();
        $routes->sort();

        foreach ($routes->getRegexPatterns() as $routeID => $pattern) {
            if (preg_match($pattern, $path)) {
                yield $routes->getRoute($routeID);
            }
        }
    }

    /**
     * @param Router $router
     * @param Route $route
     * @param Request $request
     * @return bool
     */
    private function filter(Router $router, Route $route, Request $request): bool
    {
        foreach ($router->getFilters() as $filter) {
            if (!$filter->filter($router, $route, $request)) {
                return false;
            }
        }

        return true;
    }
}