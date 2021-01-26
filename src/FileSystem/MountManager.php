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

namespace Opis\Colibri\FileSystem;

use Opis\Colibri\Stream\Stream;
use Opis\Colibri\FileSystem\Traits\PathTrait;
use Opis\Colibri\FileSystem\Handler\{AccessHandler, FileSystemHandler, SearchHandler};

class MountManager implements FileSystemHandlerManager, FileSystemHandler, AccessHandler, SearchHandler
{
    use PathTrait;

    /** @var FileSystemHandler[] */
    protected array $handlers = [];

    /**
     * @param FileSystemHandler[] $handlers
     */
    public function __construct(array $handlers = [])
    {
        foreach ($handlers as $name => $handler) {
            if (is_string($name) && $handler && ($handler instanceof FileSystemHandler)) {
                $this->mount($name, $handler);
            }
        }
    }

    public function handle(string $path, string $protocol): ?FileSystemStreamPathInfo
    {
        $path = $this->parsePath($path, $protocol);
        if ($path === null) {
            return null;
        }

        if (!isset($this->handlers[$path['handler']])) {
            return null;
        }

        return new FileSystemStreamPathInfo($this->handlers[$path['handler']], $path['path']);
    }

    public function mount(string $name, FileSystemHandler $handler): bool
    {
        $this->handlers[$name] = $handler;

        return true;
    }

    public function umount(string $name): bool
    {
        if (isset($this->handlers[$name])) {
            unset($this->handlers[$name]);
            return true;
        }

        return false;
    }

    public function handler(string $name): ?FileSystemHandler
    {
        return $this->handlers[$name] ?? null;
    }

    /**
     * @return FileSystemHandler[]
     */
    public function handlers(): iterable
    {
        return $this->handlers;
    }

