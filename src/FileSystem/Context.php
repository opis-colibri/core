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

namespace Opis\Colibri\FileSystem;

class Context
{
    protected array $options;
    protected bool $blocking = false;
    protected int $readTimeout = 0;
    protected int $readMode = STREAM_BUFFER_NONE;
    protected int $readBuffer = 8192;
    protected int $writeMode = STREAM_BUFFER_NONE;
    protected int $writeBuffer = 8192;
    protected string $protocol;
    /** @var resource|null */
    protected $context = null;

    /**
     * @param string $protocol
     * @param null|resource $context
     * @param array $options
     */
    public function __construct(string $protocol, $context = null, array $options = [])
    {
        $this->protocol = $protocol;

        // Get custom options
        if ($context && is_resource($context)) {
            $this->context = $context;
            $params = @stream_context_get_options($context)[$protocol] ?? null;
            if ($params && is_array($params)) {
                $options += $params;
            }
        }

        // Get default options
        $params = @stream_context_get_default()[$protocol] ?? null;
        if ($params && is_array($params)) {
            $options += $params;
        }


        $this->options = $options;
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return null|resource
     */
    public function resource()
    {
        return $this->context;
    }

    public function isBlocking(): bool
    {
        return $this->blocking;
    }

    public function setIsBlocking(bool $blocking): static
    {
        $this->blocking = $blocking;

        return $this;
    }

    public function getReadTimeout(): int
    {
        return $this->readTimeout;
    }

    public function setReadTimeout(int $timeout): static
    {
        $this->readTimeout = $timeout;
        return $this;
    }

    /**
     * @return int One of STREAM_BUFFER_* constants
     */
    public function getReadMode(): int
    {
        return $this->readMode;
    }

    /**
     * @param int $mode One of STREAM_BUFFER_* constants
     * @return static
     */
    public function setReadMode(int $mode): static
    {
        $this->readMode = $mode;
        return $this;
    }

    public function getReadBufferSize(): int
    {
        return $this->readBuffer;
    }

    public function setReadBufferSize(int $size): static
    {
        $this->readBuffer = $size;

        return $this;
    }

    /**
     * @return int One of STREAM_BUFFER_* constants
     */
    public function getWriteMode(): int
    {
        return $this->writeMode;
    }

    /**
     * @param int $mode One of STREAM_BUFFER_* constants
     * @return static
     */
    public function setWriteMode(int $mode): static
    {
        $this->writeMode = $mode;
        return $this;
    }

    public function getWriteBufferSize(): int
    {
        return $this->writeBuffer;
    }

    public function setWriteBufferSize(int $size): static
    {
        $this->writeBuffer = $size;

        return $this;
    }

    public function setOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function getOption(string $name, mixed $default = null): mixed
    {
        return $this->options[$name] ?? $default;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }
}