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

namespace Test\Bar;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Colibri\Collectors\RouterGlobalsCollector;
use Opis\Colibri\Collectors\RouteCollector;

class Collector extends BaseCollector
{
    public function routes(RouteCollector $route)
    {
        $route('/bar', function () {
            return 'Bar';
        });

        $route('/bar-post', function () {
            return 'OK';
        }, ['POST']);

        $route('/multiple-methods', function () {
            return 'OK';
        }, ['GET', 'POST']);

        $route('/bar-opt/{bar?}', function ($bar) {
            return $bar;
        })->implicit('bar', 'bar');

        $route('/bar-opt-g1/{g1?}', function ($g1) {
            return $g1;
        })->implicit('g1', 'OG1');

        $route('/bar-guard2', function () {
            return 'bar';
        })->guard('guard1', function () {
            return true;
        })->guard('guard2', function () {
            return false;
        });

        $route->group(function (RouteCollector $route) {
            $route('/foo', function ($upName) {
                return $upName;
            });

            $route->group(function (RouteCollector $route) {
                $route('/foo', function ($upName) {
                    return $upName;
                });

                $route->group(function (RouteCollector $route) {
                    $route('/', function ($upName) {
                        return $upName;
                    })
                        // overwrite implicit again
                        ->implicit('name', 'group3');
                }, '/baz')
                    // binding should handle & overwrite implicit
                    ->bind('name', function ($name) {
                        return implode('-', str_split($name));
                    })
                    // overwrite binding
                    ->bind('upName', function ($name) {
                        return 'UPPER:' . $name;
                    });

            }, '/bar')
                // overwrite
                ->implicit('name', 'group2');

            $route->group(function (RouteCollector $route) {
                $route('/public', function ($type) {
                    return $type;
                });

                $route('/secret', function ($type) {
                    return 'secret:' . $type;
                })
                    // only two types have secrets
                    ->whereIn('type', ['type1', 'type2']);

                $route('/intruder', function ($type) {
                    return 'intruder:' . $type;
                })
                    // intruder
                    ->whereIn('type', ['type4']);

            }, '/{type}')
                ->whereIn('type', ['type1', 'type2', 'type3']);

        }, '/bar-group')
            ->implicit('name', 'group1')
            ->bind('upName', function ($name) {
                return strtoupper($name);
            });

    }

    public function priorityRoutes(RouteCollector $route, int $priority = 1)
    {
        $route('/foo', function () {
            return 'Bar';
        });

        $route('/foo-filter1', function () {
            return 'bar';
        })
            ->filter('filter1', function () {
                return false;
            });


        $route('/foo-filter-g1', function () {
            return 'bar';
        })
            ->filter('filter_g1');

        $route('/foo-filter-g1-pass', function () {
            return 'bar';
        })
            ->filter('filter_g1', function () {
                return true;
            });
    }

    public function priorityGlobals(RouterGlobalsCollector $global, int $priority = 1)
    {
        $global->implicit('gow', 'bar');
    }
}