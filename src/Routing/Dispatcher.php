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
        $logo = function (){
            return 'data:image/svg+xml;base64, '
                . base64_encode(file_get_contents(__DIR__ . '/../../logo.svg'));
        };

        switch ($error->getCode()){
            case 404:
                return new PageNotFound(view('error.404', [
                    'status' => 404,
                    'message' => 'Not Found',
                    'path' => $context->path(),
                    'context' => $context,
                    'logo' => $logo
                ]));
            case 403:
                return new AccessDenied(view('error.403', [
                    'status' => 403,
                    'message' => 'Forbidden',
                    'path' => $context->path(),
                    'context' => $context,
                    'logo' => $logo,
                ]));
            case 405:
                return (new Response(view('error.405', [
                    'status' => 405,
                    'message' => 'Method Not Allowed',
                    'path' => $context->path(),
                    'context' => $context,
                    'logo' => $logo,
                ])))
                    ->setStatusCode(405)
                    ->addHeaders($error->getHeaders())
                    ->addHeader('Allow', implode(', ', $this->route->get('method', [])));
            case 500:
                return (new Response(view('error.500', [
                    'status' => 500,
                    'message' => 'Internal Server Error',
                    'path' => $context->path(),
                    'context' => $context,
                    'logo' => $logo,
                ])))
                    ->setStatusCode(500)
                    ->addHeaders($error->getHeaders());
            case 503:
                return (new Response(view('error.503', [
                    'status' => 503,
                    'message' => 'Service Unavailable',
                    'path' => $context->path(),
                    'context' => $context,
                    'logo' => $logo,
                ])))
                    ->setStatusCode(500)
                    ->addHeaders($error->getHeaders());
        }

        return (new Response($error->getBody()))
            ->setStatusCode($error->getCode())
            ->addHeaders($error->getHeaders());
    }
}