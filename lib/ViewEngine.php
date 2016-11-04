<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Opis\View\PHPEngine;
use function Opis\Colibri\Helpers\{view as makeView, render, asset, getURL, generateCSRFToken, r, v, t};

class ViewEngine extends PHPEngine
{
    /**
     * @param string $name
     * @param array $arguments
     * @return \Opis\Colibri\View
     */
    public function view(string $name, array $arguments = []): \Opis\Colibri\View
    {
        return makeView($name, $arguments);
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
     * @param string $name
     * @param null $default
     * @return null
     */
    public function v(string $name, $default = null)
    {
        return v($name, $default);
    }

    public function r(string $text, array $placeholders): string
    {
        return r($text, $placeholders);
    }

    /**
     * @param string $sentence
     * @param array $placeholders
     * @param string|null $lang
     * @return string
     */
    public function t(string $sentence, array $placeholders = [], string $lang = null): string
    {
        return t($sentence, $placeholders, $lang);
    }

    /**
     * @return string
     */
    public function csrfToken(): string
    {
        return generateCSRFToken();
    }
}