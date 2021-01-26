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

namespace Opis\Colibri\FileSystem\Traits;

use Opis\Colibri\Stream\Stream;

trait StreamFileTrait
{
    protected ?Stream $file = null;

    public function stream_close(): void
    {
        if ($this->file) {
            $this->file->close();
        }
    }

    public function stream_eof(): bool
    {
        return $this->file ? $this->file->isEOF() : true;
    }

    public function stream_open(
        string $path,
        string $mode,
        /** @noinspection PhpUnusedParameterInspection */
        int $options,
        /** @noinspection PhpUnusedParameterInspection */
        ?string &$opened_path = null
    ): bool
    {
        $this->file = $this->file($path, $mode);
        return $this->file !== null;
    }

    public function stream_read(int $count): ?string
    {
        return $this->file ? $this->file->read($count) : null;
    }

    public function stream_stat(): ?array
    {
        return $this->file ? $this->file->stat() : null;
    }

    public function stream_tell(): ?int
    {
        return $this->file ? $this->file->tell() : null;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return $this->file ? $this->file->seek($offset, $whence) : false;
    }

    public function stream_flush(): bool
    {
        return $this->file ? $this->file->flush() : false;
    }

    public function stream_lock(int $operation): bool
    {
        return $this->file ? $this->file->lock($operation) : false;
    }

    public function stream_truncate(int $size): bool
    {
        return $this->file ? $this->file->truncate($size) : false;
    }

    public function stream_write(string $data): ?int
    {
        return $this->file ? $this->file->write($data) : null;
    }

    public function stream_cast(
        /** @noinspection PhpUnusedParameterInspection */
        int $opt
    )
    {
        return $this->file ? $this->file->resource() : null;
    }

    /**
     * @param string $path
     * @param string $mode
     * @return Stream|null
     */
    abstract protected function file(string $path, string $mode): ?Stream;
}