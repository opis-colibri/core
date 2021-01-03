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

namespace Opis\Colibri\Stream\Wrapper;

use Opis\Colibri\Stream\Content;

class CallbackStreamWrapper extends ContentStreamWrapper
{
    const PROTOCOL = 'callback';

    /**
     * @inheritDoc
     */
    protected function content(string $path): ?Content
    {
        $prefix = static::PROTOCOL . '://';
        if (strpos($path, $prefix) !== 0) {
            return null;
        }

        $path = static::formatPath(substr($path, strlen($prefix)));
        unset($prefix);

        if (!is_callable($path)) {
            return null;
        }

        return $this->getCallbackContent($path);
    }

    /**
     * @param callable $func
     * @return Content|null
     */
    protected function getCallbackContent(callable $func): ?Content
    {
        return new Content($func);
    }

    /**
     * @inheritDoc
     */
    protected function cacheKey(string $path): string
    {
        return md5(static::formatPath($path));
    }

    /**
     * @inheritDoc
     */
    public static function protocol(): string
    {
        return static::PROTOCOL;
    }
    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param string $callback
     * @return string
     */
    public static function url(string $callback): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return static::PROTOCOL . '://' . $callback . '?' . bin2hex(random_bytes(16));
    }

    /**
     * @param string $callback
     * @param array|null $params
     * @return null|string
     */
    public static function execute(string $callback, ?array $params = null): ?string
    {
        $alreadyRegistered = static::isRegistered();
        if (!$alreadyRegistered) {
            if (!static::register()) {
                return null;
            }
        }

        $url = static::url($callback);
        $ctx = null;
        if ($params) {
            $ctx = static::createContext($params);
        }

        $data = file_get_contents($url, false, $ctx);

        if (!$alreadyRegistered) {
            static::unregister();
        }

        return is_string($data) ? $data : null;
    }

    /**
     * @param string $path
     * @return string
     */
    protected static function formatPath(string $path): string
    {
        return strpos($path, '?') === false ? $path : strstr($path, '?', true);
    }
}