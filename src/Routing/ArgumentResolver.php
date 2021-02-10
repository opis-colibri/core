<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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

use Closure;
use ArrayAccess;
use ArrayObject;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

class ArgumentResolver
{
    /** @var array */
    private array $values = [];

    /** @var callable[] */
    private array $bindings = [];

    /** @var ArrayAccess */
    private ArrayAccess $defaults;

    /**
     * ArgumentResolver constructor.
     * @param array|ArrayAccess|null $defaults
     */
    public function __construct(array|ArrayAccess|null $defaults = null)
    {
        if (!($defaults instanceof ArrayAccess)) {
            $defaults = $defaults ? new ArrayObject($defaults) : new ArrayObject();
        }
        /** @var ArrayAccess $defaults */
        $this->defaults = $defaults;
    }

    /**
     * @param string $name
     * @param $value
     * @return $this
     */
    public function addValue(string $name, $value): self
    {
        $this->values[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param callable $binding
     * @return $this
     */
    public function addBinding(string $name, callable $binding): self
    {
        $this->bindings[$name] = $binding;
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     */
    public function addValues(array $values): self
    {
        foreach ($values as $name => $value) {
            $this->values[$name] = $value;
        }

        return $this;
    }

    /**
     * @param callable[] $bindings
     * @return $this
     */
    public function addBindings(array $bindings): self
    {
        foreach ($bindings as $name => $callback) {
            $this->bindings[$name] = $callback;
        }

        return $this;
    }

    public function getArgumentValue(string $name, bool $bind = true, mixed $default = null): mixed
    {
        if ($bind && isset($this->bindings[$name])) {
            $callable = $this->bindings[$name];
            unset($this->bindings[$name]);
            $this->values[$name] = $callable(...$this->resolve($callable));
        }

        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        if ($this->defaults->offsetExists($name)) {
            return $this->defaults[$name];
        }

        return $default;
    }

    public function execute(callable $callback, bool $bind = true): mixed
    {
        $arguments = $this->resolve($callback, $bind);
        return $arguments ? $callback(...$arguments) : $callback();
    }

    public function resolve(callable $callback, bool $bind = true): array
    {
        $arguments = [];

        try {
            $parameters = $this->getParameters($callback);
        } catch (ReflectionException) {
            return $arguments;
        }

        foreach ($parameters as $param) {
            $arguments[] = $this->getArgumentValue(
                $param->getName(),
                $bind,
                $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null
            );
        }

        return $arguments;
    }

    /**
     * @param callable $callback
     * @return ReflectionParameter[]
     * @throws ReflectionException
     */
    public function getParameters(callable $callback): array
    {
        if (is_string($callback)) {
            if (function_exists($callback)) {
                $parameters = (new ReflectionFunction($callback))->getParameters();
            } else {
                $parameters = (new ReflectionMethod($callback))->getParameters();
            }
        } elseif (is_object($callback)) {
            if ($callback instanceof Closure) {
                $parameters = (new ReflectionFunction($callback))->getParameters();
            } else {
                $parameters = (new ReflectionMethod($callback, '__invoke'))->getParameters();
            }
        } else {
            /** @var array $callback */
            $parameters = (new ReflectionMethod(reset($callback), end($callback)))->getParameters();
        }

        return $parameters;
    }
}