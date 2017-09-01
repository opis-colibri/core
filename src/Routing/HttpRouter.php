<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Opis\Http\Error\AccessDenied;
use Opis\Http\Error\NotFound;
use Opis\HttpRouting\Context;
use Opis\HttpRouting\Router;
use Opis\Routing\Context as BaseContext;
use Opis\Routing\Router as AliasRouter;
use function Opis\Colibri\Functions\{app, view};

class HttpRouter extends Router
{
    public function __construct()
    {
        parent::__construct(app()->getCollector()->getRoutes());

        $this->getRouteCollection()
            ->notFound(function ($path) {
                return new NotFound(view('error.404', array('path' => $path)));
            })
            ->accessDenied(function ($path) {
                return new AccessDenied(view('error.403', array('path' => $path)));
            });
    }

    /**
     * Route path
     *
     * @param BaseContext|Context $context
     * @return mixed
     */
    public function route(BaseContext $context)
    {
        $this->currentPath = $context;
        $router = new AliasRouter(app()->getCollector()->getRouteAliases());
        $alias = $router->route(new BaseContext($context->path()));

        if ($alias !== null) {
            $context = new Context(
                (string) $alias, $context->domain(), $context->method(), $context->isSecure(), $context->request()
            );
        }

        $result = parent::route($context);

        /** @var \Opis\Http\Request $request */
        $request = $context->request();
        /** @var \Opis\Http\Response $response */
        $response = $request->response();
        $response->body($result);
        $response->send();

        return $result;
    }
}
