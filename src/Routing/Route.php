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
use Opis\Colibri\Routing\Traits\{
    Filter as FilterTrait,
    Bindings as BindingTrait
};

class Route
{
    use FilterTrait {
        getPlaceholders as getLocalPlaceholders;
        filter as private setFilter;
        guard as private setGuard;
        placeholder as private setPlaceholder;
    }

    use BindingTrait {
        getBindings as getLocalBindings;
        getDefaults as getLocalDefaults;
        bind as private setBinding;
        implicit as private setImplicit;
    }

    private RouteCollection $collection;
    private string $pattern;

    /** @var callable */
    private $action;

    private ?string $name;
    private int $priority;
    private string $id;

    /** @var string[] */
    private array $method;

    private array $cache = [];
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

    public function getDefaults(): array
    {
        if (!isset($this->cache[__FUNCTION__])) {
            $this->cache[__FUNCTION__] = $this->getLocalDefaults() + $this->collection->getDefaults();
        }

        return $this->cache[__FUNCTION__];
    }

    /**
     * @return callable[]
     */
    public function getBindings(): array
    {
        if (!isset($this->cache[__FUNCTION__])) {
            $this->cache[__FUNCTION__] = $this->getLocalBindings() + $this->collection->getBindings();
        }

        return $this->cache[__FUNCTION__];
    }

    public function getPlaceholders(): array
    {
        if (!isset($this->cache[__FUNCTION__])) {
            $this->cache[__FUNCTION__] = $this->getLocalPlaceholders() + $this->collection->getPlaceholders();
        }

        return $this->cache[__FUNCTION__];
    }

    public function bind(string $name, callable $callback): static
    {
        if ($this->inheriting && isset($this->getLocalBindings()[$name])) {
            return $this;
        }

        return $this->setBinding($name, $callback);
    }

    public function placeholder(string $name, $value): static
    {
        if ($this->inheriting && isset($this->getLocalPlaceholders()[$name])) {
            return $this;
        }

        return $this->setPlaceholder($name, $value);
    }

    public function implicit(string $name, $value): static
    {
        if ($this->inheriting && array_key_exists($name, $this->getLocalDefaults())) {
            return $this;
        }

        return $this->setImplicit($name, $value);
    }

    public function filter(string $name, ?callable $callback = null): static
    {
        if ($this->inheriting && array_key_exists($name, $this->filters)) {
            return $this;
        }

        return $this->setFilter($name, $callback);
    }

    public function guard(string $name, ?callable $callback = null): static
    {
        if ($this->inheriting && array_key_exists($name, $this->guards)) {
            return $this;
        }

        return $this->setGuard($name, $callback);
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

    public function mixin(string $name, ?array $config = null): static
    {
        if (!is_subclass_of($name, Mixin::class, true)) {
            throw new RuntimeException("Unknown mixin " . $name);
        }
        (new $name)($this, $config);
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
