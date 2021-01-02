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

namespace Opis\Colibri\View;

use Throwable;
use Opis\Colibri\I18n\Translator\LanguageInfo;
use function Opis\Colibri\{
    view, render, asset, getURI, generateCSRFToken, t
};

class PHPEngine implements Engine
{
    public function view(string $name, array $arguments = []): View
    {
        return view($name, $arguments);
    }

    public function render(string|Viewable $viewable): string
    {
        return render($viewable);
    }

    public function asset(string $module, string $path): string
    {
        return asset($module, $path);
    }

    public function getURI(string $path): string
    {
        return getURI($path);
    }

    /**
     * @param string $key
     * @param array|null $params
     * @param int $count
     * @param string|LanguageInfo|null $language
     * @return string
     */
    public function t(string $key, array $params = null, int $count = 1, $language = null): string
    {
        return t($key, $params, $count, $language);
    }

    /**
     * @return string
     */
    public function csrfToken(): string
    {
        return generateCSRFToken();
    }

    /**
     * Build
     *
     * @param   string $path
     * @param   array $vars
     *
     * @return  string
     * @throws Throwable
     */
    public function build(string $path, array $vars = []): string
    {
        ${'#path'} = $path;
        ${'#vars'} = $vars;

        unset($path, $vars);

        ob_start();

        extract(${'#vars'});

        try {
            /** @noinspection PhpIncludeInspection */
            include ${'#path'};
        } catch (Throwable $e) {
            ob_get_clean();
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * @inheritDoc
     */
    public function canHandle(string $path): bool
    {
        return true;
    }
}
