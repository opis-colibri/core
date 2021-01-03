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
use Opis\Colibri\Stream\Scanner\CSVScanner;

class CSVPrinter
{

    protected Stream $stream;

    protected string $delimiter;

    protected string $enclosure;

    protected string $escape;

    /** @var resource */
    protected $resource;

    /**
     * CSVPrinter constructor.
     * @param Stream $stream
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct(
        Stream $stream,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ) {
        if ($stream->isClosed() || !$stream->isWritable()) {
            throw new InvalidArgumentException('Stream is not writable');
        }

        $this->resource = $stream->resource();

        if (!$this->resource || !is_resource($this->resource)) {
            throw new InvalidArgumentException('Stream is not supported');
        }

        $this->stream = $stream;

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    /**
     * @return Stream
     */
    public function stream(): Stream
    {
        return $this->stream;
    }

    /**
     * @param array $fields
     * @return bool
     */
    public function append(array $fields): bool
    {
        return fputcsv($this->resource, $fields, $this->delimiter, $this->enclosure, $this->escape) !== false;
    }

    /**
     * @param CSVScanner $scanner
     * @param int $limit
     * @return int
     */
    public function copy(CSVScanner $scanner, int $limit = 0): int
    {
        if ($limit <= 0) {
            $limit = PHP_INT_MAX;
        }

        $total = 0;

        while ($total <= $limit && ($fields = $scanner->next()) !== null) {
            if ($this->append($fields)) {
                $total++;
            }
        }

        return $total;
    }
}