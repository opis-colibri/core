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

namespace Opis\Colibri\Core;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Colibri\Collectors\{RouteCollector, TemplateStreamHandlerCollector, ViewCollector};
use Opis\Colibri\Templates\CallbackTemplateHandler;
use Opis\Colibri\Internal\Views as InternalViews;
use Opis\Colibri\Internal\Routes as InternalRoutes;
use Opis\Http\Responses\FileStream;
use function Opis\Colibri\view;

class Collector extends BaseCollector
{
    public function templateHandlers(TemplateStreamHandlerCollector $collector, int $priority = -100)
    {
        $collector->register('callback', CallbackTemplateHandler::class);
    }

    public function views(ViewCollector $view, int $priority = -100)
    {
        $view->handle('welcome', InternalViews::class . '::welcome');

        $view->handle('error.{error}', InternalViews::class . '::httpError')
            ->where('error', '401|403|404|405|500|503');
    }

    public function routes(RouteCollector $route, int $priority = -100)
    {
        $route->group(function (RouteCollector $route) {
            $route('/', InternalRoutes::class . '::welcome');
            $route('/opis-colibri/assets/{file}', InternalRoutes::class . '::file')
                ->whereIn('file', ['background.png', 'favicon.png']);
        })->filter('opis-colibri-production', InternalRoutes::class . '::filter');
    }
}