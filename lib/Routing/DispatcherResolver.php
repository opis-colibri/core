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
use Opis\Routing\Dispatcher;
use Opis\Routing\DispatcherCollection;
use Opis\Routing\Path;
use Opis\Routing\Route;
use Opis\Routing\Router;

class DispatcherResolver extends \Opis\Routing\DispatcherResolver
{
    /** @var  Application */
    protected $app;

    /**
     * DispatcherResolver constructor.
     * @param Application $app
     * @param DispatcherCollection|null $collection
     */
    public function __construct(Application $app, DispatcherCollection $collection = null)
    {
        $this->app = $app;
        parent::__construct($collection);
    }

    /**
     * @param Path $path
     * @param Route $route
     * @param Router $router
     * @return Dispatcher
     */
    public function resolve(Path $path, Route $route, Router $router): Dispatcher
    {
        $dispatcher = $route->get('dispatcher', 'default');
        $factory = $this->collection->get($dispatcher);
        return $factory($this->app);
    }
}