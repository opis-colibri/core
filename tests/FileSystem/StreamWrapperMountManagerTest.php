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

use Opis\Colibri\FileSystem\FileSystemStreamWrapper;
use Opis\Colibri\FileSystem\Handler\LocalFileHandler;
use Opis\Colibri\FileSystem\MountManager;
use Opis\Colibri\FileSystem\StreamWrapperMountManager;
use PHPUnit\Framework\TestCase;

class StreamWrapperMountManagerTest extends TestCase
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

        self::$manager = new StreamWrapperMountManager([
            'a' => new LocalFileHandler(self::$dir . '/a'),
            'b' => new LocalFileHandler(self::$dir . '/b'),
        ], 'my-fs');

        FileSystemStreamWrapper::register(self::$manager->protocol(), self::$manager);
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        self::deleteFiles(self::$dir);
        FileSystemStreamWrapper::unregister(self::$manager->protocol());
    }

    protected function m(): MountManager
    {
        return self::$manager;
    }

    public function testCopyFile()
    {
        $this->assertTrue(copy('my-fs://a/copy-test1.txt', 'my-fs://b/copy-test-from-a.txt'));
        $this->assertEquals(file_get_contents('my-fs://a/copy-test1.txt'), file_get_contents('my-fs://b/copy-test-from-a.txt'));
    }

    public function testCopyFileSubDir()
    {
        $this->assertTrue(copy('my-fs://a/copy-test1.txt', 'my-fs://b/new-dir/copy-test-from-a.txt'));
        $this->assertEquals(file_get_contents('my-fs://a/copy-test1.txt'), file_get_contents('my-fs://b/new-dir/copy-test-from-a.txt'));
    }

    public function testMove()
    {
        $this->assertTrue(rename('my-fs://a/move-dir', 'my-fs://b/moved-dir'));
        $this->assertFalse(is_dir('my-fs://a/move-dir'));
        $this->assertFalse(file_exists('my-fs://a/move-dir'));
    }

    public function testDelete()
    {
        $this->assertTrue(unlink('my-fs://b/synced/a.txt'));
        $this->assertTrue(rmdir('my-fs://b/synced'));
    }

    public function testResourceRead()
    {
        $fh = fopen('my-fs://a/copy-test1.txt', 'rb');
        $this->assertTrue(is_resource($fh));

        $this->assertEquals('copy-', fread($fh, 5));
        $this->assertTrue(rewind($fh));


        $this->assertEquals('copy-', fread($fh, 5));

        $this->assertEquals('test1.txt', fread($fh, 1024));

        $this->assertTrue(feof($fh));

        $this->assertTrue(fclose($fh));
    }

    public function testResourceWrite()
    {
        $this->assertFalse(file_exists('my-fs://b/new-file.txt'));

        $fh = fopen('my-fs://b/new-file.txt', 'w+');
        $this->assertTrue(is_resource($fh));
        $this->assertEquals(4, fwrite($fh, 'new-'));
        $this->assertEquals(4, fwrite($fh, 'file'));
        $this->assertTrue(fclose($fh));

        $this->assertTrue(is_file('my-fs://b/new-file.txt'));
        $this->assertEquals('new-file', file_get_contents('my-fs://b/new-file.txt'));
    }
}
