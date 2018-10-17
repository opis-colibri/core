<?php
/* ============================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Rendering;

use Opis\Stream\IContent;
use Opis\Stream\Wrapper\AbstractContentStreamWrapper;
use function Opis\Colibri\Functions\{collect, uuid4};

final class TemplateStream extends AbstractContentStreamWrapper
{
    const PROTOCOL = 'template';

    private const REGEX = '`^' . self::PROTOCOL . '://(?<type>[^/]+)/(?<id>.*)\.(?<ext>.*)(\?[a-fA-F0-9]{32})?$`';

    /**
     * @inheritDoc
     */
    protected function content(string $path): ?IContent
    {
        if (!preg_match(self::REGEX, $path, $m)) {
            return null;
        }

        $type = $m['type'];
        $id = $m['id'];
        $ext = $m['ext'];

        unset($m);

        /** @var ITemplateStreamHandler $provider */
        $provider = collect('template-stream-handlers')->get($type);

        if ($provider === null) {
            return null;
        }

        return $provider->handle($id, $ext);
    }

    /**
     * @inheritDoc
     */
    protected function cacheKey(string $path): string
    {
        return md5(self::formatPath($path));
    }

    /**
     * @inheritDoc
     */
    public static function protocol(): string
    {
        return self::PROTOCOL;
    }

    /**
     * @param string $type
     * @param string $id
     * @param string $extension
     * @param bool $use_id
     * @return string
     */
    public static function url(string $type, string $id, string $extension, bool $use_id = false): string
    {
        $url = self::PROTOCOL . "://{$type}/{$id}.{$extension}";
        if ($use_id) {
            $url .= '?' . uuid4('');
        }
        return $url;
    }

    /**
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cached = [];
    }

    /**
     * @param string $path
     * @return string
     */
    private static function formatPath(string $path): string
    {
        return strpos($path, '?') === false ? $path : strstr($path, '?', true);
    }
}