<?php
/* ===========================================================================
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

use Opis\Uri\Uri;
use Opis\Colibri\Stream\{ResourceStream, Stream};

class Request extends Message
{
    protected string $method;
    protected string $requestTarget;
    protected bool $secure;
    protected ?Uri $uri = null;
    protected ?array $cookies = null;
    protected array $files;
    protected ?array $query = null;
    protected ?array $formData;
    protected ServerVariables $serverVars;

    /**
     * Request constructor.
     * @param string $method
     * @param string $requestTarget
     * @param string $protocolVersion
     * @param bool $secure
     * @param array $headers
     * @param array $files
     * @param null|Stream $body
     * @param array|null $cookies
     * @param array|null $query
     * @param array|null $formData
     * @param ServerVariables|null $serverVars
     */
    public function __construct(
        string $method = 'GET',
        string $requestTarget = '/',
        string $protocolVersion = 'HTTP/1.1',
        bool $secure = false,
        array $headers = [],
        array $files = [],
        ?Stream $body = null,
        ?array $cookies = null,
        ?array $query = null,
        ?array $formData = null,
        ?ServerVariables $serverVars = null
    ) {

        $this->method = strtoupper($method);
        $this->requestTarget = $requestTarget;
        $this->files = $files;
        $this->secure = $secure;

        if (!in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $body = null;
        } else {
            if ($body === null) {
                $body = new ResourceStream('php://input');
            }
        }

        $this->cookies = $cookies;
        $this->query = $query;
        $this->formData = $formData;
        $this->serverVars = $serverVars ?? new ServerVariables();

        parent::__construct($body, $headers, $protocolVersion);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    /**
     * @return Uri
     */
    public function getUri(): Uri
    {
        if ($this->uri === null) {
            $components = Uri::parseComponents($this->requestTarget);

            if (!isset($components['host'])) {
                if (isset($this->headers['Host'])) {
                    $port = null;
                    $host = $this->headers['Host'];
                    if (str_contains($host, ':')) {
                        [$host, $port] = explode(':', $host);
                        $port = (int) $port;
                        if (!Uri::isValidPort($port)) {
                            $port = null;
                        }
                    }
                    $components['host'] = $host;
                    $components['port'] = $port;
                    $components['authority'] = $port === null ? $host : $host . ':' . $port;
                }
            }

            if (isset($components['host'])) {
                if (!isset($components['scheme'])) {
                    $components['scheme'] = $this->secure ? 'https' : 'http';
                }
            }

            // Remove standard port
            if (isset($components['port']) && isset($components['scheme'])) {
                if (($components['scheme'] === 'http' && $components['port'] === 80)
                    || ($components['scheme'] === 'https' && $components['port'] === 443)) {
                    $components['port'] = null;
                }
            }

            $this->uri = new Uri($components);
        }

        return $this->uri;
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->getCookies()[$name]);
    }

    /**
     * @param string $name
     * @param bool $decode
     * @return string|null
     */
    public function getCookie(string $name, bool $decode = true): ?string
    {
        $cookie = $this->getCookies()[$name] ?? null;

        if ($decode && $cookie !== null) {
            return rawurldecode($cookie);
        }

        return $cookie;
    }

    /**
     * @return array
     */
    public function getCookies(): array
    {
        if ($this->cookies === null) {
            $result = [];
            $cookies = explode(';', $this->headers['Cookie']);
            foreach ($cookies as $cookie) {
                [$name, $value] = explode('=', $cookie, 2);
                $name = trim($name);
                if ($name === '') {
                    continue;
                }
                if ($value !== '' && $value[0] === '"' && $value[-1] === '"') {
                    $value = substr($value, 1, -1);
                }
                $result[$name] = rawurldecode($value);
            }
            $this->cookies = $result;
        }

        return $this->cookies;
    }

    /**
     * @return UploadedFile[]
     */
    public function getUploadedFiles(): array
    {
        return $this->files;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        if ($this->query === null) {
            $query = $this->getUri()->query();
            if ($query === null) {
                $query = [];
            } else {
                parse_str($query, $query);
            }
            $this->query = $query;
        }

        return $this->query;
    }

    /**
     * @return array
     */
    public function getFormData(): array
    {
        if ($this->formData === null) {
            $data = null;
            if ($this->body !== null && isset($this->headers['Content-Type'])) {
                if (str_contains($this->headers['Content-Type'], 'application/x-www-form-urlencoded')) {
                    parse_str((string)$this->body, $data);
                }
            }
            $this->formData = is_array($data) ? $data : [];
        }

        return $this->formData;
    }

    /**
     * @return ServerVariables
     */
    public function getServerVariables(): ServerVariables
    {
        return $this->serverVars;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function query(string $name, mixed $default = null): mixed
    {
        return $this->getQuery()[$name] ?? $default;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed
     */
    public function formData(string $name, mixed $default = null): mixed
    {
        return $this->getFormData()[$name] ?? $default;
    }

    /**
     * @param string $name
     * @return UploadedFile|null
     */
    public function file(string $name): ?UploadedFile
    {
        return $this->getUploadedFiles()[$name] ?? null;
    }

    /**
     * @return Request
     */
    public static function fromGlobals(): self
    {
        $vars = $_SERVER;

        $method = strtoupper($vars['REQUEST_METHOD'] ?? 'GET');

        if ($method === 'POST') {
            if (isset($vars['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                $method = strtoupper($vars['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } elseif (isset($_POST['x_http_method_override'])) {
                $method = strtoupper($_POST['x_http_method_override']);
            }
        }

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            $headers = [];
            foreach ($vars as $key => $value) {
                if (!is_scalar($value)) {
                    continue;
                }
                if (str_starts_with($key, 'HTTP_')) {
                    $key = substr($key, 5);
                } elseif (!str_starts_with($key, 'CONTENT_')) {
                    continue;
                }
                $key = str_replace('_', '-', $key);
                $headers[$key] = $value;
            }
        }

        if (($vars['PATH_INFO'] ?? '') !== '') {
            $requestTarget = $vars['PATH_INFO'];
            if (($vars['QUERY_STRING'] ?? '') !== '') {
                $requestTarget .= '?' . $vars['QUERY_STRING'];
            }
        } else {
            $requestTarget = $vars['REQUEST_URI'] ?? '/';
        }

        return new self(
            $method,
            $requestTarget,
            $vars['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
            ($vars['HTTPS'] ?? 'off') !== 'off',
            $headers,
            $_FILES ? UploadedFile::parseFiles($_FILES) : [],
            null,
            $_COOKIE,
            $_GET,
            $_POST,
            new ServerVariables($vars),
        );
    }
}