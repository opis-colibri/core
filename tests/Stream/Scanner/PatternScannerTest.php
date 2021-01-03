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

use Opis\Colibri\Stream\{DataStream, Scanner\PatternScanner};
use PHPUnit\Framework\TestCase;

class PatternScannerTest extends TestCase
{

    public function testPattern1()
    {
        $scanner = $this->scanner('this is a short sentence');
        $pattern = '/\w{2,5}/';

        $this->assertEquals('this', $scanner->next($pattern));
        $this->assertNull($scanner->skipped());
        $this->assertFalse($scanner->isEOF());

        $this->assertEquals('is', $scanner->next($pattern));
        $this->assertEquals(' ', $scanner->skipped());
        $this->assertFalse($scanner->isEOF());

        $this->assertEquals('short', $scanner->next($pattern));
        $this->assertEquals(' a ', $scanner->skipped());
        $this->assertFalse($scanner->isEOF());

        $this->assertEquals('sente', $scanner->next($pattern));
        $this->assertEquals(' ', $scanner->skipped());
        $this->assertFalse($scanner->isEOF());

        $this->assertEquals('nce', $scanner->next($pattern));
        $this->assertNull($scanner->skipped());
        $this->assertTrue($scanner->isEOF());
    }

    public function testPattern2()
    {
        $data = ['these', 'are', 'some', 'words'];
        $scanner = $this->scanner(implode(" \v \r \n \t ", $data));

        $list = [];

        while (!$scanner->isEOF()) {
            $list[] = $scanner->next('/\S+/');
        }

        $this->assertEquals($data, $list);
    }

    public function testPattern3()
    {
        $scanner = $this->scanner("a 1 b 2 c 3 d 4 e 5 f 6 z");

        $list = [];

        $list[] = $scanner->next('/\d/');
        $list[] = $scanner->next('/\d/');
        $list[] = $scanner->next('/\w/');
        $list[] = $scanner->next('/[a-z]/');
        $list[] = $scanner->next('/\d/');
        $list[] = $scanner->next('/[z]/');

        $this->assertTrue($scanner->isEOF());
        $this->assertEquals(['1', '2', 'c', 'd', '4', 'z'], $list);
    }

    public function testTokens()
    {
        $scanner = $this->scanner("red: #ff0000, green:=#00FF00; BLUE=#0000Ff", [
            'name' => '/\w+/',
            'hex' => '/#[a-f0-9]{6}/i',
        ]);

        $list = [];
        $skipped = [];
        while (!$scanner->isEOF()) {
            $name = $scanner->token('name');
            $skipped[] = $scanner->skipped();
            $hex = $scanner->token('hex');
            $skipped[] = $scanner->skipped();
            $list[$name] = $hex;
        }

        $this->assertEquals([
            'red' => '#ff0000',
            'green' => '#00FF00',
            'BLUE' => '#0000Ff',
        ], $list);

        $this->assertEquals([
            null,
            ': ',
            ', ',
            ':=',
            '; ',
            '=',
        ], $skipped);
    }

    public function testMatch()
    {
        $data = "junk [no-value] [not+matched] [invalid=] junk2 [abc=23] [def=this is matched] junk [lines=a\nb]";
        $scanner = $this->scanner($data, [
            // [name] or [name=value]
            'item' => '/\[(?<name>[a-z-]+)(?:\=(?<value>[^]]+))?\]/i',
        ]);

        $list = [];
        $skipped = [];
        while (!$scanner->isEOF()) {
            $m = $scanner->tokenMatch('item');
            $skipped[] = $scanner->skipped();

            $key = $m['name'][0];
            $value = $m['value'][0] ?? null;

            $list[$key] = $value;
        }

        $this->assertEquals([
            'no-value' => null,
            'abc' => '23',
            'def' => 'this is matched',
            'lines' => "a\nb",
        ], $list);

        $this->assertEquals([
            'junk ',
            ' [not+matched] [invalid=] junk2 ',
            ' ',
            ' junk ',
        ], $skipped);
    }

    /**
     * @param string $data
     * @param array|null $tokens
     * @return PatternScanner
     */
    protected function scanner(string $data, ?array $tokens = null): PatternScanner
    {
        return new PatternScanner(new DataStream($data), $tokens);
    }
}