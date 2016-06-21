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

use Opis\Colibri\Controller;
use Opis\Routing\Route;

class HttpRoute extends Route
{
    protected $resolvedAction;

    /**
     * Get the route's callback
     *
     * @return  callable
     *
     * @throws \RuntimeException
     */
    public function getAction()
    {
        if ($this->resolvedAction === null) {
            if ($this->routeAction instanceof Controller) {

                /** @var HttpRouter $router */
                $router = $this->get('#collection')->getRouter();
                $class = $className = $this->routeAction->getClass();
                $method = $methodName = $this->routeAction->getMethod();

                $values = $this->compile()->bind($router->getPath(), $router->getSpecialValues());


                if ($method[0] === '@') {
                    $method = substr($method, 1);
                    if (!isset($values[$method])) {
                        throw new \RuntimeException("Unknown controller variable `$method`");
                    }

                    $method = $values[$method]->value();

                    if (!is_string($method)) {
                        throw new \RuntimeException("`$methodName` must be resolved to a string");
                    }
                }

                if ($class[0] === '@') {
                    $class = substr($class, 1);
                    if (!isset($values[$class])) {
                        throw new \RuntimeException("Unknown controller variable `$class`");
                    }

                    $class = $values[$class]->value();

                    if (!is_string($class)) {
                        throw new \RuntimeException("`$className` must be resolved to a string");
                    }
                }

                if (!$this->routeAction->isStatic()) {
                    $class = $router->app()->make($class);
                }

                $this->resolvedAction = array($class, $method);
            } else {
                $this->resolvedAction = $this->routeAction;
            }
        }

        return $this->resolvedAction;
    }
}
