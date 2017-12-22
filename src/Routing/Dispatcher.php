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
use function Opis\Colibri\Functions\view;
use Opis\Colibri\HttpResponse\AccessDenied;
use Opis\Colibri\HttpResponse\PageNotFound;
use Opis\Http\Response;
use Opis\HttpRouting\{Context, HttpError, Dispatcher as BaseDispatcher};
use Opis\Routing\Context as BaseContext;
use Opis\Routing\Router as BaseRouter;

/**
 * Class Dispatcher
 * @package Opis\Colibri\Routing
 * @property HttpRoute $route
 */
class Dispatcher extends BaseDispatcher
{
    /** @var  Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param BaseRouter|HttpRouter $router
     * @param BaseContext|Context $context
     * @return mixed
     */
    public function dispatch(BaseRouter $router, BaseContext $context)
    {
        $content = parent::dispatch($router, $context);

        if($this->route === null){
            return $content;
        }

        if(!empty($interceptor = (string) $this->route->get('responseInterceptor'))){
            /** @var ResponseInterceptor $interceptor */
            if(false !== $interceptor = $this->app->getCollector()->getResponseInterceptors()->get($interceptor)){
                $content = $interceptor->handle($content, $this->route, $context->request());
            }
        }

        return $content;
    }

    /**
     * @param Context $context
     * @param HttpError $error
     * @return Response
     */
    protected function getErrorResponse(Context $context, HttpError $error)
    {
        switch ($error->getCode()){
            case 404:
                return new PageNotFound(view('error.404', ['path' => $context->path()]));
            case 403:
                return new AccessDenied(view('error.403', ['path' => $context->path()]));
        }

        return (new Response($error->getBody()))->setStatusCode($error->getCode())->addHeaders($error->getHeaders());
    }
}