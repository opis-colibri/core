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

namespace Opis\Colibri;

use Opis\View\View as OpisView;
use Opis\Colibri\Rendering\RenderableViewTrait;

class View extends OpisView
{
    use RenderableViewTrait;

    /**
     * Set a value
     *
     * @param   string $name
     * @param   mixed $value
     * @return View|static
     */
    public function set(string $name, $value): self
    {
        $this->vars[$name] = $value;
        return $this;
    }

    /**
     * Check if a value was set
     *
     * @param   string $name
     *
     * @return  boolean
     */
    public function has(string $name): bool
    {
        return isset($this->vars[$name]);
    }

    /**
     * Get a value
     *
     * @param   string $name
     * @param   mixed $default (optional)
     *
     * @return  mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->vars[$name] ?? $default;
    }
}
