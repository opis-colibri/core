<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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

use SplQueue, Generator, JsonSerializable, stdClass;
use Opis\Colibri\Http\{Request, Responses\HtmlResponse, Response, Responses\JSONResponse};

class Dispatcher
{
    public function dispatch(Router $router, Request $request): ?Response
    {
        $route = $this->findRoute($router, $request);

        if ($route === null) {
            return null;
        }

        $invoker = $router->resolveInvoker($route, $request);
        $resolver = $invoker->getArgumentResolver();
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

            if (false === $resolver->execute($callback)) {
                return null;
            }
        }

        $list = $route->getMiddleware();

        if (!$list) {
            return $this->handleResult($invoker->invokeAction());
        }

        $queue = new SplQueue();
        $next = function () use ($queue, $invoker, $resolver): Response {
            do {
                if ($queue->isEmpty()) {
                    return $this->handleResult($invoker->invokeAction());
                }
                /** @var Middleware $middleware */
                $middleware = $queue->dequeue();
            } while (!is_callable($middleware));

            return $this->handleResult($resolver->execute($middleware));
        };

        foreach ($list as $item) {
            if (is_subclass_of($item, Middleware::class, true)) {
                $queue->enqueue(new $item($next));
            }
        }

        return $next();
    }

    private function handleResult(mixed $result): Response
    {
        if (!$result instanceof Response) {
            if (is_array($result) || $result instanceof stdClass || $result instanceof JsonSerializable) {
                return new JSONResponse($result);
            }
            return new HtmlResponse($result);
        }
        return $result;
    }

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