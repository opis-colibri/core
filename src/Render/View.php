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

namespace Opis\Colibri\Render;

use Stringable;
use function Opis\Colibri\render;

class View implements Renderable, Stringable
{
    protected string $name;
    protected array $vars;
    protected ?string $renderedContent = null;

    public function __construct(string $name, array $vars = [])
    {
        $this->name = $name;
        $this->vars = $vars;
    }

    public function getViewName(): string
    {
        return $this->name;
    }

    public function getViewVariables(): array
    {
        return $this->vars;
    }

    /**
     * Set a value
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    protected function set(string $name, mixed $value): static
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * Check if a value was set
     *
     * @param   string $name
     * @return  boolean
     */
    protected function has(string $name): bool
    {
        return isset($this->vars[$name]);
    }

    /**
     * Get a value
     *
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    protected function get(string $name, mixed $default = null): mixed
    {
        return $this->vars[$name] ?? $default;
    }

    public function isRendered(): bool
    {
        return $this->renderedContent !== null;
    }

    public function __toString(): string
    {
        if ($this->renderedContent === null) {
            $this->renderedContent = render($this);
        }

        return $this->renderedContent;
    }
}
