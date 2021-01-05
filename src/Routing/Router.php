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

use ArrayAccess, ArrayObject;
use Opis\Colibri\Application;
use Opis\Colibri\Collectors\RouteCollector;
use Opis\Colibri\Http\{Request, Response};
use Opis\Colibri\Routing\Filters\{RequestFilter, UserFilter};
use function Opis\Colibri\httpError;

class Router
{
    private Application $app;
    private RouteCollection $routes;

    /** @var Filter[] */
    private array $filters;

    private Dispatcher $dispatcher;
    private ArrayAccess $global;
    private array $compacted = [];

    /**
     * Router constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->routes = $app->getCollector()->collect(RouteCollector::class);
        $this->dispatcher = new Dispatcher();
        $this->global = new ArrayObject([
            'app' => $app,
            'lang' => $app->getTranslator()->getDefaultLanguage(),
        ]);
        $this->filters = [new RequestFilter(), new UserFilter()];
    }

    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Get the route collection
     *
     * @return  RouteCollection
     */
    public function getRouteCollection(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * @return Filter[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Get global values
     *
     * @return  ArrayAccess
     */
    public function getGlobalValues(): ArrayAccess
    {
        return $this->global;
    }

    /**
     * Get the dispatcher resolver
     *
     * @return Dispatcher
     */
    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    public function resolveInvoker(Route $route, Request $request): RouteInvoker
    {
        $cid = spl_object_hash($request);
        $rid = spl_object_hash($route);

        if (!isset($this->compacted[$cid][$rid])) {
            return $this->compacted[$cid][$rid] = new RouteInvoker($this, $route, $request);
        }

        return $this->compacted[$cid][$rid];
    }

    public function route(Request $request): Response
    {
        $this->global['request'] = $request;

        $response = $this->getDispatcher()->dispatch($this, $request);

        unset($this->global['request']);

        if ($response === null) {
            $response = httpError(404);
        }

        // Handle cookies

        $cookies = $this->app->getSessionCookieContainer()->getAddedCookies();

        if (!$cookies) {
            return $response;
        }

        return $response->modify(static function (Response $response) use ($cookies) {
            foreach ($cookies as $cookie) {
                $response->setCookie(
                    $cookie['name'],
                    $cookie['value'],
                    $cookie['expires'],
                    $cookie['path'],
                    $cookie['domain'],
                    $cookie['secure'],
                    $cookie['httponly'],
                    $cookie['samesite'],
                );
            }
        });
    }
}
