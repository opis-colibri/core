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

use ArrayObject;
use Opis\Colibri\Stream\Stream;
use Opis\Colibri\FileSystem\CacheHandler\{MemoryCacheHandler};
use Opis\Colibri\FileSystem\Directory\{ArrayDirectory, CachedDirectory};
use Opis\Colibri\FileSystem\{CacheHandler, Directory, FileInfo, Stat, Context};

class CachedHandler implements FileSystemHandler, AccessHandler, SearchHandler, ContextHandler
{
    protected FileSystemHandler|AccessHandler|SearchHandler|ContextHandler $handler;
    /** @var null|ArrayObject|FileInfo[] */
    protected ?ArrayObject $data = null;
    protected CacheHandler $cache;
    protected bool $lazyDirCache = false;
    protected bool $ignoreLinks = true;
    protected bool $isContextHandler = false;
    protected bool $isAccessHandler = false;
    protected bool $isSearchHandler = false;

    public function __construct(
        FileSystemHandler $handler,
        ?CacheHandler $cache = null,
        bool $lazy_dir_cache = false,
        bool $ignore_links = true
    )
    {
        $this->handler = $handler;
        $this->cache = $cache ?? new MemoryCacheHandler();
        $this->lazyDirCache = $lazy_dir_cache;
        $this->ignoreLinks = $ignore_links;

        $this->isAccessHandler = $handler instanceof AccessHandler;
        $this->isContextHandler = $handler instanceof ContextHandler;
        $this->isSearchHandler = $handler instanceof SearchHandler;
    }

    public function handler(): FileSystemHandler
    {
        return $this->handler;
    }

    public function cache(): CacheHandler
    {
        return $this->cache;
    }

    /**
     * Initialize cached data
     */
    protected function initCache(): void
    {
        if ($this->data === null) {
            $this->data = $this->cache->load() ?? new ArrayObject();
        }
    }

    public function updateCache(FileInfo $item): bool
    {
        $this->initCache();

        $path = trim($item->path(), ' /');

        $this->data[$path] = $item;

        return $this->cache->save($this->data);
    }

    public function removeCache(string $path): bool
    {
        $this->initCache();

        $path = trim($path, ' /');

        if (isset($this->data[$path])) {
            unset($this->data[$path]);
            return $this->cache->save($this->data);
        }

        return false;
    }

    public function clearCache(?string $dir = null): bool
    {
        $this->initCache();

        if ($this->data->count() === 0) {
            return true;
        }

        if ($dir === null) {
            $dir = '';
        } else {
            $dir = trim($dir, ' /');
        }

        if ($dir === '') {
            if ($this->data->count() > 0) {
                $this->data = new ArrayObject();
                return $this->cache->save($this->data);
            }
            return false;
        }

        $changed = false;

        // Remove dir info
        if (isset($this->data[$dir])) {
            $changed = true;
            unset($this->data[$dir]);
        }

        $dir .= '/';

        // Search for sub-paths
        foreach ($this->data as $name => $info) {
            if (strpos($name, $dir) === 0) {
                $changed = true;
                unset($this->data[$name]);
            }
        }

        if ($changed) {
            return $this->cache->save($this->data);
        }

        return false;
    }

    public function rebuildCache(): bool
    {
        if ($info = $this->handler->info('/')) {
            $data = $this->rebuildCacheData(new ArrayObject(), $info, $this->handler);

            if ($this->cache->save($data)) {
                $this->data = $data;
                $this->cache->commit();
                return true;
            }
        }

        return false;
    }

    protected function rebuildCacheData(ArrayObject $data, FileInfo $file, FileSystemHandler $handler): ArrayObject
    {
        $path = trim($file->path(), ' /');
        $data[$path] = $file;

        if ($file->stat()->isDir() && ($dir = $handler->dir($path))) {
            while ($item = $dir->next()) {
                $this->rebuildCacheData($data, $item, $handler);
            }
        }

        return $data;
    }

