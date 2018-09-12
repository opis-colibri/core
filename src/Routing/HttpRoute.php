<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use Opis\Routing\Route;
use Opis\HttpRouting\Route as BaseHttpRoute;
use Opis\Colibri\Serializable\ControllerCallback;
use function Opis\Colibri\Functions\{
    app, make
};

/**
 * @property HttpRouteCollection $collection
 */
class HttpRoute extends BaseHttpRoute
{
    /** @var callable */
    protected $resolvedAction;

    /** @var bool */
    protected $inheriting = false;

    /**
     * If this is true, then no overwrite will occur
     * @param bool $inheriting
     * @return static|HttpRoute
     */
    public function setIsInheriting(bool $inheriting): self
    {
        $this->inheriting = $inheriting;
        return $this;
    }

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
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function set(string $name, $value): Route
    {
        // Handles middleware, domain, method, secure, filter, guard, *
        if ($this->inheriting && $this->has($name)) {
            return $this;
        }
        return parent::set($name, $value);
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function callback(string $name, callable $callback): BaseHttpRoute
    {
        $list = $this->get('callbacks', []);

        if ($this->inheriting && isset($list[$name])) {
            return $this;
        }

        $list[$name] = $callback;
        parent::set('callbacks', $list);

        return $this;
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function bind(string $name, callable $callback): Route
    {
        if ($this->inheriting && isset($this->bindings[$name])) {
            return $this;
        }
        return parent::bind($name, $callback);
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function placeholder(string $name, string $value): Route
    {
        if ($this->inheriting && array_key_exists($name, $this->placeholders)) {
            return $this;
        }
        return parent::placeholder($name, $value);
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function implicit(string $name, $value): Route
    {
        if ($this->inheriting && array_key_exists($name, $this->defaults)) {
            return $this;
        }
        return parent::implicit($name, $value);
    }

    /**
     * @param string ...$middleware
     * @return static|HttpRoute
     */
    public function middleware(string ...$middleware): self
    {
        $this->set('middleware', $middleware);
        return $this;
    }

    /**
     * @param string $name
     * @param array|null $config
     * @return static|HttpRoute
     */
    public function mixin(string $name, array $config = null): self
    {
        $mixins = $this->collection->getMixins();
        if (!isset($mixins[$name])) {
            throw new \RuntimeException("Unknown mixin name " . $name);
        }
        $mixins[$name]($this, $config);
        return $this;
    }
}
