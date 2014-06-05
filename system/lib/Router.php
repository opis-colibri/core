<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Opis\Colibri;

use Opis\Colibri\App;
use Opis\Routing\Router as AliasRouter;
use Opis\Routing\Path as AliasPath;
use Opis\HttpRouting\Path;
use Opis\HttpRouting\Router as HttpRouter;
use Opis\Routing\Contracts\PathInterface;
use Opis\Http\Error\NotFound;
use Opis\Http\Error\AccessDenied;

class Router extends HttpRouter
{
    
    public function __construct()
    {
        parent::__construct(App::httpRoutes(), App::httpDispatchers());
        
        $this->getRouteCollection()->accessDenied(function($path){
            return new AccessDenied(View('error.403'), array('path' => $path));
        });
    }
    
    public function route(PathInterface $path)
    {
        
        $result = parent::route($path);
        
        if($result === null)
        {
            $this->getRouteCollection()->notFound(function($path){
                return new NotFound(View('error.404', array('path' => $path)));
            });
            
            $router = new AliasRouter(App::httpRouteAliases());
            $alias = $router->route(new AliasPath($path->path()));
            
            if($alias === null)
            {
                $result = new NotFound(View('error.404', array('path' => $path)));
            }
            else
            {
                $result = parent::route(new Path(
                    $alias,
                    $path->domain(),
                    $path->method(),
                    $path->isSecure(),
                    $path->request()
                ));
            }
            
        }
        
        $response = $path->request()->response();
        $response->body($result);
        $response->send();
        
        return $result;
    }
}
