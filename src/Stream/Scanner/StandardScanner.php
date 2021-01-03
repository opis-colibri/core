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

class StandardScanner
{

    protected Stream $stream;

    /**
     * Scanner constructor.
     * @param Stream $stream
     */
    public function __construct(Stream $stream)
    {
        if ($stream->isClosed() || !$stream->isReadable()) {
            throw new InvalidArgumentException('Stream is not readable');
        }

        $this->stream = $stream;
    }

    /**
     * @return Stream
     */
    public function stream(): Stream
    {
        return $this->stream;
    }

    /**
     * @param string $format
     * @param int|null $maxLineLength
     * @return array|null
     * @see sscanf()
     */
    public function scan(string $format, ?int $maxLineLength = null): ?array
    {
        if (($line = $this->stream->readLine($maxLineLength)) === null) {
            return null;
        }

        $data = sscanf($line, $format);

        return is_array($data) ? $data : null;
    }

    /**
     * @param int $length
     * @return null|string
     */
    public function read(int $length = 8192): ?string
    {
        return $this->stream->read($length);
    }

    /**
     * @param int|null $maxLength
     * @return null|string
     */
    public function readLine(?int $maxLength = null): ?string
    {
        return $this->stream->readLine($maxLength);
    }

    /**
     * @return bool
     */
    public function isEOF(): bool
    {
        return $this->stream->isEOF();
    }
}