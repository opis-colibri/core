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

namespace Opis\Colibri\Test\FileSystem;

use PHPUnit\Framework\TestCase;
use Opis\Colibri\Stream\Stream;
use Opis\Colibri\FileSystem\{FileInfo, Directory};
use Opis\Colibri\FileSystem\Handler\{FileSystemHandler, SearchHandler};

abstract class AbstractHandler extends TestCase
{
    protected static ?FileSystemHandler $handler = null;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        self::$handler = static::handler();
    }

    abstract public static function handler(): FileSystemHandler;

    /**
     * @return FileSystemHandler|SearchHandler
     */
    protected function h(): FileSystemHandler|SearchHandler
    {
        return self::$handler;
    }

    public function testUnlink()
    {
        $this->assertFalse($this->h()->unlink('inexistent'));
        $this->assertTrue($this->h()->unlink('to-be-removed.txt'));
        $this->assertNull($this->h()->info('to-be-removed.txt'));
        $this->assertFalse($this->h()->unlink('to-be-removed/inexistent.txt'));
        $this->assertFalse($this->h()->unlink('to-be-removed'), 'Cannot unlink dir');
    }

    public function testRmDir()
    {
        $this->assertFalse($this->h()->rmdir('inexistent'));
        $this->assertFalse($this->h()->rmdir('to-be-removed', false));
        $this->assertTrue($this->h()->rmdir('to-be-removed', true));
        $this->assertNull($this->h()->info('to-be-removed'));
    }

    public function testCopyFiles()
    {

        $r = $this->h()->copy('file-inexistent.txt', 'file-inexistent-copy.txt', false);
        $this->assertNull($r);


        $r = $this->h()->copy('file1.txt', 'file1-copy.txt', false);
        $this->assertInstanceOf(FileInfo::class, $r);
        $this->assertEquals('file1-copy.txt', $r->name());
        $this->assertTrue($r->stat()->isFile());
        $this->assertEquals(14, $r->stat()->size());

        $this->assertEquals((string)$this->h()->file('file1.txt'), (string)$this->h()->file('file1-copy.txt'));

        $r = $this->h()->copy('file1.txt', 'file1-copy.txt', false);
        $this->assertNull($r);
    }

    public function testCopyDirs()
    {
        $r = $this->h()->copy('dir-inexistent', 'dir-copy', false);
        $this->assertNull($r);

        $r = $this->h()->copy('dir', 'dir-copy', false);
        $this->assertInstanceOf(FileInfo::class, $r);
        $this->assertEquals('dir-copy', $r->name());
        $this->assertTrue($r->stat()->isDir());

        $items = ['append.txt', 'dir-file1.txt'];

        $dir = $this->h()->dir('dir-copy');
        $this->assertInstanceOf(Directory::class, $dir);

        $items2 = [];
        while ($item = $dir->next()) {
            $items2[] = $item->name();
        }

        // We must sort the items, order is not relevant
        sort($items2);
        $this->assertEquals($items, $items2);

        $this->assertEquals((string)$this->h()->file('dir/dir-file1.txt'),
            (string)$this->h()->file('dir-copy/dir-file1.txt'));

        $this->assertTrue($this->h()->rmdir('dir-copy', true));
    }

    public function testCopySubDirs()
    {
        $r = $this->h()->copy('dir2', 'dir-new-sub1');
        $this->assertInstanceOf(FileInfo::class, $r);
        $this->assertEquals('dir-new-sub1', $r->name());

        $dir_source = $this->h()->dir('dir2');
        $dir_copy = $this->h()->dir('dir-new-sub1');

        while ($item = $dir_source->next()) {
            $item2 = $dir_copy->next();
            $this->assertInstanceOf(FileInfo::class, $item2);
            $this->assertEquals($item->name(), $item2->name());
            $this->assertEquals($item->stat()->mode(), $item2->stat()->mode());
        }

        $this->assertNull($dir_source->next());
        $this->assertNull($dir_copy->next());

        $this->assertTrue($this->h()->rmdir('dir-new-sub1'));
    }

    public function testMkDir()
    {
        $this->assertNull($this->h()->mkdir('dir'));
        $this->assertNull($this->h()->mkdir('dir/sub-dir/test', 0777, false));

        $r = $this->h()->mkdir('my-dir');
        $this->assertInstanceOf(FileInfo::class, $r);
        $this->assertTrue($r->stat()->isDir());
        $this->assertEquals('my-dir', $r->name());
        $this->assertInstanceOf(Directory::class, $this->h()->dir('my-dir'));
        $this->assertTrue($this->h()->rmdir('my-dir'));

        $r = $this->h()->mkdir('dir/sub-dir/test');
        $this->assertInstanceOf(FileInfo::class, $r);
        $this->assertEquals('test', $r->name());
        $this->assertTrue($r->stat()->isDir());
        $this->assertInstanceOf(Directory::class, $this->h()->dir('dir/sub-dir'));
        $this->assertInstanceOf(Directory::class, $this->h()->dir('dir/sub-dir/test'));

        $this->assertTrue($this->h()->rmdir('dir/sub-dir'));
    }

    public function testFileRead()
    {
        $this->assertNull($this->h()->file('inexistent'));
        $this->assertNull($this->h()->file('dir'));
        $this->assertEquals('file1 contents', $this->h()->file('file1.txt'));
        $this->assertEquals('file2 contents', $this->h()->file('file2.txt'));

        $this->assertEquals('dir-file1 contents', $this->h()->file('dir/dir-file1.txt'));
        $this->assertNull($this->h()->file('dir/inexistent'));
    }

    public function testFileWrite()
    {
        $f = $this->h()->file('inexistent', 'wb');
        $this->assertInstanceOf(Stream::class, $f);
        $f->write('some content');
        $f->flush();
        $f->close();

        $this->assertEquals('some content', $this->h()->file('inexistent'));

        $this->assertTrue($this->h()->unlink('inexistent'));

        $f = $this->h()->file('dir/append.txt', 'a+');

        $f->write('second line');
        $f->flush();
        $f->close();

        $this->assertEquals("first line\nsecond line", $this->h()->file('dir/append.txt'));

        $f = $this->h()->file('new-dir/sub1/sub2/sub3/file.txt', 'wb');

        $f->write('some contents');
        $f->close();

        $this->assertEquals('some contents', $this->h()->file('new-dir/sub1/sub2/sub3/file.txt'));

        $this->assertTrue($this->h()->rmdir('new-dir'));
        $this->assertNull($this->h()->file('new-dir/sub1/sub2/sub3/file.txt'));
    }

    public function testSearch()
    {
        $filter = function (FileInfo $f) {
            return $f->stat()->isFile();
        };

        $list = [];
        foreach ($this->h()->search('dir2', 'sub', $filter) as $f) {
            $list[] = $f->name();
        }
        $this->assertEquals([], $list);

        $list = [];
        foreach ($this->h()->search('dir2', 'sub', $filter, null, 1) as $f) {
            $list[] = $f->name();
        }

        sort($list);
        $this->assertEquals(['file-sub1.txt', 'file-sub2.txt'], $list);
    }
}