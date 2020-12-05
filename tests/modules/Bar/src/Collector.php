<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Test\Bar;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Colibri\Priority;
use Opis\Colibri\Collectors\{RouterGlobalsCollector, RouteCollector};

class Collector extends BaseCollector
{
    public function routes(RouteCollector $route)
    {
        $route('/bar', static fn() => 'Bar');

        $route('/bar-post', static fn() => 'OK', ['POST']);

        $route('/multiple-methods', static fn() => 'OK', ['GET', 'POST']);

        $route('/bar-opt/{bar?}', static fn($bar) => $bar)
            ->implicit('bar', 'bar');

        $route('/bar-opt-g1/{g1?}', static fn($g1) => $g1)
            ->implicit('g1', 'OG1');

        $route('/bar-guard2', static fn() => 'bar')
            ->guard('guard1', static fn() => true)
            ->guard('guard2', static fn() => false);

        $route->group(static function (RouteCollector $route) {
            $route('/foo', static fn($upName) => $upName);

            $route->group(static function (RouteCollector $route) {
                $route('/foo', static fn($upName) => $upName);

                $route->group(static function (RouteCollector $route) {
                    $route('/', static fn($upName) => $upName)
                        // overwrite implicit again
                        ->implicit('name', 'group3');
                }, '/baz')
                    // binding should handle & overwrite implicit
                    ->bind('name', static fn($name) => implode('-', str_split($name)))
                    // overwrite binding
                    ->bind('upName', static fn($name) => 'UPPER:' . $name);

            }, '/bar')
                // overwrite
                ->implicit('name', 'group2');

            $route->group(static function (RouteCollector $route) {
                $route('/public', static fn($type) => $type);

                $route('/secret', static fn($type) => 'secret:' . $type)
                    // only two types have secrets
                    ->whereIn('type', ['type1', 'type2']);

                $route('/intruder', static fn($type) => 'intruder:' . $type)
                    // intruder
                    ->whereIn('type', ['type4']);

            }, '/{type}')
                ->whereIn('type', ['type1', 'type2', 'type3']);

        }, '/bar-group')
            ->implicit('name', 'group1')
            ->bind('upName', static fn($name) => strtoupper($name));

    }

    #[Priority(1)]
    public function priorityRoutes(RouteCollector $route)
    {
        $route('/foo', static fn() => 'Bar');

        $route('/foo-filter1', static fn() => 'bar')
            ->filter('filter1', static fn() => false);

        $route('/foo-filter-g1', static fn() => 'bar')
            ->filter('filter_g1');

        $route('/foo-filter-g1-pass', static fn() => 'bar')
            ->filter('filter_g1', static fn() => true);
    }

    #[Priority(1)]
    public function priorityGlobals(RouterGlobalsCollector $global)
    {
        $global->implicit('gow', 'bar');
    }
}