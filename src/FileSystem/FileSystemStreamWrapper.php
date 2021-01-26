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

use Opis\Colibri\Stream\{
    Stream,
    StreamWrapper
};
use Opis\Colibri\FileSystem\Handler\{
    AccessHandler,
    ContextHandler,
    FileSystemHandler
};
use Opis\Colibri\FileSystem\Traits\{
    ProtocolTrait,
    StreamDirectoryTrait,
    StreamFileTrait,
    StreamMetaTrait,
    StreamContextTrait
};

class FileSystemStreamWrapper implements StreamWrapper
{
    use StreamMetaTrait, StreamContextTrait, StreamFileTrait, StreamDirectoryTrait, ProtocolTrait;

    /** @var FileSystemHandlerManager[] */
    private static array $registered = [];

    public function mkdir(string $path, int $mode, int $options): bool
    {
        return (bool)$this->forward($path, __FUNCTION__,
            [$mode, ($options & STREAM_MKDIR_RECURSIVE) === STREAM_MKDIR_RECURSIVE]);
    }

    public function rmdir(string $path, int $options): bool
    {
        return (bool)$this->forward($path, __FUNCTION__,
            [($options & STREAM_MKDIR_RECURSIVE) === STREAM_MKDIR_RECURSIVE]);
    }

    public function unlink(string $path): bool
    {
        return (bool)$this->forward($path, __FUNCTION__);
    }

    protected function touch(string $path, int $time, int $atime): bool
    {
        return (bool)$this->forward($path, __FUNCTION__, [$time, $atime], false, false, true);
    }

    protected function chmod(string $path, int $mode): bool
    {
        return (bool)$this->forward($path, __FUNCTION__, [$mode], false, false, true);
    }

    protected function chown(string $path, string $owner): bool
    {
        return (bool)$this->forward($path, __FUNCTION__, [$owner], false, false, true);
    }

    protected function chgrp(string $path, string $group): bool
    {
        return (bool)$this->forward($path, __FUNCTION__, [$group], false, false, true);
    }

    public function copy(string $from, string $to, bool $overwrite = true): bool
    {
        return $this->doCopy($from, $to, false, $overwrite);
    }

    public function rename(string $from, string $to): bool
    {
        return $this->doCopy($from, $to, true, false);
    }

    public function url_stat(string $path, int $flags): ?array
    {
        /** @var Stat|null $stat */
        $stat = $this->forward($path, 'stat', [($flags & STREAM_URL_STAT_LINK) === STREAM_URL_STAT_LINK], null);
        return $stat ? $stat->toArray() : null;
    }

    protected function file(string $path, string $mode): ?Stream
    {
        return $this->forward($path, __FUNCTION__, [$mode], null);
    }

    protected function dir(string $path): ?Directory
    {
        return $this->forward($path, __FUNCTION__, [], null, true);
    }

    protected function forward(
        string $path,
        string $method,
        array $args = [],
        mixed $failure = false,
        bool $allowRoot = false,
        bool $access = false
    ): mixed {
        $protocol = $this->setCurrentProtocolFromPath($path);
        if ($protocol === '') {
            return $failure;
        }

        $manager = self::manager($protocol);
        if ($manager === null) {
            return $failure;
        }

        $info = $manager->handle($path, $protocol);
        if ($info === null) {
            return $failure;
        }

        $path = $info->path();

        if (!$allowRoot && $path === '') {
            // Cannot access root
            return $failure;
        }

        $handler = $info->handler();

        unset($info);

        if ($access && !($handler instanceof AccessHandler)) {
            return $failure;
        }

        array_unshift($args, $path);

        unset($path);

        if ($handler instanceof ContextHandler) {
            $ctx = $handler->getContext();
            $handler->setContext($this->context());
            $ret = $handler->{$method}(...$args);
            $handler->setContext($ctx);
            return $ret;
        }

        return $handler->{$method}(...$args);
    }

