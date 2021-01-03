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

use Opis\Colibri\Stream\{PHPMemoryStream, Wrapper\PHPCodeStreamWrapper};
use PHPUnit\Framework\TestCase;

class PHPWrapperTest extends TestCase
{
    public function testInclude()
    {
        $this->assertEquals([1, 2, 3], PHPCodeStreamWrapper::include('<?php return [1, 2, 3];'));

        $this->assertEquals([1, 2, 'ok'], PHPCodeStreamWrapper::include('<?php return [1, 2, $var];', [
            'var' => 'ok',
        ]));
    }

    public function testSubInclude()
    {
        $this->assertEquals([1, 2, 'sub-passed'], PHPCodeStreamWrapper::include(
            '<?php return ' . PHPCodeStreamWrapper::class . '::include(\'<?php return [1, 2, $passed];\', ["passed" => "sub-" . $passed]);',
            ['passed' => 'passed']));
    }

    public function testIncludeExceptions()
    {
        // Parse error
        $this->assertTrue(PHPCodeStreamWrapper::include('<?php return invalid code') instanceof \ParseError);

        $this->assertTrue(PHPCodeStreamWrapper::include('<?php return 1 / 0;') instanceof \Throwable);
    }

    public function testTemplate()
    {
        $var = 5;
        $this->assertEquals('value is ' . $var, PHPCodeStreamWrapper::template('value is <?= $value ?>', [
            'value' => $var,
        ]));
    }

    public function testStreamTemplate()
    {
        $stream = new PHPMemoryStream('', 'w+');

        $this->assertTrue(PHPCodeStreamWrapper::streamTemplate($stream, 'first line'));

        $this->assertTrue($stream->write("\n") !== null);

        $this->assertTrue(PHPCodeStreamWrapper::streamTemplate($stream, '<?= $value ?>', [
            'value' => 'second line',
        ]));

        $this->assertEquals("first line\nsecond line", $stream);
    }
}