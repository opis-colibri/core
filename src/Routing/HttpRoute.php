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

use Opis\Colibri\Serializable\ControllerCallback;
use Opis\HttpRouting\Route;
use function Opis\Colibri\{
    app, make
};

class HttpRoute extends Route
{
    protected $resolvedAction;

    public function getAction(): callable
    {
        if($this->resolvedAction !== null){
            return $this->resolvedAction;
        }

        if(!($this->routeAction instanceof ControllerCallback)){
            return $this->resolvedAction = $this->routeAction;
        }

        /** @var ControllerCallback $callback */
        $callback = $this->routeAction;
        $router = app()->getHttpRouter();
        $values = $router->bind($router->extract($router->getContext(), $this), $this->getBindings());
        $method = $callback->getMethod();
        $class = $callback->getClass();

        if($class[0] === '@'){
            $class = substr($class, 1);
            if(!isset($values[$class])){
                throw new \RuntimeException("Unknown controller variable '$class'");
            }
            $class = $values[$class]->value();
        }

        if($method[0] === '@'){
            $method = substr($method, 1);
            if(!isset($values[$method])){
                throw new \RuntimeException("Unknown controller variable '$method'");
            }
            $method = $values[$method]->value();
        }

        if(!$callback->isStatic()){
            $class = make($class);
        }

        return $this->resolvedAction = [$class, $method];
    }
}
