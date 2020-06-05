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
use Opis\Colibri\Collectors\TemplateStreamHandlerCollector;
use Opis\Colibri\Collectors\ViewCollector;
use Opis\Colibri\Templates\{CallbackTemplateHandler, HttpErrors, TemplateStream};

class Collector extends BaseCollector
{
    public function templateHandlers(TemplateStreamHandlerCollector $collector, int $priority = -100)
    {
        $collector->register('callback', CallbackTemplateHandler::class);
    }

    public function views(ViewCollector $view, int $priority = -100)
    {
        $view->handle('error.{error}', function ($error) {
            return TemplateStream::url('callback', HttpErrors::class . '::error' . $error, 'php');
        })->where('error', '401|403|404|405|500|503');
    }
}