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

use Opis\Colibri\Stream\{PHPDataStream, PHPMemoryStream, Printer\CopyPrinter};
use PHPUnit\Framework\TestCase;

class CopyPrinterTest extends TestCase
{
    public function testCopy()
    {
        $printer = $this->printer();

        $this->assertEquals(1000, $printer->copy(new PHPDataStream(str_repeat('x', 1000))));
        $this->assertEquals(3, $printer->append("\nok"));

        $this->assertEquals(str_repeat('x', 1000) . "\nok", $printer->stream());
    }

    /**
     * @param string $data
     * @return CopyPrinter
     */
    protected function printer(string $data = ''): CopyPrinter
    {
        return new CopyPrinter(new PHPMemoryStream($data, 'w+'));
    }
}