    public function mkdir(string $path, int $mode = 0777, bool $recursive = true): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$mode, $recursive]);
    }

    public function rmdir(string $path, bool $recursive = true): bool
    {
        return $this->forward($path, __FUNCTION__, [$recursive]);
    }

    public function unlink(string $path): bool
    {
        return $this->forward($path, __FUNCTION__, [], false);
    }

    public function touch(string $path, int $time, ?int $atime = null): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$time, $atime], null, false, true);
    }

    public function chmod(string $path, int $mode): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$mode], null, false, true);
    }

    public function chown(string $path, string $owner): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$owner], null, false, true);
    }

    public function chgrp(string $path, string $group): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$group], null, false, true);
    }

    public function stat(string $path, bool $resolve_links = true): ?Stat
    {
        return $this->forward($path, __FUNCTION__, [$resolve_links], null);
    }

    public function write(string $path, Stream $stream, int $mode = 0777): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [$stream, $mode]);
    }

    public function file(string $path, string $mode = 'rb'): ?Stream
    {
        return $this->forward($path, __FUNCTION__, [$mode], null);
    }

    public function dir(string $path): ?Directory
    {
        return $this->forward($path, __FUNCTION__, [], null, true);
    }

    public function info(string $path): ?FileInfo
    {
        return $this->forward($path, __FUNCTION__, [], null, true);
    }

    public function exists(string $path): bool
    {
        return $this->stat($path) !== null;
    }

    public function isFile(string $path): bool
    {
        if ($stat = $this->stat($path)) {
            return $stat->isFile();
        }
        return false;
    }

    public function isDir(string $path): bool
    {
        if ($stat = $this->stat($path)) {
            return $stat->isDir();
        }

        return false;
    }

    public function isLink(string $path): bool
    {
        if ($stat = $this->stat($path, false)) {
            return $stat->isLink();
        }

        return false;
    }

    public function rename(string $from, string $to): ?FileInfo
    {
        if (!str_contains($from, '://')) {
            return null;
        }

        if (!str_contains($to, '://')) {
            $to = $this->mergePaths($to, $from);
            if ($to === null) {
                return null;
            }
        }

        [$proto_from, $from] = explode('://', $from, 2);
        $handler_from = $this->handler($proto_from);
        if ($handler_from === null) {
            return null;
        }
        $from = $this->normalizePath($from);

        [$proto_to, $to] = explode('://', $to, 2);
        $to = $this->normalizePath($to);

        if ($proto_from === $proto_to) {
            $info = $handler_from->rename($from, $to);
            if ($info instanceof ProtocolInfo) {
                $info->setProtocol($proto_to);
            }
            return $info;
        }

        $handler_to = $this->handler($proto_to);
        if ($handler_to === null) {
            return null;
        }

        /** @var FileInfo $from_info */
        $from_info = $this->info($proto_from . '://' . $from);

        if ($from_info === null) {
            return null;
        }

        if (!$this->doCopy($handler_from, $from_info, $handler_to, $to, true, true)) {
            return null;
        }

        if ($from_info->stat()->isDir()) {
            $handler_from->rmdir($from_info->path(), true);
        } else {
            $handler_from->unlink($from_info->path());
        }

        return $this->info($proto_to . '://' . $to);
    }

    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo
    {
        if (!str_contains($from, '://')) {
            return null;
        }

        if (!str_contains($to, '://')) {
            $to = $this->mergePaths($to, $from);
            if ($to === null) {
                return null;
            }
        }

        [$proto_from, $from] = explode('://', $from, 2);
        $handler_from = $this->handler($proto_from);
        if ($handler_from === null) {
            return null;
        }
        $from = $this->normalizePath($from);

        [$proto_to, $to] = explode('://', $to, 2);
        $to = $this->normalizePath($to);

        if ($proto_from === $proto_to) {
            $info = $handler_from->copy($from, $to, $overwrite);
            if ($info instanceof ProtocolInfo) {
                $info->setProtocol($proto_to);
            }
            return $info;
        }

        $handler_to = $this->handler($proto_to);
        if ($handler_to === null) {
            return null;
        }

        /** @var FileInfo $from_info */
        $from_info = $this->info($proto_from . '://' . $from);

        if ($from_info === null) {
            return null;
        }

        if (!$this->doCopy($handler_from, $from_info, $handler_to, $to, true, $overwrite)) {
            return null;
        }

        return $this->info($proto_to . '://' . $to);
    }

    /**
     * @inheritDoc
     */
    public function search(
        string $path,
        string $text,
        ?callable $filter = null,
        ?array $options = null,
        ?int $depth = 0,
        ?int $limit = null
    ): iterable {
        if (strpos($path, '://') === false) {
            return [];
        }

        [$protocol, $path] = explode('://', $path, 2);

        $handler = $this->handler($protocol);
        if ($handler === null || !($handler instanceof SearchHandler)) {
            return [];
        }

        $path = $this->normalizePath($path);

        foreach ($handler->search($path, $text, $filter, $options, $depth, $limit) as $item) {
            if ($item instanceof ProtocolInfo) {
                $item->setProtocol($protocol);
            }

            yield $item;
        }
    }

    public function copyFiltered(
        string $from,
        string $to,
        bool $recursive = true,
        bool $overwrite = true,
        ?callable $filter = null
    ): ?int {
        if (!str_contains($from, '://')) {
            return null;
        }

        if (!str_contains($to, '://')) {
            $to = $this->mergePaths($to, $from);
            if ($to === null) {
                return null;
            }
        }

        [$proto_from, $from] = explode('://', $from, 2);
        $handler_from = $this->handler($proto_from);
        if ($handler_from === null) {
            return null;
        }
        $from = $this->normalizePath($from);

        $from_info = $handler_from->info($from);
        if ($from_info === null) {
            return null;
        }

        [$proto_to, $to] = explode('://', $to, 2);
        $to = $this->normalizePath($to);

        if ($proto_from === $proto_to) {
            $handler_to = $handler_from;
        } else {
            $handler_to = $this->handler($proto_to);
            if ($handler_to === null) {
                return null;
            }
        }

        unset($from, $proto_from, $proto_to);

        return $this->doCopy($handler_from, $from_info, $handler_to, $to, $recursive, $overwrite, $filter);
    }

    public function sync(string $source, string $replica, ?callable $filter = null): ?int
    {
        if (!str_contains($source, '://')) {
            return null;
        }

        if (!str_contains($replica, '://')) {
            $replica = $this->mergePaths($replica, $source);
            if ($replica === null) {
                return null;
            }
        }

        [$proto_source, $source] = explode('://', $source, 2);
        $handler_source = $this->handler($proto_source);
        if ($handler_source === null) {
            return null;
        }

        $source = $this->normalizePath($source);

        $source_info = $handler_source->info($source);
        if ($source_info === null) {
            return null;
        }

        [$proto_replica, $replica] = explode('://', $replica, 2);
        $replica = $this->normalizePath($replica);

        if ($proto_source === $proto_replica) {
            $handler_replica = $handler_source;
        } else {
            $handler_replica = $this->handler($proto_replica);
            if ($handler_replica === null) {
                return null;
            }
        }

        unset($source, $proto_source, $proto_replica);

        if (!$filter) {
            $filter = static function (FileInfo $source, FileInfo $replica): bool {
                $sStat = $source->stat();
                $rStat = $replica->stat();

                if ($sStat->size() !== $rStat->size()) {
                    return true;
                }

                return $sStat->mtime() > $rStat->mtime();
            };
        }

        return $this->doCopy($handler_source, $source_info, $handler_replica, $replica, true, true, null, $filter);
    }

    public function absolutePath(string $path, string $protocol): ?string
    {
        if (!str_contains($path, '://')) {
            return null;
        }

        [$handler, $path] = explode('://', $path, 2);

        $path = $this->normalizePath($path);

        if ($path === '') {
            return $protocol . '://' . $handler;
        }

        return $protocol . '://' . $handler . '/' . $path;
    }

    public function relativePath(string $path, ?string $protocol = null): ?string
    {
        $path = $this->parsePath($path, $protocol);

        if ($path === null) {
            return null;
        }

        return $path['handler'] . '://' . $path['path'];
    }

    public function mergePaths(string $path, string $base, ?string $protocol = null): ?string
    {
        $absolute = $protocol !== null;

        if (str_contains($path, '://')) {
            return $absolute ? $this->absolutePath($path, $protocol) : $this->relativePath($path);
        }

        if (!str_contains($base, '://')) {
            return null;
        }

        [$proto, $base] = explode('://', $base, 2);

        if ($path === '') {
            return $absolute ? $this->absolutePath($base, $protocol) : $proto . '://' . $this->normalizePath($base);
        }

        if ($path[0] === '/') {
            return $absolute ? $this->absolutePath($proto . "://" . $path,
                $protocol) : $proto . '://' . $this->normalizePath($path);
        }

        $shouldPop = substr($base, -1, 1) !== '/';

        $base = explode('/', $this->normalizePath($base));

        if ($shouldPop) {
            array_pop($base);
        }

        foreach (explode('/', $path) as $item) {
            $item = trim($item);
            if ($item === '' || $item === '.') {
                continue;
            }

            if ($item === '..') {
                if ($base) {
                    array_pop($base);
                }
                continue;
            }

            $base[] = $item;
        }

        $base = $base ? implode('/', $base) : '';

        if ($absolute) {
            return $protocol . '://' . $proto . ($base === '' ? '' : '/' . $base);
        }

        return $proto . '://' . $base;
    }

    protected function doCopy(
        FileSystemHandler $from,
        FileInfo $from_info,
        FileSystemHandler $to,
        string $to_path,
        bool $recursive,
        bool $overwrite,
        ?callable $filter = null,
        ?callable $overwrite_filter = null
    ): int {
        if ($filter && !$filter($from_info)) {
            return 0;
        }

        $from_stat = $from_info->stat();
        $to_info = $to->info($to_path);
        $to_stat = $to_info ? $to_info->stat() : null;

        if ($from_stat->isFile()) {
            if ($to_info) {
                if (!$overwrite) {
                    return 0;
                }

                if ($overwrite_filter !== null && !$overwrite_filter($from_info, $to_info)) {
                    return 0;
                }

                if ($to_stat->isDir()) {
                    $to->rmdir($to_path, true);
                }
            }

            $file = $from->file($from_info->path());

            if ($file === null) {
                return 0;
            }

            $ok = $to->write($to_path, $file, $from_stat->mode()) !== null;

            $file->close();

            return $ok ? 1 : 0;
        }

        if (!$recursive || !$from_stat->isDir()) {
            return 0;
        }

        if ($to_info && (!$overwrite || !$to_stat->isDir())) {
            return 0;
        }

        $directory = $from->dir($from_info->path());
        if ($directory === null) {
            return 0;
        }

        unset($from_stat, $from_info, $to_info, $to_stat);

        $total = 0;

        while ($item = $directory->next()) {
            if ($filter && !$filter($item)) {
                continue;
            }
            $total += $this->doCopy($from, $item, $to, $to_path . '/' . $item->name(), $recursive, $overwrite, $filter,
                $overwrite_filter);
            unset($item);
        }

        return $total;
    }

    protected function forward(
        string $path,
        string $method,
        array $args = [],
        $failure = null,
        bool $allowRoot = false,
        bool $access = false
    ): mixed {
        if (!str_contains($path, '://')) {
            return $failure;
        }

        [$protocol, $path] = explode('://', $path, 2);

        $handler = $this->handler($protocol);
        if ($handler === null) {
            return $failure;
        }

        if ($access && !($handler instanceof AccessHandler)) {
            return $failure;
        }

        $path = $this->normalizePath($path);

        if (!$allowRoot && $path === '') {
            return $failure;
        }

        array_unshift($args, $path);

        $ret = $handler->{$method}(...$args);

        if ($ret instanceof ProtocolInfo) {
            $ret->setProtocol($protocol);
        }

        return $ret;
    }
}