<?php
/* ============================================================================
 * Copyright 2018-2021 Zindex Software
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

namespace Opis\Colibri\Http;

use Throwable;
use InvalidArgumentException, RuntimeException;
use Opis\Colibri\Stream\{Stream, ResourceStream};

class UploadedFile
{
    protected ?string $name;
    protected ?string $type;
    protected int $error = UPLOAD_ERR_OK;
    protected ?int $size = null;
    protected ?Stream $stream = null;
    protected ?string $file = null;
    protected bool $moved = false;

    /**
     * @param string|Stream $file
     * @param string|null $name
     * @param int|null $size
     * @param string|null $type
     * @param int $error
     */
    public function __construct(
        string|Stream $file,
        ?string $name = null,
        ?int $size = null,
        ?string $type = null,
        int $error = UPLOAD_ERR_OK
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->error = $error;

        if ($error !== UPLOAD_ERR_OK) {
            return;
        }

        if (is_string($file)) {
            if (!is_file($file)) {
                throw new RuntimeException("File {$file} does not exists");
            }
            if (substr(PHP_SAPI, 0, 3) !== 'cli' && !is_uploaded_file($file)) {
                throw new RuntimeException("File {$file} was not uploaded");
            }
            $this->file = realpath($file);
            if ($size === null) {
                $size = filesize($file);
            }
        } elseif ($file instanceof Stream) {
            $this->stream = $file;
            if ($size === null) {
                $size = $this->stream->size();
            }
        }

        $this->size = $size;
    }

    public function getStream(): Stream
    {
        if ($this->moved || $this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("Stream is not available");
        }
        if ($this->stream === null) {
            $this->stream = new ResourceStream($this->file);
        }
        return $this->stream;
    }

    public function wasMoved(): bool
    {
        return $this->moved;
    }

    public function moveToFile(string $destination): bool
    {
        if ($this->moved) {
            throw new RuntimeException("File was already moved");
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("File was not properly uploaded! Upload error: {$this->error}");
        }

        $targetDir = dirname($destination);
        if (!is_dir($targetDir)) {
            throw new RuntimeException("Directory {$targetDir} does not exists");
        }
        if (!is_writable($targetDir)) {
            throw new RuntimeException("Directory {$targetDir} is not writable");
        }

        $ok = false;

        if ($this->file !== null) {
            if (str_starts_with(PHP_SAPI, 'cli')) {
                $ok = rename($this->file, $destination);
            } else {
                $ok = is_uploaded_file($this->file) && move_uploaded_file($this->file, $destination);
            }
        } elseif ($this->stream !== null) {
            $ok = $this->copyContents($this->stream, new ResourceStream(realpath($destination), 'wb'));
        }

        if ($ok) {
            $this->moved = true;
        }

        return $ok;
    }

    public function moveToStream(Stream $destination): bool
    {
        if ($this->moved) {
            throw new RuntimeException("File was already moved");
        }

        if ($this->error !== UPLOAD_ERR_OK) {
            throw new RuntimeException("File was not properly uploaded! Upload error: {$this->error}");
        }

        if (!$destination->isWritable()) {
            throw new InvalidArgumentException("Stream is not writable");
        }

        if ($this->copyContents($this->getStream(), $destination)) {
            $this->moved = true;
            return true;
        }

        return false;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function hasError(): bool
    {
        return $this->error !== UPLOAD_ERR_OK;
    }

    public function getClientFilename(): ?string
    {
        return $this->name;
    }

    public function getClientMediaType(): ?string
    {
        return $this->type;
    }

    protected function copyContents(Stream $from, Stream $to, bool $close = true): bool
    {
        if (!$from->isReadable() || !$to->isWritable()) {
            return false;
        }

        try {
            while (!$from->isEOF()) {
                $to->write($from->read());
            }
        } catch (Throwable) {
            return false;
        } finally {
            if ($close) {
                $from->close();
            }
        }

        return true;
    }

    public static function factory(array $file): self
    {
        return new self(
            $file['tmp_name'] ?? null,
            $file['name'] ?? null,
            $file['size'] ?? null,
            $file['type'] ?? null,
            $file['error'] ?? UPLOAD_ERR_OK
        );
    }

    /**
     * @param array $files
     * @return array
     */
    public static function parseFiles(array $files): array
    {
        $list = [];

        foreach ($files as $key => $file) {
            if ($file instanceof self) {
                $list[$key] = $file;
                continue;
            }

            if (!$file || !is_array($file)) {
                continue;
            }

            if (isset($file['tmp_name'])) {
                if (!is_array($file['tmp_name'])) {
                    $list[$key] = self::factory($file);
                    continue;
                }

                $nested = [];
                foreach ($file['tmp_name'] as $index => $name) {
                    $nested[$index] = [
                        'tmp_name' => $name,
                        'size' => $file['size'][$index] ?? null,
                        'error' => $file['error'][$index] ?? UPLOAD_ERR_OK,
                        'name' => $file['name'][$index] ?? null,
                        'type' => $file['type'][$index] ?? null,
                    ];
                }

                $list[$key] = self::parseFiles($nested);
                continue;
            }

            $list[$key] = self::parseFiles($file);
        }

        return $list;
    }
}