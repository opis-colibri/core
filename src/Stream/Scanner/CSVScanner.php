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

class CSVScanner
{

    protected Stream $stream;

    protected string $delimiter;

    protected string $enclosure;

    protected string $escape;

    protected int $length = 0;

    /** @var resource */
    protected $resource;

    /**
     * CSVScanner constructor.
     * @param Stream $stream
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param int $longest_line
     */
    public function __construct(
        Stream $stream,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\',
        int $longest_line = 0
    ) {
        if ($stream->isClosed() || !$stream->isReadable()) {
            throw new InvalidArgumentException('Stream is not readable');
        }

        $this->resource = $stream->resource();

        if (!$this->resource || !is_resource($this->resource)) {
            throw new InvalidArgumentException('Stream is not supported');
        }

        $this->stream = $stream;

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->length = $longest_line;
    }

    /**
     * @return Stream
     */
    public function stream(): Stream
    {
        return $this->stream;
    }

    /**
     * @return array|null
     */
    public function next(): ?array
    {
        if ($this->stream->isEOF()) {
            return null;
        }

        do {
            $data = fgetcsv($this->resource, $this->length, $this->delimiter, $this->enclosure, $this->escape);

            if ($data === null || $data === false) {
                return null;
            }

            if (count($data) === 1 && $data[0] === null) {
                continue;
            }

            return $data;
        } while (true);
    }

    /**
     * @return iterable|array[]
     */
    public function all(): iterable
    {
        while (($data = $this->next()) !== null) {
            yield $data;
        }
    }

    /**
     * @return bool
     */
    public function isEOF(): bool
    {
        return $this->stream->isEOF();
    }
}