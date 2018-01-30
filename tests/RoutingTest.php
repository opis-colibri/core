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


class RoutingTest extends BaseClass
{
    public function testFrontPage()
    {
        $result = $this->exec('/');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Front page', $result->getBody());
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
        $this->assertEquals('Bar', $result->getBody());
    }

    public function testPriorityRoutes()
    {
        $result = $this->exec('/foo');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('Bar', $result->getBody());
    }

    public function testHttpMethod()
    {
        $result = $this->exec('/foo-post', 'POST');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', $result->getBody());
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
        $this->assertEquals('OK', $result->getBody());
    }

    public function testMultipleMethods1()
    {
        $result = $this->exec('/multiple-methods');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', $result->getBody());
    }

    public function testMultipleMethods2()
    {
        $result = $this->exec('/multiple-methods', 'POST');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OK', $result->getBody());
    }

    public function testOptionalPathSegment1()
    {
        $result = $this->exec('/foo-opt');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('missing', $result->getBody());
    }

    public function testOptionalPathSegment2()
    {
        $result = $this->exec('/foo-opt/ok');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('ok', $result->getBody());
    }

    public function testOptionalPathSegmentImplicit1()
    {
        $result = $this->exec('/bar-opt');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bar', $result->getBody());
    }

    public function testOptionalPathSegmentImplicit2()
    {
        $result = $this->exec('/bar-opt/ok');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('ok', $result->getBody());
    }

    public function testImplicitGlobal1()
    {
        $result = $this->exec('/foo-opt-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('G1', $result->getBody());
    }

    public function testImplicitGlobal1Override()
    {
        $result = $this->exec('/bar-opt-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('OG1', $result->getBody());
    }

    public function testFilter1()
    {
        $result = $this->exec('/foo-filter1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', $result->getBody());
    }

    public function testFilterGlobal1()
    {
        $result = $this->exec('/foo-filter-g1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', $result->getBody());
    }

    public function testFilterGlobal1OverridePass()
    {
        $result = $this->exec('/foo-filter-g1-pass');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('bar', $result->getBody());
    }

    public function testSingleGuardPass()
    {
        $result = $this->exec('/foo-guard1');

        $this->assertEquals(200, $result->getStatusCode());
        $this->assertEquals('foo', $result->getBody());
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
        $this->assertEquals('foo', $result->getBody());
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
        $this->assertEquals('foo', $result->getBody());
    }
}