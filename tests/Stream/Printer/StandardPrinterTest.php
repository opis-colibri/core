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

namespace Opis\Colibri\Test\Stream\Printer;

use Opis\Colibri\Stream\{PHPMemoryStream, Printer\StandardPrinter};
use PHPUnit\Framework\TestCase;

class StandardPrinterTest extends TestCase
{

    public function testWrite()
    {
        $printer = $this->printer();

        $stream = $printer->stream();

        $this->assertEquals(0, $stream->size());
        $printer->print("%s=%d\n", "a", 1);
        $this->assertEquals(4, $stream->size());
        $printer->print("%s=%d\n", "bcd", 22);
        $this->assertEquals(4 + 7, $stream->size());
        $printer->write("some string\n");
        $printer->write("other string");
        $printer->write(" on same line");

        $this->assertEquals("a=1\nbcd=22\nsome string\nother string on same line", $stream);
    }

    /**
     * @param string $data
     * @return StandardPrinter
     */
    protected function printer(string $data = ''): StandardPrinter
    {
        return new StandardPrinter(new PHPMemoryStream($data, 'w+'));
    }
}