    protected function doCopy(string $from, string $to, bool $move, bool $overwrite): bool
    {
        $protocol = $this->setCurrentProtocolFromPath($from);
        if ($protocol === '' || $protocol !== $this->parseProtocolFromPath($to)) {
            return false;
        }

        if (!($manager = self::manager($protocol))) {
            return false;
        }

        if (($from = $manager->handle($from, $protocol)) === null) {
            return false;
        }

        $from_handler = $from->handler();
        $from = $from->path();

        if ($from === '') {
            return false;
        }

        if (($to = $manager->handle($to, $protocol)) === null) {
            return false;
        }

        $to_handler = $to->handler();
        $to = $to->path();

        if ($to === '') {
            return false;
        }

        unset($protocol);

        if ($from_handler === $to_handler) {
            $handler = $from_handler;
            unset($from_handler, $to_handler);

            if ($handler === null) {
                return false;
            }

            if ($from === $to) {
                return false;
            }

            $ctx = null;

            if ($handler instanceof ContextHandler) {
                $ctx = $handler->getContext();
                $handler->setContext($this->context());
            }

            if ($move) {
                $ret = $handler->rename($from, $to) !== null;
            } else {
                $ret = $handler->copy($from, $to, $overwrite) !== null;
            }

            if ($handler instanceof ContextHandler) {
                $handler->setContext($ctx);
            }

            return $ret;
        }

        $from_stat = $from_handler->stat($from);
        if ($from_stat === null) {
            return false;
        }

        $to_stat = $to_handler->stat($to);

        if (!$overwrite && $to_stat) {
            return false;
        }

        $ctx = false;
        $from_ctx = null;
        $to_ctx = null;

        if ($from_handler instanceof ContextHandler) {
            $ctx = $this->context();
            $from_ctx = $from_handler->getContext();
            $from_handler->setContext($ctx);
        }

        if ($to_handler instanceof ContextHandler) {
            if ($ctx === false) {
                $ctx = $this->context();
            }
            $to_ctx = $to_handler->getContext();
            $to_handler->setContext($ctx);
        }


        if ($from_stat->isDir()) {
            $ok = $this->copyDir($from_handler, $from, $to_handler, $to, $move, $overwrite, $from_stat->mode());
        } else {
            $ok = $this->copyFile($from_handler, $from, $to_handler, $to, $move, $overwrite, $from_stat->mode());
        }

        if ($ctx !== false) {
            if ($from_handler instanceof ContextHandler) {
                $from_handler->setContext($from_ctx);
            }
            if ($to_handler instanceof ContextHandler) {
                $to_handler->setContext($to_ctx);
            }
        }

        return $ok;
    }

    protected function copyDir(
        FileSystemHandler $from,
        string $from_path,
        FileSystemHandler $to,
        string $to_path,
        bool $move,
        bool $overwrite,
        int $mode = 0777
    ): bool {
        $dir = $from->dir($from_path);

        if ($dir === null) {
            return false;
        }

        $stat = $to->stat($to_path);
        if ($stat) {
            if (!$stat->isDir()) {
                if (!$overwrite) {
                    return false;
                }
                if (!$to->unlink($to_path)) {
                    return false;
                }
                if (!$to->mkdir($to_path, $mode, true)) {
                    return false;
                }
            }
        } elseif (!$to->mkdir($to_path, $mode, true)) {
            return false;
        }
        unset($stat);


        $ok = true;

        while ($ok && ($item = $dir->next())) {
            $name = $item->name();
            if ($item->stat()->isDir()) {
                $ok = $this->copyDir($from, $from_path . '/' . $name, $to, $to_path . '/' . $name, $move, $overwrite,
                    $item->stat()->mode());
            } else {
                $ok = $this->copyFile($from, $from_path . '/' . $name, $to, $to_path . '/' . $name, $move, $overwrite,
                    $item->stat()->mode());
            }
            unset($item);
        }

        unset($dir);

        if ($ok && $move) {
            $ok = $from->rmdir($from_path);
        }

        return $ok;
    }

    protected function copyFile(
        FileSystemHandler $from,
        string $from_path,
        FileSystemHandler $to,
        string $to_path,
        bool $move,
        bool $overwrite,
        int $mode = 0777
    ): bool {
        $stream = $from->file($from_path, 'rb');

        if ($stream === null) {
            return false;
        }

        $stat = $to->stat($to_path);
        if ($stat) {
            if ($stat->isDir()) {
                if (!$overwrite) {
                    return false;
                }
                if (!$to->rmdir($to_path, true)) {
                    return false;
                }
            } elseif (!$overwrite) {
                return false;
            }
        }
        unset($stat);


        $ok = $to->write($to_path, $stream, $mode) !== null;

        unset($stream);

        if ($ok && $move) {
            $ok = $from->unlink($from_path);
        }

        return $ok;
    }

    final public static function manager(string $protocol): ?FileSystemHandlerManager
    {
        return self::$registered[$protocol] ?? null;
    }

    final public static function register(string $protocol, FileSystemHandlerManager $manager, ?int $protocol_flags = null): bool
    {
        if (isset(self::$registered[$protocol])) {
            return self::$registered[$protocol] === $manager;
        }

        if (!stream_wrapper_register($protocol, static::class, $protocol_flags ?? static::protocolFlags())) {
            return false;
        }

        self::$registered[$protocol] = $manager;

        return true;
    }

    final public static function unregister(string $protocol): bool
    {
        if (!isset(self::$registered[$protocol]) || !stream_wrapper_unregister($protocol)) {
            return false;
        }

        unset(self::$registered[$protocol]);

        return true;
    }

    final public static function isRegistered(string $protocol): bool
    {
        return isset(self::$registered[$protocol]);
    }

    /**
     * @return int
     * One of STREAM_IS_* constants
     */
    protected static function protocolFlags(): int
    {
        return STREAM_IS_URL;
    }
}