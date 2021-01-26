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

namespace Opis\Colibri\FileSystem\Handler;

use Throwable;
use Opis\Colibri\FileSystem\Traits\SearchTrait;
use Opis\Colibri\Stream\{Stream, Printer\CopyPrinter};
use Opis\Colibri\FileSystem\Directory\LocalDirectory;
use Opis\Colibri\FileSystem\{Directory, FileInfo, FileStream, Stat};
use RecursiveDirectoryIterator, RecursiveIteratorIterator, FilesystemIterator;

class LocalFileHandler implements FileSystemHandler, AccessHandler, SearchHandler
{
    use SearchTrait;

    protected string $root;
    protected ?string $baseUrl = null;
    protected int $defaultMode = 0777;

    public function __construct(string $root, ?string $base_url = null, int $default_mode = 0777)
    {
        $this->root = realpath($root) . '/';
        if ($base_url !== null) {
            $this->baseUrl = rtrim($base_url, '/') . '/';
        }
        $this->defaultMode = $default_mode;
    }

    public function root(): string
    {
        return $this->root;
    }

    protected function fullPath(string $path): string
    {
        return $this->root . $path;
    }

    public function mkdir(string $path, int $mode = 0777, bool $recursive = true): ?FileInfo
    {
        $fullPath = $this->fullPath($path);
        if (is_dir($fullPath)) {
            return null;
        }
        if (!@mkdir($fullPath, $mode, $recursive)) {
            return null;
        }
        return $this->info($path);
    }

    public function rmdir(string $path, bool $recursive = true): bool
    {
        $path = $this->fullPath($path);
        if (!is_dir($path)) {
            return false;
        }

        if (!$recursive) {
            return @rmdir($path);
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $filename => $fileInfo) {
            if ($fileInfo->isDir()) {
                if (!@rmdir($filename)) {
                    return false;
                }
            } elseif (!@unlink($filename)) {
                return false;
            }
        }

        return @rmdir($path);
    }

    public function unlink(string $path): bool
    {
        return @unlink($this->fullPath($path));
    }

    public function touch(string $path, int $time, ?int $atime = null): ?FileInfo
    {
        if (!$this->ensureDir($path, $this->defaultMode)) {
            return null;
        }

        if (!@touch($this->fullPath($path), $time, $atime ?? $time)) {
            return null;
        }

        return $this->info($path);
    }

    public function chmod(string $path, int $mode): ?FileInfo
    {
        if (!@chmod($this->fullPath($path), $mode)) {
            return null;
        }
        return $this->info($path);
    }

    public function chown(string $path, string $owner): ?FileInfo
    {
        if (!@chown($this->fullPath($path), $owner)) {
            return null;
        }
        return $this->info($path);
    }

    public function chgrp(string $path, string $group): ?FileInfo
    {
        if (!@chgrp($this->fullPath($path), $group)) {
            return null;
        }
        return $this->info($path);
    }

    public function rename(string $from, string $to): ?FileInfo
    {
        if ($from === $to) {
            return null;
        }
        if (!@rename($this->fullPath($from), $this->fullPath($to))) {
            return null;
        }
        return $this->info($to);
    }

    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo
    {
        $from = trim($from, ' /');
        $to = trim($to, ' /');

        if ($from === '' || $from === $to) {
            return null;
        }

        $from_stat = $this->stat($from);
        if (!$from_stat) {
            return null;
        }

        $to_stat = $this->stat($to);

        if ($to_stat && !$overwrite) {
            return null;
        }

        if ($from_stat->isDir()) {
            $dir = $this->dir($from);
            if ($dir === null) {
                return null;
            }

            if ($to_stat) {
                if (!$to_stat->isDir()) {
                    if (!$this->unlink($to)) {
                        return null;
                    }
                    if (!$this->mkdir($to, $from_stat->mode(), true)) {
                        return null;
                    }
                }
            } elseif (!$this->mkdir($to, $from_stat->mode(), true)) {
                return null;
            }

            unset($from_stat, $to_stat);

            $ok = true;

            while ($ok && ($item = $dir->next())) {
                $name = $item->name();
                $ok = $this->copy($from . '/' . $name, $to . '/' . $name, $overwrite);
                unset($name, $item);
            }

            return $ok ? $this->info($to) : null;
        }

        if ($to_stat && $to_stat->isDir()) {
            if (!$this->rmdir($to)) {
                return null;
            }
        }

        unset($to_stat);

        return $this->write($to, $this->file($from, 'rb'), $from_stat->mode());
    }

    public function stat(string $path, bool $resolve_links = true): ?Stat
    {
        $stat = $resolve_links ? @stat($this->fullPath($path)) : @lstat($this->fullPath($path));
        return $stat ? new Stat($stat) : null;
    }

    public function write(string $path, Stream $stream, int $mode = 0777): ?FileInfo
    {
        if ($stream->size() === 0 && $stream->isEOF()) {
            $now = time();
            if ($this->touch($path, $now, $now)) {
                $this->chmod($path, $mode);
                return $this->info($path);
            }
            return null;
        }

        if (!$this->ensureDir($path, $this->defaultMode)) {
            return null;
        }

        if ($this->writeFile($path, $stream)) {
            $this->chmod($path, $mode);
            return $this->info($path);
        }

        return null;
    }

    public function file(string $path, string $mode = 'rb'): ?Stream
    {
        if ($stat = $this->stat($path)) {
            if ($stat->isDir()) {
                return null;
            }
        }

        if (!$this->ensureDir($path, $this->defaultMode)) {
            return null;
        }

        try {
            return new FileStream($this->fullPath($path), $mode, $stat);
        } catch (Throwable) {
            return null;
        }
    }

    public function dir(string $path): ?Directory
    {
        $stat = $this->stat($path);
        if (!$stat || !$stat->isDir()) {
            return null;
        }
        return new LocalDirectory($this, $path, $this->root);
    }

    public function info(string $path): ?FileInfo
    {
        $path = trim($path, ' /');
        if ($path === '') {
            return null;
        }

        $stat = $this->stat($path);
        if ($stat === null) {
            return null;
        }

        $url = null;
        if ($this->baseUrl !== null) {
            $url = $this->baseUrl . $path;
        }

        $type = null;

        if (!$stat->isDir()) {
            $type = mime_content_type($this->fullPath($path));
            if (!$type || $type === 'directory') {
                $type = null;
            }
        }

        return new FileInfo($path, $stat, $type, $url);
    }

    protected function ensureDir(string $path, int $mode): bool
    {
        $path = trim($path, ' /');
        if ($path === '' || !str_contains($path, '/')) {
            return true;
        }

        $path = explode('/', $path);
        array_pop($path);
        $path = implode('/', $path);

        if ($stat = $this->stat($path, true)) {
            return $stat->isDir();
        }
        return $this->mkdir($path, $mode, true) !== null;
    }

    protected function writeFile(string $path, Stream $stream): bool
    {
        if (!$stream->isReadable()) {
            return false;
        }

        $path = $this->fullPath($path);

        if ($resource = $stream->resource()) {
            if (get_resource_type($resource) === 'stream') {
                $to = @fopen($path, 'wb+');
                if (!$to) {
                    return false;
                }
                $res = stream_copy_to_stream($resource, $to);
                @fclose($to);
                return !($res === false);
            }
            unset($resource);
        }

        $wasEOF = $stream->isEOF();
        $written = (new CopyPrinter(new FileStream($path, 'wb+')))->copy($stream);
        return $wasEOF || $written > 0;
    }
}