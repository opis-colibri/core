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

namespace Opis\Colibri\Stream\Printer;

use InvalidArgumentException;
use Opis\Colibri\Stream\Stream;

class StandardPrinter
{

    protected Stream $stream;

    /**
     * Printer constructor.
     * @param Stream $stream
     */
    public function __construct(Stream $stream)
    {
        if ($stream->isClosed() || !$stream->isWritable()) {
            throw new InvalidArgumentException('Stream is not writable');
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
     * @param mixed ...$args
     * @return int|null
     * @see sprintf()
     */
    public function print(string $format, ...$args): ?int
    {
        return $this->stream->write(sprintf($format, ...$args));
    }

    /**
     * @param string $data
     * @return int|null
     */
    public function write(string $data): ?int
    {
        return $this->stream->write($data);
    }
}