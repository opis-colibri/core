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

namespace Opis\Colibri\Test\Stream\Wrapper;

use PHPUnit\Framework\TestCase;

class CustomWrapperTest extends TestCase
{
    public function testCustom()
    {
        $this->assertFalse(CustomWrapper::isRegistered());
        $this->assertTrue(CustomWrapper::register());
        $this->assertTrue(CustomWrapper::isRegistered());

        $this->assertEquals('ok', file_get_contents('custom://ok'));
        $this->assertEquals('ok ok ok', file_get_contents('custom://ok ok ok'));
        $this->assertEquals(5, filesize('custom://12345'));

        $this->assertTrue(CustomWrapper::unregister());
        $this->assertFalse(CustomWrapper::isRegistered());
        $this->assertFalse(CustomWrapper::unregister());
    }
}