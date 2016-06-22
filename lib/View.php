<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

use Exception;
use Opis\Colibri\Components\ApplicationTrait;
use Opis\Colibri\Components\ViewTrait;
use Opis\View\View as OpisView;

class View extends OpisView
{
    use ApplicationTrait;

    /** @var    Application */
    protected $app;

    /** @var    string */
    protected $renderedContent = null;

    /**
     * Constructor
     *
     * @param   Application $app
     * @param   string $name
     * @param   array $arguments (optional)
     */
    public function __construct(Application $app, string $name, array $arguments = array())
    {
        $this->app = $app;
        parent::__construct($name, $arguments);
    }


    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }

    /**
     * Set a value
     *
     * @param   string $name
     * @param   mixed $value
     * @return View|static
     */
    public function set(string $name, $value): self
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * Check if a value was setted
     *
     * @param   string $name
     *
     * @return  boolean
     */
    public function has(string $name): bool
    {
        return isset($this->arguments[$name]);
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
        return $this->arguments[$name] ?? $default;
    }

    /**
     * Stringify
     *
     * @return  string
     */
    public function __toString()
    {
        if ($this->renderedContent === null) {
            try {
                $this->renderedContent = (string) $this->app->getViewApp()->render($this);

            } catch (Exception $e) {
                $this->renderedContent = (string) $e;
            }
        }

        return $this->renderedContent;
    }
}
