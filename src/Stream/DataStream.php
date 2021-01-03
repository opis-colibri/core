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

namespace Opis\Colibri\Stream;

class DataStream implements Stream
{

    protected ?string $content = null;
    protected int $length = 0;
    protected int $pointer = 0;
    protected ?array $stat = null;
    protected bool $readable = false;
    protected bool $writable = false;
    protected bool $seekable = true;
    protected ?array $meta = null;

    /**
     * DataStream constructor.
     * @param string $data
     * @param string $mode
     * @param int|null $created
     * @param int|null $updated
     * @param array $meta
     */
    public function __construct(
        string $data,
        string $mode = 'rb',
        ?int $created = null,
        ?int $updated = null,
        array $meta = []
    ) {
        $this->content = $data;
        $this->length = strlen($data);

        $this->meta = $meta + [
                'timed_out' => false,
                'blocked' => false,
                'unread_bytes' => 0,
                'stream_type' => 'opis/stream',
                'wrapper_type' => 'content',
            ];

        $this->meta['mode'] = $mode;

        $list = str_split($mode);
        $this->readable = (bool)array_intersect($list, ['r', '+']);
        $this->writable = (bool)array_intersect($list, ['w', 'a', 'x', 'c', '+']);
        $this->seekable = !in_array('a', $list);

        unset($list, $mode, $meta);

        if (!$this->seekable) {
            $this->pointer = $this->length;
        }

        $created = $created ?? time();
        $updated = $updated ?? $created;

        $this->stat = [
            0 => 0,
            'dev' => 0,
            1 => 0,
            'ino' => 0,
            2 => 0777 | 0x8000,
            'mode' => 0777 | 0x8000,
            3 => 0,
            'nlink' => 0,
            4 => 0,
            'uid' => 0,
            5 => 0,
            'gid' => 0,
            6 => 0,
            'rdev' => 0,
            7 => $this->length,
            'size' => $this->length,
            8 => 0,
            'atime' => 0,
            9 => $updated,
            'mtime' => $updated,
            10 => $created,
            'ctime' => $created,
            11 => -1,
            'blksize' => -1,
            12 => -1,
            'blocks' => -1,
        ];
    }

    /**
     * @inheritDoc
     */
    public function read(int $length = 8192): ?string
    {
        if (!$this->readable || $this->isEOF()) {
            return null;
        }

        $data = substr($this->content, $this->pointer, $length);

        $this->pointer += $length;

        if ($this->pointer > $this->length) {
            $this->pointer = $this->length;
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function readLine(?int $maxLength = null): ?string
    {
        if (!$this->readable || $this->isEOF()) {
            return null;
        }

        $pos = strpos($this->content, "\n", $this->pointer);

        if ($pos === false) {
            if ($maxLength) {
                return $this->read($maxLength);
            }
            return $this->readToEnd();
        }

        $data = substr($this->content, $this->pointer, $pos - $this->pointer);

        $this->pointer = $pos + 1;

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function readToEnd(): ?string
    {
        if (!$this->readable || $this->isEOF()) {
            return null;
        }

        $data = substr($this->content, $this->pointer);

        $this->pointer = $this->length;

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function write(string $string): ?int
    {
        if (!$this->writable || $this->content === null) {
            return null;
        }

        $len = strlen($string);

        if ($len === 0) {
            return 0;
        }

        $data = substr($this->content, 0, $this->pointer);
        $data .= $string;
        unset($string);

        $ptr = $len + $this->pointer;
        if ($ptr < $this->length) {
            $data .= substr($this->content, $ptr);
        }

        $this->content = $data;
        $this->length = strlen($data);
        $this->pointer = $ptr > $this->length ? $this->length : $ptr;

        return $len;
    }

    /**
     * @inheritDoc
     */
    public function truncate(int $size): bool
    {
        if (!$this->writable || $this->content === null) {
            return false;
        }

        if ($size === $this->length) {
            return true;
        }

        if ($size < 0) {
            $size = 0;
        }

        if ($size < $this->length) {
            $this->content = substr($this->content, 0, $size);
            if ($this->pointer > $size) {
                $this->pointer = $size;
            }
        } else {
            $this->content .= str_repeat("\x0", $size - $this->length);
        }

        $this->length = $size;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function flush(): bool
    {
        return $this->writable && $this->content !== null;
    }

    /**
     * @inheritDoc
     */
    public function tell(): ?int
    {
        return !$this->seekable || $this->content === null ? null : $this->pointer;
    }

    /**
     * @inheritDoc
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (!$this->seekable || $this->content === null) {
            return false;
        }

        $crt = $this->pointer;

        switch ($whence) {
            case SEEK_SET:
                $this->pointer = $offset;
                break;
            case SEEK_CUR:
                $this->pointer += $offset;
                break;
            case SEEK_END:
                $this->pointer = $this->length + $offset;
                break;
        }

        if ($this->pointer < 0 || $this->pointer >= $this->length) {
            $this->pointer = $crt;
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): bool
    {
        if (!$this->seekable || $this->content === null) {
            return false;
        }
        $this->pointer = 0;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        $this->content = null;
        $this->stat = null;
        $this->meta = null;
        $this->length = $this->pointer = 0;
    }

    /**
     * @inheritDoc
     */
    public function isWritable(): bool
    {
        return $this->writable && $this->content !== null;
    }

    /**
     * @inheritDoc
     */
    public function isReadable(): bool
    {
        return $this->readable && $this->content !== null;
    }

    /**
     * @inheritDoc
     */
    public function isSeekable(): bool
    {
        return $this->seekable && $this->content !== null;
    }

    /**
     * @inheritDoc
     */
    public function isEOF(): bool
    {
        return $this->content === null || $this->pointer >= $this->length;
    }

    /**
     * @inheritDoc
     */
    public function isClosed(): bool
    {
        return $this->content === null;
    }

    /**
     * @inheritDoc
     */
    public function size(): ?int
    {
        return $this->content === null ? null : $this->length;
    }

    /**
     * @inheritDoc
     */
    public function stat(): ?array
    {
        if ($this->content === null) {
            return null;
        }

        if ($this->stat['size'] !== $this->length) {
            $this->stat[7] = $this->stat['size'] = $this->length;
        }

        return $this->stat;
    }

    /**
     * @inheritDoc
     */
    public function lock(int $operation): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function metadata(string $key = null)
    {
        if ($this->meta === null) {
            return null;
        }

        $meta = ['seekable' => $this->isSeekable(), 'eof' => $this->isEOF()] + $this->meta;

        if ($key === null) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function resource(bool $detach = false)
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function __toString()
    {
        return $this->content ?? '';
    }
}