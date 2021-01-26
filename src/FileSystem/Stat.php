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

use JsonSerializable;

class Stat implements JsonSerializable
{
    /** @var int[] */
    protected array $info;

    /**
     * @param int[] $info
     */
    public function __construct(array $info)
    {
        $this->info = $info;
    }

    /**
     * Device number
     * @return int
     */
    public function dev(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Inode number or 0
     * @return int
     */
    public function ino(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Inode protection mode
     * @return int
     */
    public function mode(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Number of links or 0
     * @return int
     */
    public function nlink(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * User id of owner or 0
     * @return int
     */
    public function uid(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Group id of owner or 0
     * @return int
     */
    public function gid(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Device type, if inode device or 0
     * @return int
     */
    public function rdev(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Size in bytes
     * @return int
     */
    public function size(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Time of last access
     * @return int
     */
    public function atime(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Time of last modification
     * @return int
     */
    public function mtime(): int
    {
        return $this->info[__FUNCTION__] ?? $this->ctime();
    }

    /**
     * Time of last inode change
     * @return int
     */
    public function ctime(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Block size of filesystem IO or -1
     * @return int
     */
    public function blksize(): int
    {
        return $this->info[__FUNCTION__] ?? -1;
    }

    /**
     * Number of 512-byte blocks allocated or -1
     * @return int
     */
    public function blocks(): int
    {
        return $this->info[__FUNCTION__] ?? 0;
    }

    /**
     * Checks if stat is for a file
     * @return bool
     */
    public function isFile(): bool
    {
        return ($this->mode() & 0xF000) === 0x8000;
    }

    /**
     * Checks if stat is for a directory
     * @return bool
     */
    public function isDir(): bool
    {
        return ($this->mode() & 0xF000) === 0x4000;
    }

    /**
     * Checks if stat is for a link
     * @return bool
     */
    public function isLink(): bool
    {
        return ($this->mode() & 0xF000) === 0xA000;
    }

    /**
     * @param bool $indexed
     * @return array
     */
    public function toArray(bool $indexed = true): array
    {
        $stat = [
            'dev' => $this->dev(),
            'ino' => $this->ino(),
            'mode' => $this->mode(),
            'nlink' => $this->nlink(),
            'uid' => $this->uid(),
            'gid' => $this->gid(),
            'rdev' => $this->rdev(),
            'size' => $this->size(),
            'atime' => $this->atime(),
            'mtime' => $this->mtime(),
            'ctime' => $this->ctime(),
            'blksize' => $this->blksize(),
            'blocks' => $this->blocks(),
        ];

        return $indexed ? array_merge(array_values($stat), $stat) : $stat;
    }

    public function jsonSerialize()
    {
        return $this->toArray(false);
    }

    public function __serialize(): array
    {
        return $this->toArray(false);
    }

    public function __unserialize(array $data): void
    {
        $this->info = $data;
    }
}