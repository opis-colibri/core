<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

        $this->app->getHttpRouter()->getRouteCollection()
            ->notFound(function (){
               return 404;
            })
            ->accessDenied(function (){
                return 403;
            });
    }

    public function testRoute()
    {
        $this->route->get('/', function(){
            return 'ok';
        });

        $this->route->get('/foo', function(){
            return 'foo';
        });

        $this->route->get('/bar', function(){
            return 'bar';
        })->filter('test', function(){
            return false;
        })->access('test');
        $this->route->get('/param/{x}', function($x){
            return $x;
        });

        $this->assertEquals('ok', $this->app->run(Request::create('/')));
        $this->assertEquals('foo', $this->app->run(Request::create('/foo')));
        $this->assertEquals(404, $this->app->run(Request::create('/404')));
        $this->assertEquals(403, $this->app->run(Request::create('/bar')));
        $this->assertEquals('foo', $this->app->run(Request::create('/param/foo')));
    }

}