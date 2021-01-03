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

namespace Opis\Colibri\Test\Stream\Scanner;

use Opis\Colibri\Stream\{PHPDataStream, Scanner\CSVScanner};
use PHPUnit\Framework\TestCase;

class CSVScannerTest extends TestCase
{
    public function testCsv()
    {
        $data = [
            ['abc', 'bcd', 1],
            ['cde', 'def', 2.2],
            ['egt', 'far', null, 123],
        ];

        $scanner = $this->scanner($data);

        $this->assertEquals($data[0], $scanner->next());
        $this->assertEquals($data[1], $scanner->next());
        $this->assertEquals($data[2], $scanner->next());
        $this->assertTrue($scanner->isEOF());
        $this->assertNull($scanner->next());
    }

    public function testCsvAll()
    {
        $data = [
            ['a', 'b', 1],
            ['c', 'd', 2.2],
            ['e', 'f', null],
        ];

        $scanner = $this->scanner($data);

        $list = [];
        foreach ($scanner->all() as $item) {
            $list[] = $item;
        }

        $this->assertEquals($data, $list);
    }

    /**
     * @param array $data
     * @return CSVScanner
     */
    protected function scanner(array $data): CSVScanner
    {
        $data = array_map(function (array $columns): string {
            $columns = array_map(function ($item): string {
                if (is_string($item)) {
                    return '"' . $item . '"';
                }
                if (is_int($item) || is_float($item)) {
                    return $item;
                }
                return '';
            }, $columns);
            return implode(",", $columns);
        }, $data);

        $data = implode("\n", $data);

        return new CSVScanner(new PHPDataStream($data, 'rb', 'text/csv'));
    }
}