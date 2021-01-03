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

use Opis\Colibri\Stream\{PHPMemoryStream, Printer\CSVPrinter};
use PHPUnit\Framework\TestCase;

class CSVPrinterTest extends TestCase
{
    public function testCSV()
    {
        $printer = $this->printer();
        $printer->append(['Name', 'Value']);
        $printer->append(['a b', 1]);
        $printer->append(['b c', 'str val']);
        $printer->append(['c', -2.2]);
        $printer->append([3.14, "PI"]);
        $printer->append(['nil', null]);

        $csv = implode("\n", [
                'Name,Value',
                '"a b",1',
                '"b c","str val"',
                'c,-2.2',
                '3.14,PI',
                'nil,',
            ]) . "\n";

        $this->assertEquals($csv, $printer->stream());
    }

    /**
     * @param string $data
     * @return CSVPrinter
     */
    protected function printer(string $data = ''): CSVPrinter
    {
        return new CSVPrinter(new PHPMemoryStream($data, 'w+'));
    }
}