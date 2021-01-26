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

use Opis\Colibri\FileSystem\Handler\FileSystemHandler;

class StreamWrapperMountManager extends MountManager
{
    protected string $protocol;

    /**
     * @param FileSystemHandler[] $handlers
     * @param string $protocol
     */
    public function __construct(array $handlers, string $protocol)
    {
        parent::__construct($handlers);
        $this->protocol = $protocol;
    }

    public function protocol(): string
    {
        return $this->protocol;
    }

    public function rename(string $from, string $to): ?FileInfo
    {
        if (!str_contains($from, '://')) {
            return null;
        }

        if (!str_contains($to, '://')) {
            $to = $this->mergePaths($to, $from);
            if ($to === null) {
                return null;
            }
        }

        [$proto_from, $from] = explode('://', $from, 2);
        $handler_from = $this->handler($proto_from);
        if ($handler_from === null) {
            return null;
        }
        $from = $this->normalizePath($from);

        [$proto_to, $to] = explode('://', $to, 2);
        $to = $this->normalizePath($to);

        if ($proto_from === $proto_to) {
            $info = $handler_from->rename($from, $to);
            if ($info instanceof ProtocolInfo) {
                $info->setProtocol($proto_to);
            }
            return $info;
        }

        $from = $this->protocol . '://' . $proto_from . '/' . $from;
        $to = $this->protocol . '://' . $proto_to . '/' . $to;

        if (!@rename($from, $to)) {
            return null;
        }

        return $this->info($proto_to . '://' . $to);
    }

    public function copy(string $from, string $to, bool $overwrite = true): ?FileInfo
    {
        if (!str_contains($from, '://')) {
            return null;
        }

        if (!str_contains($to, '://')) {
            $to = $this->mergePaths($to, $from);
            if ($to === null) {
                return null;
            }
        }

        [$proto_from, $from] = explode('://', $from, 2);
        $handler_from = $this->handler($proto_from);
        if ($handler_from === null) {
            return null;
        }
        $from = $this->normalizePath($from);

        [$proto_to, $to] = explode('://', $to, 2);
        $to = $this->normalizePath($to);

        if ($proto_from === $proto_to) {
            $info = $handler_from->copy($from, $to, $overwrite);
            if ($info instanceof ProtocolInfo) {
                $info->setProtocol($proto_to);
            }
            return $info;
        }

        $from = $this->protocol . '://' . $proto_from . '/' . $from;
        $to = $this->protocol . '://' . $proto_to . '/' . $to;

        if (!@copy($from, $to)) {
            return null;
        }

        return $this->info($proto_to . '://' . $to);
    }

    /**
     * @param string $path
     * @param string $mode
     * @param array|null $context_options
     * @param array|null $context_params
     * @return resource|null
     */
    public function open(string $path, string $mode = 'rb', ?array $context_options = null, ?array $context_params = null)
    {
        $path = $this->absolutePath($path, $this->protocol);
        if ($path === null) {
            return null;
        }

        $ctx = null;
        if ($context_options || $context_params) {
            $ctx = $this->createContext($context_options ?? [], $context_params);
        }
        unset($context_options, $context_params);

        if ($ctx) {
            $resource = @fopen($path, $mode, false, $ctx);
        } else {
            $resource = @fopen($path, $mode, false);
        }

        return is_resource($resource) ? $resource : null;
    }

    public function contents(string $path, ?array $context_options = null, ?array $context_params = null): ?string
    {
        $path = $this->absolutePath($path, $this->protocol);
        if ($path === null) {
            return null;
        }

        $ctx = null;
        if ($context_options || $context_params) {
            $ctx = $this->createContext($context_options ?? [], $context_params);
        }
        unset($context_options, $context_params);

        $data = file_get_contents($path, false, $ctx);

        return is_string($data) ? $data : null;
    }

    /**
     * @param array $options
     * @param array|null $params
     * @return resource
     */
    public function createContext(array $options, ?array $params = null)
    {
        return stream_context_create([$this->protocol => $options], $params);
    }
}