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

namespace Opis\Colibri\Templates;

use Opis\Stream\Content;
use Opis\Stream\Wrapper\ContentStreamWrapper;
use function Opis\Colibri\Functions\app;

final class TemplateStream extends ContentStreamWrapper
{
    const PROTOCOL = 'template';

    private const REGEX = '`^' . self::PROTOCOL . '://(?<type>[^/]+)/(?<id>.*)\.(?<ext>.*)$`';

    /**
     * @inheritDoc
     */
    protected function content(string $path): ?Content
    {
        if (!preg_match(self::REGEX, $path, $m)) {
            return null;
        }

        $type = $m['type'];
        $id = $m['id'];
        $ext = $m['ext'];

        unset($m);

        /** @var TemplateStreamHandler $provider */
        $provider = app()->getCollector()->getTemplateStreamHandlers()->get($type);

        if ($provider === null) {
            return null;
        }

        return $provider->handle($id, $ext);
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
     * @return string
     */
    public static function url(string $type, string $id, string $extension): string
    {
        return self::PROTOCOL . "://{$type}/{$id}.{$extension}";
    }

    /**
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cached = [];
    }
}