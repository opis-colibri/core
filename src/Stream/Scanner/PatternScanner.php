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

namespace Opis\Colibri\Stream\Scanner;

use InvalidArgumentException;
use Opis\Colibri\Stream\Stream;

class PatternScanner
{

    protected Stream $stream;

    /** @var string[]|null */
    protected ?array $tokens = null;

    protected string $buffer = '';

    protected ?string $skipped = null;

    /**
     * PatternScanner constructor.
     * @param Stream $stream
     * @param string[]|null $tokens
     */
    public function __construct(Stream $stream, ?array $tokens = null)
    {
        if ($stream->isClosed() || !$stream->isReadable()) {
            throw new InvalidArgumentException('Stream is not readable');
        }
        $this->stream = $stream;
        $this->tokens = $tokens;
    }

    /**
     * @return Stream
     */
    public function stream(): Stream
    {
        return $this->stream;
    }

    /**
     * @return null|string
     */
    public function skipped(): ?string
    {
        return $this->skipped;
    }

    /**
     * @param string $pattern
     * @param int $chunk
     * @return null|string
     */
    public function next(string $pattern, int $chunk = 128): ?string
    {
        return $this->doNext($pattern, false, $chunk);
    }

    /**
     * @param string $pattern
     * @param int $chunk
     * @return null|array
     */
    public function nextMatch(string $pattern, int $chunk = 128): ?array
    {
        return $this->doNext($pattern, true, $chunk);
    }

    /**
     * @param string $name
     * @param int $chunk
     * @return null|string
     */
    public function token(string $name, int $chunk = 128): ?string
    {
        $pattern = $this->tokens[$name] ?? null;
        if (!is_string($pattern)) {
            return null;
        }
        return $this->doNext($pattern, false, $chunk);
    }

    /**
     * @param string $name
     * @param int $chunk
     * @return array|null
     */
    public function tokenMatch(string $name, int $chunk = 128): ?array
    {
        $pattern = $this->tokens[$name] ?? null;
        if (!is_string($pattern)) {
            return null;
        }
        return $this->doNext($pattern, true, $chunk);
    }

    /**
     * @param int $length
     * @return null|string
     */
    public function read(int $length = 8192): ?string
    {
        $this->skipped = null;
        if ($this->buffer === '') {
            if ($this->stream->isEOF()) {
                return null;
            }
            $len = 0;
        } else {
            $len = strlen($this->buffer);
        }

        if ($length <= $len) {
            $data = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, $length);
            return $data;
        }

        $data = $this->buffer;

        $this->buffer = '';

        return $data . $this->stream->read($length - $len);
    }

    /**
     * @return bool
     */
    public function isEOF(): bool
    {
        return $this->buffer === '' && $this->stream->isEOF();
    }

    /**
     * @param string $pattern
     * @param bool $match
     * @param int $chunk
     * @return string|array|null
     */
    protected function doNext(string $pattern, bool $match, int $chunk)
    {
        $this->skipped = null;
        while (!$this->stream->isEOF()) {
            $data = $this->checkBuffer($pattern);
            if ($data !== null) {
                return $match ? $data : $data[0][0];
            }
            $this->buffer .= $this->stream->read($chunk);
        }

        $data = $this->checkBuffer($pattern);
        if ($data !== null) {
            return $match ? $data : $data[0][0];
        }

        $this->skipped = null;
        $this->buffer = '';

        return null;
    }

    /**
     * @param string $pattern
     * @return null|array
     */
    protected function checkBuffer(string $pattern): ?array
    {
        if ($this->buffer !== '' && preg_match($pattern, $this->buffer, $m, PREG_OFFSET_CAPTURE)) {
            $this->skipped = $m[0][1] > 0 ? substr($this->buffer, 0, $m[0][1]) : null;
            $this->buffer = substr($this->buffer, strlen($m[0][0]) + $m[0][1]);
            return $m;
        }

        return null;
    }
}