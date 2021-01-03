<?php
/* ===========================================================================
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

interface Stream
{
    const SEEK_SET = \SEEK_SET;
    const SEEK_CUR = \SEEK_CUR;
    const SEEK_END = \SEEK_END;

    /**
     * Consumes data from stream
     * @param int $length
     * @return string|null
     */
    public function read(int $length = 8192): ?string;

    /**
     * Consumes the current line
     * Line delimiters \r & \n are trimmed
     * @param int|null $maxLength
     * @return null|string
     */
    public function readLine(?int $maxLength = null): ?string;

    /**
     * Consumes all the remaining data from stream
     * @return string|null
     */
    public function readToEnd(): ?string;

    /**
     * Appends data to stream
     * @param string $string
     * @return int|null
     */
    public function write(string $string): ?int;

    /**
     * @param int $size
     * @return bool
     */
    public function truncate(int $size): bool;

    /**
     * @return bool
     */
    public function flush(): bool;

    /**
     * Current position of the pointer
     * @return int|null
     */
    public function tell(): ?int;

    /**
     * Sets the pointer position
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * Performs seek(0)
     * @return bool
     */
    public function rewind(): bool;

    /**
     * Closes the stream
     */
    public function close(): void;

    /**
     * Checks if the stream is writable
     * @return bool
     */
    public function isWritable(): bool;

    /**
     * Checks if the stream is readable
     * @return bool
     */
    public function isReadable(): bool;

    /**
     * Checks if the stream is seekable
     * @return bool
     */
    public function isSeekable(): bool;

    /**
     * Checks if pointer reached end-of-file
     * @return bool
     */
    public function isEOF(): bool;

    /**
     * Checks if the stream was closed
     * @return bool
     */
    public function isClosed(): bool;

    /**
     * Gets stream size
     * @return int|null
     */
    public function size(): ?int;

    /**
     * @return array|null
     */
    public function stat(): ?array;

    /**
     * @param int $operation
     * @return bool
     */
    public function lock(int $operation): bool;

    /**
     * Get stream meta information
     * @param string|null $key
     * @return mixed|array|null
     * @see stream_get_meta_data()
     */
    public function metadata(string $key = null);

    /**
     * Gets the associated resource, if any
     * @param bool $detach Marks resource as detached, so it won't be closed when this object is destructed.
     *                      You have to close the resource the resource using fclose().
     * @return resource|null
     */
    public function resource(bool $detach = false);

    /**
     * Gets all stream data and restores pointer position if possible
     * @return string
     */
    public function __toString();
}