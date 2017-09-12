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
use Opis\HttpRouting\Context;
use Opis\HttpRouting\Router;
use Opis\Routing\Context as BaseContext;
use Opis\Routing\Router as AliasRouter;

class HttpRouter extends Router
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        parent::__construct($app->getCollector()->getRoutes(), new Dispatcher($app));
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
        $router = new AliasRouter($this->app->getCollector()->getRouteAliases());
        $alias = $router->route(new BaseContext($context->path()));

        if ($alias !== null) {
            $context = new Context(
                (string) $alias, $context->domain(), $context->method(), $context->isSecure(), $context->request()
            );
        }

        return parent::route($context);
    }
}
