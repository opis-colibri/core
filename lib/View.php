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
use Opis\View\View as OpisView;

class View extends OpisView
{
    /** @var    \Opis\Colibri\Application */
    protected $app;

    /** @var    string */
    protected $renderedContent = null;

    /**
     * Constructor
     * 
     * @param   \Opis\Colibri\Application   $app
     * @param   string                      $name
     * @param   array                       $arguments  (optional)
     */
    public function __construct(Application $app, $name, array $arguments = array())
    {
        $this->app = $app;
        parent::__construct($name, $arguments);
    }
    
    /**
     * Get application
     * 
     * @return  \Opis\Colibri\Application
     */
    public function app()
    {
        return $this->app;
    }
    
    /**
     * Set a value
     * 
     * @param   string  $name
     * @param   mixed   $value
     * 
     * @return  $this
     */
    public function set($name, $value)
    {
        $this->arguments[$name] = $value;
        return $this;
    }
    
    /**
     * Check if a value was setted
     * 
     * @param   string  $name
     * 
     * @return  boolean
     */
    public function has($name)
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Get a value
     * 
     * @param   string  $name
     * @param   mixed   $default    (optional)
     * 
     * @return  mixed
     */
    public function get($name, $default = null)
    {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
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
                $this->renderedContent = $this->app->render($this);

                if (!is_string($this->renderedContent)) {
                    $this->renderedContent = (string) $this->renderedContent;
                }
            } catch (Exception $e) {
                $this->renderedContent = (string) $e;
            }
        }
        return $this->renderedContent;
    }
}
