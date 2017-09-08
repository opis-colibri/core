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

namespace Opis\Colibri\Test;

use Opis\Colibri\Application;
use Opis\Colibri\Containers\RouteCollector;
use Opis\Http\Request;
use Opis\Http\Response;
use PHPUnit\Framework\TestCase;

class HttpRoutingTest extends TestCase
{
    /** @var  Application */
    protected $app;

    /** @var  RouteCollector */
    public $route;

    public function setUp()
    {
        $this->app = new Application(__DIR__, include __DIR__ . '/../vendor/autoload.php');
        $this->app->bootstrap();
        $this->route = (function($data){
            $this->dataObject = $data;
            return $this;
        })->call(new RouteCollector(), $this->app->getCollector()->getRoutes());

        $this->route->get('/', function(){
            return 'home';
        });

        $this->route->get('/foo', function(){
            return 'foo';
        });

        $this->route->get('/bar', function(){
            return 'bar';
        })->callback('test', function(){
            return false;
        })->access('test');

        $this->route->get('/param1/{x}', function($x){
            return $x;
        });

        $this->route->get('/param2/{x?}', function($x = 'foo'){
           return $x;
        });

        $this->route->get('/param3/{x?}', function($x){
            return $x;
        })->implicit('x', 'foo');

        $this->route->get('/bind/{x}', function($x){
           return $x;
        })
        ->bind('x', function($x){
            return strtoupper($x);
        });
    }

    private function route(Request $request): Response
    {
        return $this->app->run($request);
    }

    public function testHomePage()
    {
        $response = $this->route(Request::create('/'));
        $this->assertEquals('home', $response->getBody());
    }

    public function testRoute()
    {
        $response = $this->route(Request::create('/foo'));
        $this->assertEquals('foo', $response->getBody());
    }

    public function test404()
    {
        $response = $this->route(Request::create('/404'));
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test403()
    {
        $response = $this->route(Request::create('/bar'));
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testParam()
    {
        $response = $this->route(Request::create('/param1/foo'));
        $this->assertEquals('foo', $response->getBody());
    }

    public function testOptionalParam()
    {
        $response = $this->route(Request::create('/param2'));
        $this->assertEquals('foo', $response->getBody());
        $response = $this->route(Request::create('/param2/bar'));
        $this->assertEquals('bar', $response->getBody());
    }

    public function testOptionalImplicitParam()
    {
        $response = $this->route(Request::create('/param2'));
        $this->assertEquals('foo', $response->getBody());
        $response = $this->route(Request::create('/param2/bar'));
        $this->assertEquals('bar', $response->getBody());
    }

    public function testBindParam()
    {
        $response = $this->route(Request::create('/bind/foo'));
        $this->assertEquals('FOO', $response->getBody());
    }
}