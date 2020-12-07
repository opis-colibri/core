<?php
/* ============================================================================
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
use function Opis\Colibri\view;

class ViewsTest extends BaseAppTestCase
{

    protected static function setupApp(ApplicationBuilder $builder): void
    {
        $builder->createEnabledTestModule(
            'test/views',
            'Test\\Views',
            __DIR__ . '/code/test-views',
            'Test\\Views\\Collector'
        );
    }

    public function testValue()
    {
        $this->assertEquals("Received: OK", view('test.value', ['value' => 'OK']));
    }

    public function testSubView()
    {
        $this->assertEquals('Rendering test.value => Received: Hello', view('test.subview', [
            'name' => 'test.value',
            'args' => [
                'value' => 'Hello'
            ]
        ]));
    }

    public function testSubViewRecursive()
    {
        $this->assertEquals('Rendering test.subview => Rendering test.value => Received: OK', view('test.subview', [
            'name' => 'test.subview',
            'args' => [
                'name' => 'test.value',
                'args' => [
                    'value' => 'OK'
                ]
            ]
        ]));
    }
}