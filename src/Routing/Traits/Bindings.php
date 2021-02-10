<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\Routing\Traits;

trait Bindings
{
    /** @var callable[] */
    private array $bindings = [];
    private array $defaults = [];

    /**
     * @return  callable[]
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    public function getDefaults(): array
    {
        return $this->defaults;
    }

    public function bind(string $name, callable $callback): static
    {
        $this->bindings[$name] = $callback;
        return $this;
    }

    public function implicit(string $name, mixed $value): static
    {
        $this->defaults[$name] = $value;
        return $this;
    }
}