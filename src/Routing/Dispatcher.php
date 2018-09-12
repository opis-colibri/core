<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use Opis\Colibri\Application;
use Opis\Http\{
    Responses\HtmlResponse, Response
};
use Opis\Routing\{
    DispatcherTrait, IDispatcher, Router as BaseRouter
};
use Opis\HttpRouting\Router;
use function Opis\Colibri\Functions\{
    httpError
};

class Dispatcher implements IDispatcher
{
    use DispatcherTrait;

    /** @var Application */
    private $app;

    /**
     * Dispatcher constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param Router|BaseRouter $router
     * @return mixed
     */
    public function dispatch(BaseRouter $router)
    {
        /** @var HttpRoute $route */
        $route = $this->findRoute($router);

        if ($route === null) {
            return httpError(404);
        }

        $callbacks = $route->getCallbacks();
        $invoker = $router->resolveInvoker($route);

        foreach ($route->get('guard', []) as $guard) {
            if (isset($callbacks[$guard])) {
                $callback = $callbacks[$guard];
                $args = $invoker->getArgumentResolver()->resolve($callback);
                if (false === $callback(...$args)) {
                    return httpError(404);
                }
            }
        }

        $list = $route->get('middleware', []);

        if (empty($list)) {
            $result = $invoker->invokeAction();
            if (!$result instanceof Response) {
                $result = new HtmlResponse($result);
            }
            return $result;
        }

        $queue = new \SplQueue();
        $collectedMiddleware = $this->app->getCollector()->getMiddleware()->getList();
        $next = function () use ($queue, $invoker) {
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
            if (isset($collectedMiddleware[$item])) {
                $class = $collectedMiddleware[$item];
                $queue->enqueue(new $class($next));
            } elseif (is_subclass_of($item, Middleware::class, true)) {
                $queue->enqueue(new $item($next));
            }
        }

        return $next();
    }
}