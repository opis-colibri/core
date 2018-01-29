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
use Opis\Colibri\HttpResponse\MethodNotAllowed;
use Opis\Http\Request;
use Opis\Http\Response;
use Opis\Routing\DispatcherTrait;
use Opis\Routing\IDispatcher;
use Opis\Routing\Router as BaseRouter;
use Opis\HttpRouting\Router;
use function Opis\Colibri\Functions\{
    logo, notFound, view
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
     * @throws \Exception
     */
    public function dispatch(BaseRouter $router)
    {
        /** @var HttpRoute $route */
        $route = $this->findRoute($router);

        if ($route === null) {
            return notFound();
        }

        /** @var Request $request */
        $request = $router->getContext()->data();

        if (!in_array($request->method(), $route->get('method', ['GET']))) {
            return new MethodNotAllowed(view('error.405', [
                'status' => 405,
                'message' => 'Method not allowed',
                'path' => $request->path(),
                'logo' => logo(),
            ]));
        }

        $callbacks = $route->getCallbacks();
        $compacted = $router->compact($route);


        foreach ($route->get('guard', []) as $guard) {
            if (isset($callbacks[$guard])) {
                $callback = $callbacks[$guard];
                $args = $compacted->getArguments($callback);
                if (false === $callback(...$args)) {
                    return notFound();
                }
            }
        }

        $list = $route->get('middleware', []);


        if (!empty($list)) {
            $result = $compacted->invokeAction();
            if (!$result instanceof Response) {
                $result = new Response($result);
            }
            return $result;
        }

        $queue = new \SplQueue();
        $collectedMiddleware = $this->app->getCollector()->getMiddleware()->getList();
        $next = function () use (&$next, $queue, $compacted) {
            do {
                if ($queue->isEmpty()) {
                    $result = $compacted->invokeAction();
                    if (!$result instanceof Response) {
                        $result = new Response($result);
                    }
                    return $result;
                }
                /** @var Middleware $middleware */
                $middleware = $queue->dequeue();
            } while (!is_callable($middleware));

            $args = $compacted->getArguments($middleware);
            $args[] = $next;
            $result = $middleware(...$args);
            if (!$result instanceof Response) {
                $result = new Response($result);
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