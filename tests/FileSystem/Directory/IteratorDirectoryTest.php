<?php
/* ============================================================================
 * Copyright 2019-2021 Zindex Software
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

namespace Opis\Colibri\Test\FileSystem\Directory;

use ArrayIterator;
use PHPUnit\Framework\TestCase;
use Opis\Colibri\FileSystem\FileInfo;
use Opis\Colibri\FileSystem\Directory\IteratorDirectory;
use Opis\Colibri\FileSystem\Stat\{DirectoryStat, FileStat};

class IteratorDirectoryTest extends TestCase
{
    public function testPath()
    {
        $items = new ArrayIterator();

        $dir = new IteratorDirectory('my/path', $items);
        $this->assertEquals('my/path', $dir->path());

        $dir = new IteratorDirectory('my/path/', $items);
        $this->assertEquals('my/path', $dir->path());

        $dir = new IteratorDirectory('/my/path/', $items);
        $this->assertEquals('my/path', $dir->path());
    }

    public function testFiles()
    {
        $path = 'my/path';

        $items = [
            new FileInfo($path . '/file1.txt', new FileStat(0777, 10)),
            new FileInfo($path . '/file2.txt', new FileStat(0777, 20)),
            new FileInfo($path . '/dir', new DirectoryStat(0777)),
        ];

        $items = new ArrayIterator($items);

        $dir = new IteratorDirectory($path, $items);

        $f1 = $dir->next();

        $this->assertInstanceOf(FileInfo::class, $f1);
        $this->assertEquals('file1.txt', $f1->name());
        $this->assertEquals('my/path/file1.txt', $f1->path());
        $this->assertTrue($f1->stat()->isFile());

        $f2 = $dir->next();

        $this->assertInstanceOf(FileInfo::class, $f2);
        $this->assertEquals('file2.txt', $f2->name());
        $this->assertEquals('my/path/file2.txt', $f2->path());
        $this->assertTrue($f2->stat()->isFile());

        $f3 = $dir->next();

        $this->assertInstanceOf(FileInfo::class, $f3);
        $this->assertEquals('dir', $f3->name());
        $this->assertEquals('my/path/dir', $f3->path());
        $this->assertTrue($f3->stat()->isDir());

        $this->assertNull($dir->next());

        $this->assertTrue($dir->rewind());

        $this->assertSame($f1, $dir->next());
        $this->assertSame($f2, $dir->next());
        $this->assertSame($f3, $dir->next());

        $this->assertNull($dir->next());
    }
}