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
use Opis\Colibri\Attributes\Priority;
use Opis\Colibri\Collectors\{RouteCollector};

class Collector extends BaseCollector
{
    public function routes(RouteCollector $route)
    {
        $route('/bar', static fn() => 'Bar');

        $route('/bar-post', static fn() => 'OK', ['POST']);

        $route('/multiple-methods', static fn() => 'OK', ['GET', 'POST']);

        $route('/bar-opt/{bar?}', static fn($bar) => $bar)
            ->default('bar', 'bar');

        $route('/bar-opt-g1/{g1?}', static fn($g1) => $g1)
            ->default('g1', 'OG1');

        $route('/bar-guard2', static fn() => 'bar')
            ->guard(static fn() => true)
            ->guard(static fn() => false);

        $route->group(static function (RouteCollector $route) {
            $route('/foo', static fn($upName) => $upName);

            $route->group(static function (RouteCollector $route) {
                $route('/foo', static fn($upName) => $upName);

                $route->group(static function (RouteCollector $route) {
                    $route('/', static fn($upName) => $upName)
                        // overwrite implicit again
                        ->default('name', 'group3');
                }, '/baz')
                    // binding should handle & overwrite implicit
                    ->bind('name', static fn($name) => implode('-', str_split($name)))
                    // overwrite binding
                    ->bind('upName', static fn($name) => 'UPPER:' . $name);

            }, '/bar')
                // overwrite
                ->default('name', 'group2');

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
            ->default('name', 'group1')
            ->bind('upName', static fn($name) => strtoupper($name));

    }

    #[Priority(1)]
    public function priorityRoutes(RouteCollector $route)
    {
        $route('/foo', static fn() => 'Bar');

        $route('/foo-filter1', static fn() => 'bar')
            ->filter(static fn() => false);

        $route('/foo-filter-g1-pass', static fn() => 'bar')
            ->filter(static fn() => true);
    }

    public function domainRoutes(RouteCollector $route)
    {
        $route->get('/sub-domain', static fn() => 'sub=1')
            ->domain('sub1.example.com');

        $route->get('/sub-domain', static fn() => 'sub=2')
            ->domain('sub2.example.com');

        $route->get('/sub-domain', static fn($name) => "sub=$name")
            ->domain('sub-{name}.example.com')
            ->where('name', '[a-z]+')
        ;
    }

    public function paramResolverRoutes(RouteCollector $route)
    {
        $route->get('/param-resolver/exception', static fn(string $foo) => $foo);
        $route->get('/param-resolver/default', static fn(string $foo = 'bar') => ['v' => $foo]);
        $route->get('/param-resolver/nullable', static fn(?string $foo) => ['v' => $foo]);
        $route->get('/param-resolver/variadic', static fn(string ...$foo) => ['v' => $foo]);
        $route->get('/param-resolver/union', static fn(string|int|TestClassA $foo) => ['v' => $foo]);
        $route->get('/param-resolver/union-nullable', static fn(string|int|null $foo) => ['v' => $foo]);
        $route->get('/param-resolver/unknown', static fn($foo) => ['v' => $foo]);
    }
}