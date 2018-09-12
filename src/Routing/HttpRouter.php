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

namespace Opis\Colibri\Routing;

use Opis\Colibri\Application;
use Opis\HttpRouting\Router;
use Opis\Routing\Context;

class HttpRouter extends Router
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;

        parent::__construct($app->getCollector()->getRoutes(), new Dispatcher($app), null, new \ArrayObject([
            'app' => $app,
            'lang' => $app->getTranslator()->getDefaultLanguage(),
        ]));
    }

    /**
     * @inheritdoc
     */
    public function route(Context $context)
    {
        $this->global['request'] = $context->data();
        return parent::route($context);
    }

    /**
     * @return Application
     */
    public function getApp(): Application
    {
        return $this->app;
    }
}
