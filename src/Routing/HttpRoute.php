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

use Opis\Colibri\Serializable\ControllerCallback;
use Opis\HttpRouting\Route;
use function Opis\Colibri\Functions\{
    app, make
};

class HttpRoute extends Route
{
    protected $resolvedAction;

    /**
     * @inheritdoc
     */
    public function getAction(): callable
    {
        if ($this->resolvedAction !== null) {
            return $this->resolvedAction;
        }

        if (!$this->routeAction instanceof ControllerCallback) {
            return $this->resolvedAction = $this->routeAction;
        }

        /** @var ControllerCallback $callback */
        $callback = $this->routeAction;

        $methodName = $callback->getMethod();
        $className = $callback->getClass();

        $argResolver = app()->getHttpRouter()->resolveInvoker($this)->getArgumentResolver();

        if ($className[0] === '@') {
            $className = substr($className, 1);
            $class = $argResolver->getArgumentValue($className);
            if ($class === null) {
                throw new \RuntimeException("Unknown controller variable '$className'");
            }
        } else {
            $class = $className;
        }

        if ($methodName[0] === '@') {
            $methodName = substr($methodName, 1);
            $method = $argResolver->getArgumentValue($methodName);
            if ($method === null) {
                throw new \RuntimeException("Unknown controller variable '$methodName'");
            }
        } else {
            $method = $methodName;
        }

        if (!$callback->isStatic()) {
            $class = make($class);
        }

        return $this->resolvedAction = [$class, $method];
    }

    /**
     * @param string[] ...$middleware
     * @return HttpRoute
     */
    public function middleware(string ...$middleware): self
    {
        $this->set('middleware', $middleware);
        return $this;
    }
}
