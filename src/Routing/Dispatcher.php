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

namespace Opis\Colibri\Routing;

use Opis\Colibri\Application;
use Opis\Routing\CompiledRoute;
use Opis\Routing\Context;
use Opis\Routing\DispatcherTrait;
use Opis\Routing\IDispatcher;
use Opis\Routing\Router;
use function Opis\Colibri\Functions\{
    notFound
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
     * @param Router $router
     * @param Context $context
     * @return mixed
     */
    public function dispatch(Router $router, Context $context)
    {
        $this->router = $router;
        $this->context = $context;

        /** @var HttpRoute $route */
        $route = $this->findRoute();

        if ($route === null) {
            return notFound();
        }

        $compiled = new CompiledRoute($context, $route, $this->getExtraVariables());

        $callbacks = $route->getCallbacks();

        foreach ($route->get('guard', []) as $guard) {
            if (isset($callbacks[$guard])) {
                $callback = $callbacks[$guard];
                $args = $compiled->getArguments($callback);
                if (false === $callback(...$args)) {
                    return notFound();
                }
            }
        }

        $list = $route->get('middleware', []);

        if (empty($list)) {
            return $compiled->invokeAction();
        }

        $queue = new \SplQueue();
        $collectedMidleware = $this->app->getCollector()->getMiddleware()->getList();

        foreach ($list as $item) {
            if (isset($collectedMidleware[$item])) {
                $class = $collectedMidleware[$item];
                $queue->enqueue(new $class($queue, $compiled));
            } elseif (is_subclass_of($item, Middleware::class, true)) {
                $queue->enqueue(new $item($queue, $compiled));
            }
        }

        do {
            if ($queue->isEmpty()) {
                return $compiled->invokeAction();
            }
            /** @var Middleware $middleware */
            $middleware = $queue->dequeue();
        } while (!is_callable($middleware));

        $args = $compiled->getArguments($middleware);
        return $middleware(...$args);
    }
}