<?php
/* ============================================================================
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

namespace Test\Views;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Colibri\ItemCollectors\ViewCollector;

class Collector extends BaseCollector
{
    /**
     * @param ViewCollector $view
     */
    public function views(ViewCollector $view)
    {
        $view->handle('test.{name}', function (string $name) {
            return __DIR__ . "/../../views/test.{$name}.php";
        })
            ->whereIn('name', ['value', 'subview']);
    }
}