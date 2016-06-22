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
use Opis\Colibri\Components\ApplicationTrait;
use Opis\Routing\Router;
use Opis\View\ViewApp as BaseViewApp;

class ViewApp extends BaseViewApp
{
    use ApplicationTrait;

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
        $this->viewItem = new ViewItem($app);
        parent::__construct($app->collector()->getViews(), $app->collector()->getViewEngineResolver(), true, 'this');
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
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
                'request' => $this->app->getHttpRequest(),
                'response' => $this->app->getHttpResponse(),
                't' => $this->app->getTranslator(),
                'lang' => $this->app->getTranslator()->getLanguage(),
                'view' => $this,
            );

            $this->router = new Router($this->collection, null, $this->getFilters(), $specials);
        }

        return $this->router;
    }
}
