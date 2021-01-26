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
use Opis\Colibri\Stream\PHPDataStream;
use Opis\Colibri\FileSystem\{FileInfo, MountManager};
use Opis\Colibri\FileSystem\Handler\LocalFileHandler;

class MountManagerTest extends TestCase
{
    use FilesTrait;

    protected static MountManager $manager;
    protected static string $dir;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        self::$dir = self::copyFiles(__DIR__ . '/files/manager', __DIR__ . '/files');

        self::$manager = new MountManager([
            'a' => new LocalFileHandler(self::$dir . '/a'),
            'b' => new LocalFileHandler(self::$dir . '/b'),
        ]);
    }

    public static function tearDownAfterClass(): void
    {
        self::deleteFiles(self::$dir);
    }

    protected function m(): MountManager
    {
        return self::$manager;
    }

    public function testCopyFile()
    {
        $info = $this->m()->copy('a://copy-test1.txt', 'b://copy-test-from-a.txt');
        $this->assertInstanceOf(FileInfo::class, $info);

        $this->assertEquals('copy-test-from-a.txt', $info->path());
        $this->assertEquals('b://copy-test-from-a.txt', $info->fullPath());
    }

    public function testCopyFileSubDir()
    {
        $info = $this->m()->copy('a://copy-test1.txt', 'b://new-dir/copy-test-from-a.txt');
        $this->assertInstanceOf(FileInfo::class, $info);

        $this->assertEquals('copy-test-from-a.txt', $info->name());
        $this->assertEquals('new-dir/copy-test-from-a.txt', $info->path());
    }

    public function testCopyDir()
    {
        $info = $this->m()->copy('a://copy-dir', 'b://copy-from-a');
        $this->assertInstanceOf(FileInfo::class, $info);
        $this->assertTrue($info->stat()->isDir());

        $info = $this->m()->info('b://copy-from-a/sub-copy-dir/b.txt');
        $this->assertInstanceOf(FileInfo::class, $info);
        $this->assertTrue($info->stat()->isFile());

    }

    public function testMoveFile()
    {
        $info = $this->m()->rename('a://move-file1.txt', 'b://move-sub-dir/move-sub-dir2/moved-from-a.txt');
        $this->assertInstanceOf(FileInfo::class, $info);

        $this->assertNull($this->m()->info('a://move-file1.txt'));
    }

    public function testMoveDir()
    {
        $info = $this->m()->rename('a://move-dir', 'b://moved-from-a');
        $this->assertInstanceOf(FileInfo::class, $info);
        $this->assertTrue($info->stat()->isDir());

        $info = $this->m()->info('b://moved-from-a/sub-move-dir/b.txt');
        $this->assertInstanceOf(FileInfo::class, $info);
        $this->assertTrue($info->stat()->isFile());

        $this->assertNull($this->m()->info('a://move-dir'));
    }

    public function testSync()
    {
        $this->m()->unlink('b://synced/a.txt');

        $total = $this->m()->sync('a://synced', 'b://synced');
        $this->assertEquals(4, $total);


        $total = $this->m()->sync('a://synced', 'b://synced');
        $this->assertEquals(0, $total);

        $this->m()->write('a://synced/b.txt', new PHPDataStream('some string'));
        $total = $this->m()->sync('a://synced', 'b://synced');
        $this->assertEquals(1, $total);

        $this->m()->rmdir('b://synced/dir');

        $total = $this->m()->sync('a://synced', 'b://synced');
        $this->assertEquals(2, $total);
    }
}