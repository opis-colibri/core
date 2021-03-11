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

namespace Opis\Colibri;

use Opis\Colibri\Attributes\Priority;
use Opis\Colibri\HTML\Template as HtmlTemplate;
use Opis\Colibri\Http\Responses\{FileStream, HtmlResponse};
use Opis\Colibri\Templates\{CallbackTemplateHandler, TemplateStream};
use Opis\Colibri\Collectors\{RouteCollector, ViewCollector, TemplateStreamHandlerCollector};

/**
 * @internal
 */
final class InternalCollector extends Collector
{
    const RESOURCES_DIR = __DIR__ . '/../resources';

    public function templateHandlers(TemplateStreamHandlerCollector $collector): void
    {
        $collector->register('callback', CallbackTemplateHandler::class);
    }

    public function views(ViewCollector $view): void
    {
        $view->handle('welcome', self::class . '::welcomeView');

        $view
            ->handle('error.{error}', self::class . '::httpErrorView')
            ->where('error', '401|403|404|405|500|503');

        $view
            ->handle('html.{type}', self::class . '::htmlTemplateView')
            ->where('type', 'document|link|style|script|collection|meta|attributes');
    }

    #[Priority(-100)]
    public function routes(RouteCollector $route): void
    {
        $route
            ->group(static function (RouteCollector $route) {
                $route('/', self::class . '::welcomePage');
                $route('/opis-colibri/assets/{file}', self::class . '::assetFile')
                    ->whereIn('file', ['logo.png', 'background.png', 'favicon.png']);
            })
            ->filter(self::class . '::isDevelopment');
    }

    /** -- Filters -- */

    public static function isDevelopment(): bool
    {
        return !env('APP_PRODUCTION', false);
    }

    /** -- Controllers -- **/

    public static function welcomePage(): HtmlResponse
    {
        return new HtmlResponse(view('welcome'));
    }

    public static function assetFile(string $file): FileStream
    {
        return new FileStream(self::RESOURCES_DIR . "/assets/{$file}");
    }

    /** -- Views -- **/

    public static function welcomeView(): string
    {
        return self::RESOURCES_DIR . '/templates/welcome.php';
    }

    public static function httpErrorView(): string
    {
        return self::RESOURCES_DIR . '/templates/http-error.php';
    }

    public static function htmlTemplateView(string $type): string
    {
        return TemplateStream::url('callback', HtmlTemplate::class . '::' . $type, 'php');
    }
}