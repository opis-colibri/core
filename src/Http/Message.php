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

namespace Opis\Colibri\Http;

use Opis\Colibri\Stream\Stream;

abstract class Message
{
    protected array $headers = [];

    protected string $protocolVersion;

    protected ?Stream $body;

    /**
     * Message constructor.
     * @param null|Stream $body
     * @param array $headers
     * @param string $protocolVersion
     */
    public function __construct(?Stream $body = null, array $headers = [], string $protocolVersion = 'HTTP/1.1')
    {
        $this->body = $body;
        $this->headers = $this->filterHeaders($headers);
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * @return null|Stream
     */
    public function getBody(): ?Stream
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$this->formatHeader($name)]);
    }

    /**
     * @param string $name
     * @param string|null $default
     * @return null|string
     */
    public function getHeader(string $name, string $default = null): ?string
    {
        return $this->headers[$this->formatHeader($name)] ?? $default;
    }

    /**
     * @param array $headers
     * @return array
     */
    protected function filterHeaders(array $headers): array
    {
        $result = [];

        foreach ($headers as $name => $value) {
            if (!is_scalar($value) || !is_string($name)) {
                continue;
            }
            $name = $this->formatHeader($name);
            $result[$name] = trim($value);
        }

        return $result;
    }

    /**
     * @param string $header
     * @return string
     */
    protected function formatHeader(string $header): string
    {
        return ucwords(strtolower(trim($header)), '-');
    }
}