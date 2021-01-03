<?php
/* ============================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Test\Http;

use Opis\Stream\{PHPDataStream, ResourceStream};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    public function testData()
    {
        $data = "some---data---final";

        $stream = new PHPDataStream($data);

        $this->assertEquals(strlen($data), $stream->size());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertFalse($stream->isWritable());

        $this->assertEquals('some', $stream->read(4));

        $stream->seek(3, SEEK_CUR);

        $this->assertEquals($data, $stream); // Should restore cursor position

        $this->assertEquals('data', $stream->read(4));

        $this->assertFalse($stream->isEOF());

        $this->assertEquals('---final', $stream->readToEnd());

        $this->assertTrue($stream->isEOF());

        $stream->close();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isSeekable());
    }

    public function testResource()
    {
        $data = "some---data---final";

        $stream = new ResourceStream('php://memory', 'w+');

        $stream->write($data);
        $stream->rewind();

        $this->assertEquals(strlen($data), $stream->size());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isWritable());

        $this->assertEquals('some', $stream->read(4));

        $stream->seek(3, SEEK_CUR);

        $this->assertEquals($data, $stream); // Should restore cursor position

        $this->assertEquals('data', $stream->read(4));

        $this->assertFalse($stream->isEOF());

        $this->assertEquals('---final', $stream->readToEnd());

        $this->assertTrue($stream->isEOF());

        $stream->close();

        $this->assertFalse($stream->isReadable());
        $this->assertFalse($stream->isWritable());
        $this->assertFalse($stream->isSeekable());
    }
}