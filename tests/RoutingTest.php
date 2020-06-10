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

namespace Opis\Colibri\Test;

use Opis\Colibri\Testing\Builders\ApplicationBuilder;

class RoutingTest extends BaseAppTestCase
{

    protected static function setupApp(ApplicationBuilder $builder): void
    {
        $builder->addEnabledModuleFromPath(__DIR__ . '/modules/Foo');
        $builder->addEnabledModuleFromPath(__DIR__ . '/modules/Bar');
    }

    public function testFrontPage()
    {
        $result = $this->exec('/');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Front page', (string)$result->getBody());
    }

    public function testNotFoundPage()
    {
        $result = $this->exec('/page-not-found');

        $this->assertEquals(404, $result->getStatusCode());
    }


    public function testBarPage()
    {
        $result = $this->exec('/bar');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Bar', (string)$result->getBody());
    }

    public function testPriorityRoutes()
    {
        $result = $this->exec('/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Bar', (string)$result->getBody());
    }

    public function testHttpMethod()
    {
        $result = $this->exec('/foo-post', 'POST');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', (string)$result->getBody());
    }

    public function testHttpMethodFail()
    {
        $result = $this->exec('/foo-post', 'GET');

        $this->assertEquals(405, $result->getStatusCode());
    }

    public function testHttpMethod2()
    {
        $result = $this->exec('/bar-post', 'POST');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', (string)$result->getBody());
    }

    public function testMultipleMethods1()
    {
        $result = $this->exec('/multiple-methods');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', (string)$result->getBody());
    }

    public function testMultipleMethods2()
    {
        $result = $this->exec('/multiple-methods', 'POST');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', (string)$result->getBody());
    }

    public function testOptionalPathSegment1()
    {
        $result = $this->exec('/foo-opt');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('missing', (string)$result->getBody());
    }

    public function testOptionalPathSegment2()
    {
        $result = $this->exec('/foo-opt/ok');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('ok', (string)$result->getBody());
    }

    public function testOptionalPathSegmentImplicit1()
    {
        $result = $this->exec('/bar-opt');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bar', (string)$result->getBody());
    }

    public function testOptionalPathSegmentImplicit2()
    {
        $result = $this->exec('/bar-opt/ok');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('ok', (string)$result->getBody());
    }

    public function testImplicitGlobal1()
    {
        $result = $this->exec('/foo-opt-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('G1', (string)$result->getBody());
    }

    public function testGlobalOverwrite()
    {
        $result = $this->exec('/foo-global-overwrite');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bar', (string)$result->getBody());
    }

    public function testImplicitGlobal1Override()
    {
        $result = $this->exec('/bar-opt-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OG1', (string)$result->getBody());
    }

    public function testFilter1()
    {
        $result = $this->exec('/foo-filter1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', (string)$result->getBody());
    }

    public function testFilterGlobal1()
    {
        $result = $this->exec('/foo-filter-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', (string)$result->getBody());
    }

    public function testFilterGlobal1OverridePass()
    {
        $result = $this->exec('/foo-filter-g1-pass');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bar', (string)$result->getBody());
    }

    public function testSingleGuardPass()
    {
        $result = $this->exec('/foo-guard1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', (string)$result->getBody());
    }

    public function testSingleGuardFail()
    {
        $result = $this->exec('/bar-guard1');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testMultiGuardPass()
    {
        $result = $this->exec('/foo-guard2');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', (string)$result->getBody());
    }

    public function testMultiGuardFail()
    {
        $result = $this->exec('/bar-guard2');

        $this->assertEquals(404, $result->getStatusCode());
    }

    public function testGuardUnknown()
    {
        $result = $this->exec('/foo-guard-uk');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', (string)$result->getBody());
    }

    public function testBind1()
    {
        $result = $this->exec('/foo/bind/1/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('fooFOO', (string)$result->getBody());
    }

    public function testBind2()
    {
        $result = $this->exec('/foo/bind/2/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('FOO', (string)$result->getBody());
    }

    public function testBindGlobal1()
    {
        $result = $this->exec('/foo/bind/3/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bind_g1_foo', (string)$result->getBody());
    }

    public function testBindGlobal2()
    {
        $result = $this->exec('/foo/bind/4/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bind_g2_foo', (string)$result->getBody());
    }

    public function testMiddlewareAuth()
    {
        $result = $this->exec('/foo/protected');

        $this->assertEquals(401, $result->getStatusCode());
        $this->assertEquals('Unauthorized', (string)$result->getBody());
    }

    public function testMiddlewareChain1()
    {
        $result = $this->exec('/foo/chain/1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('PREFIX-FOO', (string)$result->getBody());
    }

    public function testMiddlewareChain2()
    {
        $result = $this->exec('/foo/chain/2');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('prefix-FOO', (string)$result->getBody());
    }

    public function testGroup1()
    {
        $result = $this->exec('/bar-group/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('GROUP1', (string)$result->getBody());
    }

    public function testGroup2()
    {
        $result = $this->exec('/bar-group/bar/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('GROUP2', (string)$result->getBody());
    }

    public function testGroup3()
    {
        $result = $this->exec('/bar-group/bar/baz/');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('UPPER:g-r-o-u-p-3', (string)$result->getBody());
    }

    public function testGroup4()
    {
        // public
        for ($i = 1; $i <= 3; $i++) {
            $result = $this->exec('/bar-group/type' . $i . '/public');

            $this->assertEquals(200, $result->getStatusCode());
            $this->assertEquals('type' . $i, (string)$result->getBody());
        }

        // secret
        for ($i = 1; $i <= 2; $i++) {
            $result = $this->exec('/bar-group/type' . $i . '/secret');

            $this->assertEquals(200, $result->getStatusCode());
            $this->assertEquals('secret:type' . $i, (string)$result->getBody());
        }

        // should not have secret available
        $result = $this->exec('/bar-group/type3/secret');
        $this->assertEquals(404, $result->getStatusCode());

        // should not match
        $result = $this->exec('/bar-group/type4/public');
        $this->assertEquals(404, $result->getStatusCode());

        // intruder
        $result = $this->exec('/bar-group/type4/intruder');
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('intruder:type4', (string)$result->getBody());
    }

}