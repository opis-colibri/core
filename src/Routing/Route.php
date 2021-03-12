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

use RuntimeException;

class Route
{
    private RouteCollection $collection;
    private string $pattern;

    /** @var callable[] */
    private array $guards = [];
    private array $placeholders = [];
    /** @var callable[] */
    private array $filters = [];

    /** @var callable[] */
    private array $bindings = [];
    private array $defaults = [];

    /** @var callable */
    private $action;

    private ?string $name;
    private int $priority;
    private string $id;

    /** @var string[] */
    private array $method;

    private array $properties = [];
    private bool $inheriting = false;

    public function __construct(
        RouteCollection $collection,
        string $id,
        string $pattern,
        callable $action,
        array $method = ['GET'],
        int $priority = 0,
        ?string $name = null
    ) {
        $this->collection = $collection;
        $this->id = $id;
        $this->pattern = $pattern;
        $this->action = $action;
        $this->name = $name;
        $this->priority = $priority;
        $this->method = $method;
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getAction(): callable
    {
        return $this->action;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRouteCollection(): RouteCollection
    {
        return $this->collection;
    }

    /**
     * @return string[]
     */
    public function getMethod(): array
    {
        return $this->method;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    /**
     * @return callable[]
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return callable[]
     */
    public function getGuards(): array
    {
        return $this->guards;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return  callable[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function bind(string $name, callable $callback): static
    {
        if ($this->inheriting && array_key_exists($name, $this->bindings)) {
            return $this;
        }
        $this->bindings[$name] = $callback;
        return $this;
    }

    public function placeholder(string $name, mixed $value): static
    {
        if ($this->inheriting && array_key_exists($name, $this->placeholders)) {
            return $this;
        }
        $this->placeholders[$name] = $value;
        return $this;
    }

    public function default(string $name, mixed $value): static
    {
        if ($this->inheriting && array_key_exists($name, $this->defaults)) {
            return $this;
        }
        $this->defaults[$name] = $value;
        return $this;
    }

    public function filter(callable ...$callbacks): static
    {
        foreach ($callbacks as $callback) {
            if (!in_array($callback, $this->filters, true)) {
                $this->filters[] = $callback;
            }
        }

        return $this;
    }

    public function guard(callable ...$callbacks): static
    {
        foreach ($callbacks as $callback) {
            if (!in_array($callback, $this->guards, true)) {
                $this->guards[] = $callback;
            }
        }

        return $this;
    }

    public function middleware(string ...$middleware): static
    {
        if ($this->inheriting && isset($this->properties['middleware'])) {
            return $this;
        }
        $this->properties['middleware'] = $middleware;
        return $this;
    }

    public function getMiddleware(): ?array
    {
        return $this->properties['middleware'] ?? null;
    }

    public function domain(string $value): static
    {
        if ($this->inheriting && isset($this->properties['domain'])) {
            return $this;
        }
        $this->properties['domain'] = $value;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->properties['domain'] ?? null;
    }

    public function secure(bool $value = true): static
    {
        if ($this->inheriting && isset($this->properties['secure'])) {
            return $this;
        }

        $this->properties['secure'] = $value;
        return $this;
    }

    public function isSecure(): bool
    {
        return $this->properties['secure'] ?? false;
    }

    public function where(string $name, string $value): static
    {
        return $this->placeholder($name, $value);
    }

    public function whereIn(string $name, array $values): static
    {
        if (empty($values)) {
            return $this;
        }

        return $this->placeholder($name, $this->collection->getRegexBuilder()->join($values));
    }

    public function mixin(string $class, ?array $config = null): static
    {
        if (!is_subclass_of($class, Mixin::class, true)) {
            throw new RuntimeException("Unknown mixin " . $class);
        }
        (new $class)($this, $config);
        return $this;
    }

    public function __serialize(): array
    {
        return [
            'collection' => $this->collection,
            'pattern' => $this->pattern,
            'action' => $this->action,
            'method' => $this->method,
            'name' => $this->name,
            'priority' => $this->priority,
            'id' => $this->id,
            'properties' => $this->properties,
            'placeholders' => $this->placeholders,
            'filters' => $this->filters,
            'guards' => $this->guards,
            'bindings' => $this->bindings,
            'defaults' => $this->defaults,
        ];
    }

    public function __unserialize(array $data): void
    {
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }
    }

    public static function setIsInheriting(Route $route, bool $value): void
    {
        $route->inheriting = $value;
    }
}
