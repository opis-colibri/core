<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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
use Opis\Colibri\Components\ApplicationTrait;
use Opis\Colibri\Components\ViewTrait;
use Opis\Http\Error\AccessDenied;
use Opis\Http\Error\NotFound;
use Opis\HttpRouting\Path;
use Opis\HttpRouting\Router;
use Opis\Routing\Path as BasePath;
use Opis\Routing\Router as AliasRouter;

/**
 * Class HttpRouter
 * @package Opis\Colibri\Routing
 * @method HttpRouteCollection getRouteCollection()
 */
class HttpRouter extends Router
{
    use ApplicationTrait;
    use ViewTrait;

    /** @var    Application */
    protected $app;

    /** @var    \Opis\HttpRouting\Path */
    protected $path;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $specials = array(
            'app' => $app,
            'request' => $app->request(),
            'response' => $app->response(),
            't' => $app->getTranslator(),
            'lang' => $app->getTranslator()->getLanguage(),
            'view' => $app->getViewApp(),
        );

        parent::__construct($app->collector()->getRoutes(), $app->collector()->getDispatcherResolver(), null, $specials);

        $this->getRouteCollection()
            ->notFound(function ($path) {
                return new NotFound($this->view('error.404', array('path' => $path)));
            })
            ->accessDenied(function ($path) {
                return new AccessDenied($this->view('error.403', array('path' => $path)));
            });
        
        $this->getRouteCollection()->setRouter($this);
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * Get current path
     *
     * @return  BasePath
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Route path
     *
     * @param Path $path
     * @return mixed
     */
    public function route(BasePath $path)
    {
        $router = new AliasRouter($this->app->collector()->getRouteAliases());
        $alias = $router->route(new BasePath($path->path()));

        if ($alias !== null) {
            $path = new Path(
                (string) $alias, $path->domain(), $path->method(), $path->isSecure(), $path->request()
            );
        }

        $this->path = $path;
        $result = parent::route($path);

        /** @var \Opis\Http\Request $request */
        $request = $path->request();
        /** @var \Opis\Http\Response $response */
        $response = $request->response();
        $response->body($result);
        $response->send();

        return $result;
    }
}
