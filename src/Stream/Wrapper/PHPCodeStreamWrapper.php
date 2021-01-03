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

use Throwable;
use Opis\Colibri\Stream\{Content, Stream};

class PHPCodeStreamWrapper extends ContentStreamWrapper
{
    const PROTOCOL = 'php-code';

    /**
     * @inheritDoc
     */
    final protected function content(string $path): ?Content
    {
        $prefix = static::PROTOCOL . '://';
        if (strpos($path, $prefix) !== 0) {
            return null;
        }

        return $this->phpCodeContent(substr($path, strlen($prefix)));
    }

    /**
     * @param string $code
     * @return Content
     */
    protected function phpCodeContent(string $code): Content
    {
        return new Content($code, null, null, 'text/html');
    }

    /**
     * @inheritDoc
     */
    protected function streamMeta(Content $content, string $path, string $mode, ?array $options = null): array
    {
        return [
            'wrapper_type' => static::PROTOCOL,
            'mediatype' => $content->type(),
            'mode' => $mode,
            // no point to put uri here
        ];
    }

    /**
     * @inheritDoc
     */
    public static function protocol(): string
    {
        return static::PROTOCOL;
    }

    /**
     * @param string $code
     * @return string
     */
    public static function url(string $code): string
    {
        return static::PROTOCOL . '://' . $code;
    }

    /**
     * @param string $code
     * @param array|null $vars
     * @return mixed|Throwable
     */
    public static function include(string $code, ?array $vars = null)
    {
        $alreadyRegistered = static::isRegistered();
        if (!$alreadyRegistered) {
            if (!static::register()) {
                return false;
            }
        }

        if ($vars){
            $ret = include_code_internal(static::url($code), $vars);
        } else {
            $ret = include_code_internal(static::url($code));
        }

        if (!$alreadyRegistered) {
            static::unregister();
        }

        return $ret;
    }

    /**
     * @param string $code
     * @param array|null $vars
     * @return string
     */
    public static function template(string $code, ?array $vars = null): string
    {
        if (!ob_start()) {
            return '';
        }

        static::include($code, $vars);

        unset($code, $vars);

        return ob_get_clean();
    }

    /**
     * @param Stream $outputStream
     * @param string $code
     * @param array|null $vars
     * @param int $chunk
     * @return bool
     */
    public static function streamTemplate(Stream $outputStream, string $code, ?array $vars = null, int $chunk = 512): bool
    {
        if ($outputStream->isClosed() || !$outputStream->isWritable()) {
            return false;
        }

        $ok = ob_start(static function (string $data) use ($outputStream): bool {
            if ($data !== '') {
                return $outputStream->write($data) !== null;
            }
            return true;
        }, $chunk);

        if (!$ok) {
            return false;
        }

        unset($ok, $chunk);

        static::include($code, $vars);

        unset($code, $vars);

        return ob_end_flush();
    }
}

/**
 * @internal
 */
function include_code_internal() {
    if (func_num_args() > 1 && (${'#vars'} = func_get_arg(1))) {
        extract(${'#vars'}, EXTR_SKIP);
        unset(${'#vars'});
    }

    try {
        /** @noinspection PhpIncludeInspection */
        return include(func_get_arg(0));
    } catch (Throwable $e) {
        return $e;
    }
}