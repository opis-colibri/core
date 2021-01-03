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

use Opis\Colibri\Stream\{PHPDataStream, Scanner\StandardScanner};
use PHPUnit\Framework\TestCase;

class StandardScannerTest extends TestCase
{
    public function testStandardScanner()
    {
        $scanner = $this->scanner("a 2 bc 3.0", "test rest of line", "123 123.4 -123.4");

        $this->assertEquals(["a", 2, "bc", 3.0], $scanner->scan("%s %d %s %f"));
        $this->assertFalse($scanner->isEOF());
        $this->assertEquals("test ", $scanner->read(5));
        $this->assertFalse($scanner->isEOF());
        $this->assertEquals("rest of line", $scanner->readLine());
        $this->assertFalse($scanner->isEOF());
        $this->assertEquals(["123", 123.4, -123], $scanner->scan("%s %f %d"));
        $this->assertTrue($scanner->isEOF());
    }

    /**
     * @param string ...$lines
     * @return StandardScanner
     */
    protected function scanner(string ...$lines): StandardScanner
    {
        return new StandardScanner(new PHPDataStream(implode("\n", $lines)));
    }
}