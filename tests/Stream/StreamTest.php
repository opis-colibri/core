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

namespace Opis\Colibri\Test\Stream;

use Opis\Colibri\Stream\{DataStream, PHPMemoryStream, Stream, PHPDataStream, ResourceStream};
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    /**
     * @dataProvider readableStreamProvider
     */
    public function testReadable(callable $factory)
    {
        $data = 'this is data';
        /** @var Stream $stream */
        $stream = $factory($data);

        $this->assertTrue($stream->isReadable());

        $this->assertFalse($stream->isClosed());
        $this->assertFalse($stream->isEOF());

        $this->assertEquals(0, $stream->tell());
        $this->assertEquals(strlen($data), $stream->size());


        $this->assertEquals('this', $stream->read(4));
        $this->assertEquals(4, $stream->tell());
        $this->assertEquals(' ', $stream->read(1));
        $this->assertEquals('is data', $stream->readToEnd());
        $this->assertTrue($stream->isEOF());

        $this->assertNull($stream->read());

        $stream->close();
        $this->assertTrue($stream->isClosed());
    }

    /**
     * @dataProvider readableStreamProvider
     */
    public function testReadLine(callable $factory)
    {
        $lines = [
            'a',
            'b',
            'c',
        ];

        /** @var Stream $stream */
        $stream = $factory(implode("\n", $lines));

        $list = [];
        while (($l = $stream->readLine()) !== null) {
            $list[] = $l;
        }

        $this->assertEquals($lines, $list);
    }

    /**
     * @dataProvider writableStreamProvider
     */
    public function testWritable(callable $factory)
    {
        /** @var Stream $stream */
        $stream = $factory('w+');

        $this->assertTrue($stream->isWritable());
        $this->assertTrue($stream->isSeekable());
        $this->assertFalse($stream->isClosed());

        $this->assertEquals(4, $stream->write('this'));
        $this->assertEquals(1, $stream->write('#'));

        $this->assertTrue($stream->seek(-1, ResourceStream::SEEK_CUR));
        $this->assertEquals(4, $stream->write(' is '));
        $this->assertEquals(4, $stream->write('data'));

        $this->assertEquals('this is data', $stream);
    }

    /**
     * @dataProvider seekableStreamProvider
     */
    public function testSeek(callable $factory)
    {
        $data = 'this is data';
        /** @var Stream $stream */
        $stream = $factory($data, 'r');

        $this->assertTrue($stream->isSeekable());

        $stream->seek(5, ResourceStream::SEEK_SET);

        $this->assertEquals('is', $stream->read(2));

        $stream->seek(1, ResourceStream::SEEK_CUR);

        $this->assertEquals('data', $stream->read(4));

        $stream->rewind();

        $this->assertEquals('this', $stream->read(4));

        $pos = $stream->tell();

        $this->assertEquals('this is data', $stream);

        $this->assertEquals($pos, $stream->tell());

        $this->assertEquals(strlen($data), $stream->size());
    }

    public function readableStreamProvider(): array
    {
        $list[] = [
            static fn ($data) => new PHPMemoryStream($data, 'rb+'),
        ];

        $list[] = [
            static fn ($data) => new ResourceStream('data://text/plain,' . $data, 'r'),
        ];

        $list[] = [
            static fn ($data) => new PHPDataStream($data, 'r'),
        ];

        $list[] = [
            static fn ($data) => new DataStream($data, 'r'),
        ];

        return $list;
    }

    public function seekableStreamProvider(): array
    {
        return $this->readableStreamProvider();
    }

    public function writableStreamProvider(): array
    {
        // PHPDataStream is readonly

        $list[] = [
            static fn () => new PHPMemoryStream('', 'w+'),
        ];

        $list[] = [
            static fn () => new ResourceStream(tempnam(sys_get_temp_dir(), 'opis-stream-'), 'w+'),
        ];

        $list[] = [
            static fn () => new DataStream('', 'w+'),
        ];

        return $list;
    }
}