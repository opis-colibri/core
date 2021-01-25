<?php
/* ============================================================================
 * Copyright 2021 Zindex Software
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

use Psr\Http\Message\StreamInterface;

class PSRStream implements Stream
{
    protected array $stat;
    protected bool $isClosed = false;
    protected StreamInterface $stream;

    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;

        $size = null;
        if ($stream->isReadable()) {
            $size = $stream->getSize();
        }

        $time = time();

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
            7 => $size,
            'size' => $size,
            8 => 0,
            'atime' => 0,
            9 => $time,
            'mtime' => $time,
            10 => $time,
            'ctime' => $time,
            11 => -1,
            'blksize' => -1,
            12 => -1,
            'blocks' => -1,
        ];
    }

    public function psrInstance(): StreamInterface
    {
        return $this->stream;
    }

    public function read(int $length = 8192): ?string
    {
        return $this->stream->read($length);
    }

    public function readLine(?int $maxLength = null): ?string
    {
        return null;
    }

    public function readToEnd(): ?string
    {
        return $this->stream->getContents();
    }

    public function write(string $string): ?int
    {
        if (!$this->stream->isWritable()) {
            return null;
        }
        return $this->stream->write($string);
    }

    public function truncate(int $size): bool
    {
        // cannot truncate
        return false;
    }

    public function flush(): bool
    {
        return $this->stream->isWritable();
    }

    public function tell(): ?int
    {
        return $this->stream->tell();
    }

    public function seek(int $offset, int $whence = SEEK_SET): bool
    {
        if (!$this->stream->isSeekable()) {
            return false;
        }
        $this->stream->seek($offset, $whence);
        return true;
    }

    public function rewind(): bool
    {
        if (!$this->stream->isSeekable()) {
            return false;
        }
        $this->stream->rewind();
        return true;
    }

    public function close(): void
    {
        $this->stream->close();
        $this->isClosed = true;
    }

    public function isWritable(): bool
    {
        return $this->stream->isWritable();
    }

    public function isReadable(): bool
    {
        return $this->stream->isReadable();
    }

    public function isSeekable(): bool
    {
        return $this->stream->isSeekable();
    }

    public function isEOF(): bool
    {
        return $this->stream->eof();
    }

    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    public function size(): ?int
    {
        return $this->stream->getSize();
    }

    public function stat(): ?array
    {
        return $this->stat;
    }

    public function lock(int $operation): bool
    {
        return false;
    }

    public function metadata(?string $key = null): mixed
    {
        return $this->stream->getMetadata($key);
    }

    public function resource(bool $detach = false)
    {
        if ($detach) {
            return $this->stream->detach();
        }
        return null;
    }

    public function __toString(): string
    {
        return (string)$this->stream;
    }
}