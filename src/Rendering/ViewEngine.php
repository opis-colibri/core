<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

use Opis\Colibri\View;
use Opis\Intl\Translator\LanguageInfo;
use Opis\View\PHPEngine;
use function Opis\Colibri\Functions\{
    view, render, asset, getURL, generateCSRFToken, r, t
};

class ViewEngine extends PHPEngine
{
    /**
     * @param string $name
     * @param array $arguments
     * @return \Opis\Colibri\View
     */
    public function view(string $name, array $arguments = []): View
    {
        return view($name, $arguments);
    }

    /**
     * @param $view
     * @return string
     */
    public function render($view): string
    {
        return render($view);
    }

    /**
     * @param string $module
     * @param string $path
     * @param bool $full
     * @return string
     */
    public function asset(string $module, string $path, bool $full = false): string
    {
        return asset($module, $path, $full);
    }

    /**
     * @param string $path
     * @param bool $full
     * @return string
     */
    public function getURL(string $path, bool $full = false): string
    {
        return getURL($path, $full);
    }

    /**
     * @param string $text
     * @param array $placeholders
     * @return string
     */
    public function r(string $text, array $placeholders): string
    {
        return r($text, $placeholders);
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
}