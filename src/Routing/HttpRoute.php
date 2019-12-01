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
use RuntimeException;
use function Opis\Colibri\Functions\{
    app, make
};

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

        $callback = parent::getAction();

        if (!$callback instanceof ControllerCallback) {
            return $this->resolvedAction = $callback;
        }

        /** @var ControllerCallback $callback */

        $methodName = $callback->getMethod();
        $className = $callback->getClass();

        $argResolver = app()->getHttpRouter()->resolveInvoker($this)->getArgumentResolver();

        if ($className[0] === '@') {
            $className = substr($className, 1);
            $class = $argResolver->getArgumentValue($className);
            if ($class === null) {
                throw new RuntimeException("Unknown controller variable '$className'");
            }
        } else {
            $class = $className;
        }

        if ($methodName[0] === '@') {
            $methodName = substr($methodName, 1);
            $method = $argResolver->getArgumentValue($methodName);
            if ($method === null) {
                throw new RuntimeException("Unknown controller variable '$methodName'");
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
    public function filter(string $name, callable $callback = null): BaseHttpRoute
    {
        return $this->setCallback('filters', $name, $callback);
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function guard(string $name, callable $callback = null): BaseHttpRoute
    {
        return $this->setCallback('guards', $name, $callback);
    }

    /**
     * @inheritDoc
     * @return static|HttpRoute
     */
    public function bind(string $name, callable $callback): Route
    {
        if ($this->inheriting && isset($this->getLocalBindings()[$name])) {
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
        if ($this->inheriting && array_key_exists($name, $this->getLocalPlaceholders())) {
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
        if ($this->inheriting && array_key_exists($name, $this->getLocalDefaults())) {
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
        /** @var HttpRouteCollection $collection */
        $collection = $this->getRouteCollection();
        $mixins = $collection->getMixins();
        if (!isset($mixins[$name])) {
            throw new RuntimeException("Unknown mixin name " . $name);
        }
        $mixins[$name]($this, $config);
        return $this;
    }

    /**
     * @param string $type
     * @param string $name
     * @param callable|null $callback
     * @return HttpRoute
     */
    private function setCallback(string $type, string $name, ?callable $callback): self
    {
        $list = $this->get($type, []);

        if ($this->inheriting && isset($list[$name])) {
            return $this;
        }

        $list[$name] = $callback;
        parent::set($type, $list);

        return $this;
    }
}
