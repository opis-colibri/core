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

namespace Opis\Colibri\Routing;

use Opis\Colibri\Application;
use Opis\Colibri\View;
use Opis\Routing\Router;
use Opis\View\ViewRouter as BaseRouter;

class ViewRouter extends BaseRouter
{
    /** @var    Application */
    protected $app;

    /**
     * Constructor
     *
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $this->param = $app;
        parent::__construct($app->collector()->getViews(), $app->collector()->getViewEngineResolver());
    }

    /**
     * Returns an instance of the specified contract or class
     *
     * @param   string $name Contract name or class name
     * @param   array $arguments (optional) Arguments that will be passed to the contract constructor
     *
     * @return  mixed
     */
    public function __invoke($name, array $arguments = array())
    {
        return new View($this->app, $name, $arguments);
    }

    /**
     * Get the application
     *
     * @return  \Opis\Colibri\Application
     */
    public function app()
    {
        return $this->app;
    }

    /**
     * Creates a new view
     *
     * @param   string $name View name
     * @param   array $arguments (optional) View's arguments
     *
     * @return  \Opis\Colibri\View
     */
    public function view($name, array $arguments = array())
    {
        return new View($this->app, $name, $arguments);
    }

    /**
     * Returns a path to a module's asset
     *
     * @param   string $module Module name
     * @param   string $path Module's resource relative path
     * @param   boolean $full Full path flag
     *
     * @return  string
     */
    public function asset($module, $path, $full = true)
    {
        return $this->app->asset($module, $path, $full);
    }

    /**
     * Get the URI for a path
     *
     * @param   string $path The path
     * @param   boolean $full (optional) Full URI flag
     *
     * @return  string
     */
    public function getURL($path, $full = true)
    {
        return $this->app->getURL($path, $full);
    }

    /**
     * Creates an path from a named route
     *
     * @param   string $route Route name
     * @param   array $args (optional) Route wildcard's values
     *
     * @return  string
     */
    public function getPath($route, array $args = array())
    {
        return $this->app->getPath($route, $args);
    }

    /**
     * Return a variable's value
     *
     * @param   string $name Variable's name
     * @param   mixed $default (optional) The value that will be returned if the variable doesn't exist
     *
     * @return  mixed
     */
    public function variable($name, $default = null)
    {
        return $this->app->variable($name, $default);
    }

    /**
     * Translate a text
     *
     * @param   string $sentence The text that will be translated
     * @param   array $placeholders (optional) An array of placeholders
     * @param   string $lang (optional) Translation language
     *
     * @return  string  Translated text
     */
    public function t($sentence, $placeholders = array(), $lang = null)
    {
        return $this->app->t($sentence, $placeholders, $lang);
    }

    /**
     * Generates a CSRF token
     *
     * @return  string
     */
    public function csrfToken()
    {
        return $this->app->csrfToken();
    }

    /**
     * Get router (override)
     *
     * @return  \Opis\Routing\Router
     */
    protected function getRouter()
    {
        if ($this->router === null) {

            $specials = array(
                'app' => $this->app,
                'request' => $this->app->request(),
                'response' => $this->app->response(),
                't' => $this->app->getTranslator(),
                'lang' => $this->app->getTranslator()->getLanguage(),
                'view' => $this,
            );

            $this->router = new Router($this->collection, null, $this->getFilters(), $specials);
        }

        return $this->router;
    }
}
