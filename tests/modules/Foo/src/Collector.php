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

namespace Test\Foo;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Http\Request;
use Opis\Colibri\Collectors\{RouterGlobalsCollector, RouteCollector};
use Test\Foo\Middleware\{AuthMiddleware, ToUpperMiddleware, PrefixMiddleware};
use function Opis\Colibri\response;

class Collector extends BaseCollector
{
    public function routerGlobals(RouterGlobalsCollector $global)
    {
        $global->implicit('g1', 'G1');
        $global->implicit('gow', 'foo');

        $global->filter('filter_g1', static fn() => false);

        $global->bind('bind_g1', static fn ($foo1) => 'bind_g1_' . $foo1);

        $global->bind('bind_g2', static fn ($bind_g2) => 'bind_g2_' . $bind_g2);
    }

    public function routes(RouteCollector $route)
    {
        $route('/', static fn() => 'Front page');

        $route('/foo', static fn() => 'Foo');

        $route('/foo-post', static function (Request $request) {
            if ($request->getMethod() === 'GET'){
                return response('Method not allowed', 405);
            }
            return 'OK';
        }, ['POST', 'GET']);

        $route('/foo-opt/{foo?}', static fn ($foo = 'missing') => $foo);

        $route('/foo-opt-g1/{g1?}', static fn($g1) => $g1);

        $route('/foo-filter1', static fn() => 'foo');

        $route('/foo-filter-g1', static fn() => 'foo');

        $route('/foo-filter-g1-pass', static fn() => 'foo');

        $route('/foo-guard1', static fn() => 'foo')
            ->guard('guard1', static fn() => true);

        $route('/foo-guard2', static fn() => 'foo')
            ->guard('guard1', static fn() => true)
            ->guard('guard2', static fn() => true);

        $route('/foo-guard-uk', static fn() => 'foo')
            ->guard('guard_uk1')
            ->guard('guard_uk2');

        $route('/foo/bind/1/{foo1}', static fn ($foo1, $foo2) => $foo1 . $foo2)
            ->bind('foo2', static fn ($foo1) => strtoupper($foo1));

        $route('/foo/bind/2/{foo1}', static fn ($foo1) => $foo1)
            ->bind('foo1', static fn ($foo1) => strtoupper($foo1));

        $route('/foo/bind/3/{foo1}', static fn ($bind_g1) => $bind_g1);

        $route('/foo/bind/4/{bind_g2}', static fn ($bind_g2) => $bind_g2);

        $route('/foo/protected', static fn() => 'foo')
            ->middleware(AuthMiddleware::class);

        $route('/foo/chain/1', static fn() => 'foo')
            ->middleware(ToUpperMiddleware::class, PrefixMiddleware::class);

        $route('/foo/chain/2', static fn() => 'foo')
            ->middleware(PrefixMiddleware::class, ToUpperMiddleware::class);

        $route('/foo-global-overwrite', static fn($gow) => $gow);
    }
}