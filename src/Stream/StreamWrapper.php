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

interface StreamWrapper
{
    /**
     * @param string $path
     * @param string $mode
     * @param int $options
     * @param null|string $opened_path
     * @return bool
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool;

    /**
     * Closes the stream
     * @return void
     */
    public function stream_close(): void;

    /**
     * @param int $count
     * @return null|string
     */
    public function stream_read(int $count): ?string;

    /**
     * @param int $offset
     * @param int $whence
     * @return bool
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool;

    /**
     * @return int|null
     */
    public function stream_tell(): ?int;

    /**
     * Checks for end-of-file
     * @return bool
     */
    public function stream_eof(): bool;

    /**
     * @return array|null
     */
    public function stream_stat(): ?array;

    /**
     * @return bool
     */
    public function stream_flush(): bool;

    /**
     * @param int $operation
     * @return bool
     */
    public function stream_lock(int $operation): bool;

    /**
     * @param int $size
     * @return bool
     */
    public function stream_truncate(int $size): bool;

    /**
     * @param string $data
     * @return int|null
     */
    public function stream_write(string $data): ?int;

    /**
     * @param int $opt
     * @return resource|null
     */
    public function stream_cast(int $opt);

    /**
     * @param int $option
     * @param int $arg1
     * @param int|null $arg2
     * @return bool
     */
    public function stream_set_option(int $option, int $arg1, ?int $arg2 = null): bool;
}