    public function stat(string $path, bool $resolve_links = true): ?Stat
    {
        if (!$resolve_links && !$this->ignoreLinks) {
            return $this->handler->stat($path, false);
        }

        if ($info = $this->info($path)) {
            return $info->stat();
        }

        return null;
    }

    public function dir(string $path): ?Directory
    {
        $this->initCache();

        $path = trim($path, ' /');

        if (isset($this->data[$path])) {
            $path .= '/';
            $len = strlen($path);
            $files = [];

            foreach ($this->data as $name => $info) {
                if (strpos($name, $path) === false) {
                    continue;
                }

                $name = substr($name, $len);

                if (strpos($name, '/') === false) {
                    $files[] = $info;
                }
            }

            if ($files) {
                return new ArrayDirectory($path, $files);
            }
        }

        if (($dir = $this->handler->dir($path)) === null) {
            return null;
        }

        if ($this->lazyDirCache) {
            return new CachedDirectory($dir, $this);
        }

        while ($item = $dir->next()) {
            $this->updateCache($item);
        }

        $dir->rewind();

        return $dir;
    }

    public function info(string $path): ?FileInfo
    {
        $this->initCache();

        $path = trim($path, ' /');

        if (isset($this->data[$path])) {
            return $this->data[$path];
        }

        if ($info = $this->handler->info($path)) {
            $this->updateCache($info);
        }

        return $info;
    }

    public function mkdir(string $path, int $mode = 0777, bool $recursive = true): ?FileInfo
    {
        if ($item = $this->handler->mkdir($path, $mode, $recursive)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function rmdir(string $path, bool $recursive = true): bool
    {
        if ($this->handler->rmdir($path, $recursive)) {
            $this->clearCache($path);
            return true;
        }

        return false;
    }

    public function unlink(string $path): bool
    {
        if ($this->handler->unlink($path)) {
            $this->removeCache($path);
            return true;
        }

        return false;
    }

    public function touch(string $path, int $time, ?int $atime = null): ?FileInfo
    {
        if (!$this->isAccessHandler) {
            return null;
        }

        if ($item = $this->handler->touch($path, $time, $atime)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function chmod(string $path, int $mode): ?FileInfo
    {
        if (!$this->isAccessHandler) {
            return null;
        }

        if ($item = $this->handler->chmod($path, $mode)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function chown(string $path, string $owner): ?FileInfo
    {
        if (!$this->isAccessHandler) {
            return null;
        }

        if ($item = $this->handler->chown($path, $owner)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function chgrp(string $path, string $group): ?FileInfo
    {
        if (!$this->isAccessHandler) {
            return null;
        }

        if ($item = $this->handler->chgrp($path, $group)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function rename(string $from, string $to): ?FileInfo
    {
        if ($item = $this->handler->rename($from, $to)) {
            $this->clearCache($from);
            $this->updateCache($item);
        }

        return $item;
    }

    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo
    {
        if ($item = $this->handler->copy($from, $to, $overwrite)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function write(string $path, Stream $stream, int $mode = 0777): ?FileInfo
    {
        if ($item = $this->handler->write($path, $stream, $mode)) {
            $this->updateCache($item);
        }

        return $item;
    }

    public function file(string $path, string $mode = 'rb'): ?Stream
    {
        // No cache here
        return $this->handler->file($path, $mode);
    }

    public function search(
        string $path,
        string $text,
        ?callable $filter = null,
        ?array $options = null,
        ?int $depth = 0,
        ?int $limit = null
    ): iterable
    {
        return $this->isSearchHandler ? $this->handler->search($path, $text, $filter, $options, $depth, $limit) : [];
    }

    public function setContext(?Context $context): bool
    {
        return $this->isContextHandler ? $this->handler->setContext($context) : false;
    }

    public function getContext(): ?Context
    {
        return $this->isContextHandler ? $this->handler->getContext() : null;
    }
}