<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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
use function Opis\Colibri\Functions\{
    response
};
use Opis\Colibri\ItemCollectors\RouteCollector;
use Opis\Http\Request;
use Test\Foo\Middleware\AuthMiddleware;
use Test\Foo\Middleware\ToUpperMiddleware;
use Test\Foo\Middleware\PrefixMiddleware;

class Collector extends BaseCollector
{
    public function routes(RouteCollector $route)
    {
        $route->implicit('g1', 'G1');

        $route->callback('filter_g1', function () {
            return false;
        });

        $route->bind('bind_g1', function ($foo1) {
            return 'bind_g1_' . $foo1;
        });

        $route->bind('bind_g2', function ($bind_g2) {
            return 'bind_g2_' . $bind_g2;
        });

        $route('/', function () {
            return 'Front page';
        });

        $route('/foo', function () {
            return 'Foo';
        });

        $route('/foo-post', function (Request $request) {
            if ($request->getMethod() === 'GET'){
                return response('Method not allowed', 405);
            }
            return 'OK';
        }, ['POST', 'GET']);

        $route('/foo-opt/{foo?}', function ($foo = 'missing') {
            return $foo;
        });

        $route('/foo-opt-g1/{g1?}', function ($g1) {
            return $g1;
        });

        $route('/foo-filter1', function () {
            return 'foo';
        });

        $route('/foo-filter-g1', function () {
            return 'foo';
        });

        $route('/foo-filter-g1-pass', function () {
            return 'foo';
        });

        $route('/foo-guard1', function () {
            return 'foo';
        })->callback('guard1', function () {
            return true;
        })->guard('guard1');

        $route('/foo-guard2', function () {
            return 'foo';
        })->callback('guard1', function () {
            return true;
        })->callback('guard2', function () {
            return true;
        })->guard('guard1', 'guard2');

        $route('/foo-guard-uk', function () {
            return 'foo';
        })->guard('guard_uk1', 'guard_uk2');

        $route('/foo/bind/1/{foo1}', function ($foo1, $foo2) {
            return $foo1 . $foo2;
        })->bind('foo2', function ($foo1) {
            return strtoupper($foo1);
        });

        $route('/foo/bind/2/{foo1}', function ($foo1) {
            return $foo1;
        })->bind('foo1', function ($foo1) {
            return strtoupper($foo1);
        });

        $route('/foo/bind/3/{foo1}', function ($bind_g1) {
            return $bind_g1;
        });

        $route('/foo/bind/4/{bind_g2}', function ($bind_g2) {
            return $bind_g2;
        });

        $route('/foo/protected', function () {
            return 'foo';
        })->middleware(AuthMiddleware::class);

        $route('/foo/chain/1', function () {
            return 'foo';
        })->middleware(ToUpperMiddleware::class, PrefixMiddleware::class);

        $route('/foo/chain/2', function () {
            return 'foo';
        })->middleware(PrefixMiddleware::class, ToUpperMiddleware::class);

    }
}