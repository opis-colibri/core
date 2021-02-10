<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\IoC;

use Opis\Colibri\Application;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionUnionType;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    protected const TYPES = ['bool', 'int', 'float', 'string', 'callable', 'array', 'object', 'self', 'mixed'];

    /** @var Dependency[] */
    protected array $bindings = [];

    /** @var object[] */
    protected array $instances = [];

    /** @var string[] */
    protected array $aliases = [];

    /** @var ReflectionClass[] */
    protected array $reflectionClass = [];

    /** @var ReflectionMethod[] */
    protected array $reflectionMethod = [];

    public function __construct()
    {
        $this->instances[Application::class] = Application::getInstance();
    }

    public function singleton(string $abstract, string|callable|null $concrete = null, array $arguments = []): static
    {
        return $this->bindDependency($abstract, $concrete, $arguments, true);
    }

    public function bind(string $abstract, string|callable|null $concrete = null, array $arguments = []): static
    {
        return $this->bindDependency($abstract, $concrete, $arguments, false);
    }

    public function unbind(string $abstract): static
    {
        unset(
            $this->instances[$abstract],
            $this->aliases[$abstract],
            $this->bindings[$abstract],
        );

        return $this;
    }

    public function alias(string $alias, ?string $type): static
    {
        if ($type === null) {
            unset($this->aliases[$alias]);
        } else {
            $this->aliases[$alias] = $type;
        }

        return $this;
    }

    public function extend(string $abstract, callable $extender): static
    {
        $this->resolve($abstract)->addExtender($extender);
        return $this;
    }

    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $dependency = $this->resolve($abstract);

        $instance = $this->build($dependency->getConcrete(), $dependency->getArguments());

        foreach ($dependency->getExtenders() as $callback) {
            $new_instance = $callback($instance, $this);
            if ($new_instance !== null) {
                $instance = $new_instance;
            }
        }

        if ($dependency->isShared()) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    public function __invoke(string $abstract): object
    {
        return $this->make($abstract);
    }

    /**
     * @inheritDoc
     */
    public function get($id): object
    {
        if (!isset($this->aliases[$id])) {
            throw new NotFoundException();
        }

        return $this->make($id);
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return isset($this->aliases[$id]);
    }

    public function getInstance(string $key): ?object
    {
        return $this->instances[$key] ?? null;
    }

    protected function bindDependency(string $abstract, string|callable|null $concrete, array $arguments, bool $shared): static
    {
        if (is_null($concrete)) {
            $concrete = $abstract;
        }

        if (!is_string($concrete) && !is_callable($concrete)) {
            throw new InvalidArgumentException('The second argument must be an instantiable class or a callable');
        }

        $dependency = new Dependency($concrete, $arguments, $shared);

        unset($this->instances[$abstract]);
        unset($this->aliases[$abstract]);

        $this->bindings[$abstract] = $dependency;

        return $this;
    }

    protected function resolve(string $abstract, array &$stack = []): Dependency
    {
        if (isset($this->aliases[$abstract])) {
            $alias = $this->aliases[$abstract];

            if (in_array($alias, $stack)) {
                $stack[] = $alias;
                $error = implode(' => ', $stack);
                throw new BindingException("Circular reference detected: $error");
            } else {
                $stack[] = $alias;
                return $this->resolve($alias, $stack);
            }
        }

        if (!isset($this->bindings[$abstract])) {
            $this->bind($abstract);
        }

        return $this->bindings[$abstract];
    }

    protected function build(string|callable $concrete, array $arguments = []): object
    {
        if (is_callable($concrete)) {
            return $concrete($this, $arguments);
        }

        if (isset($this->reflectionClass[$concrete])) {
            $reflection = $this->reflectionClass[$concrete];
        } else {
            try {
                $reflection = $this->reflectionClass[$concrete] = new ReflectionClass($concrete);
            } catch (ReflectionException $e) {
                throw new NotFoundException($e->getMessage(), 0, $e);
            }
        }

        if (!$reflection->isInstantiable()) {
            throw new BindingException("The '${concrete}' type is not instantiable");
        }

        if (isset($this->reflectionMethod[$concrete])) {
            $constructor = $this->reflectionMethod[$concrete];
        } else {
            $constructor = $this->reflectionMethod[$concrete] = $reflection->getConstructor();
        }

        if (is_null($constructor)) {
            return new $concrete();
        }

        // Resolve arguments
        $parameters = array_diff_key($constructor->getParameters(), $arguments);

        /**
         * @var int $key
         * @var ReflectionParameter $parameter
         */
        foreach ($parameters as $key => $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType) {
                $class = $type->getName();

                if (in_array($class, self::TYPES)) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $arguments[$key] = $parameter->getDefaultValue();
                        continue;
                    }

                    if ($parameter->allowsNull()) {
                        $arguments[$key] = null;
                        continue;
                    }

                    throw new BindingException("Could not resolve [$parameter] for building $concrete");
                }

                try {
                    $arguments[$key] = isset($this->bindings[$class])
                        ? $this->make($class)
                        : $this->build($class);
                } catch (BindingException $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $arguments[$key] = $parameter->getDefaultValue();
                        continue;
                    }

                    if ($parameter->allowsNull()) {
                        $arguments[$key] = null;
                        continue;
                    }

                    throw $e;
                }

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[$key] = $parameter->getDefaultValue();
                continue;
            }

            if (($type instanceof ReflectionUnionType) && $parameter->allowsNull()) {
                $arguments[$key] = null;
                continue;
            }

            throw new BindingException("Could not resolve [$parameter] for building $concrete");
        }

        ksort($arguments);

        return $reflection->newInstanceArgs($arguments);
    }

    public function __serialize(): array
    {
        return [
            'bindings' => $this->bindings,
            'aliases' => $this->aliases,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->bindings = $data['bindings'];
        $this->aliases = $data['aliases'];
        $this->__construct();
    }
}
