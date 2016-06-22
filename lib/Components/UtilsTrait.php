<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Components;

use Opis\Colibri\Serializable\ControllerCallback;

trait UtilsTrait
{
    use ApplicationTrait;

    /**
     * Get a variable's value
     *
     * @param string $name
     * @param null $default
     * @return null|mixed
     */
    protected function v(string $name, $default = null)
    {
        $var = $this->getApp()->getVariables();
        return array_key_exists($name, $var) ? $var[$name] : $default;
    }

    /**
     * Replace
     *
     * @param string $text
     * @param array $placeholders
     * @return string
     */
    protected function r(string $text, array $placeholders): string
    {
        return $this->getApp()->getPlaceholder()->replace($text, $placeholders);
    }

    /**
     * Translate
     *
     * @param string $sentence
     * @param array $placeholders
     * @param string|null $lang
     * @return string
     */
    protected function t(string $sentence, array $placeholders = [], string $lang = null): string
    {
        return $this->getApp()->getTranslator()->translate($sentence, $placeholders, $lang);
    }

    /**
     * @param string $module
     * @param string $path
     * @param bool $full
     * @return string
     */
    protected function getAsset(string $module, string $path, bool $full = false): string
    {
        $assetsPath = $this->getApp()->getAppInfo()->assetsPath();
        return $this->getURL($assetsPath . '/' . $module . '/' . ltrim($path), $full);
    }

    /**
     * @param string $path
     * @param bool $full
     * @return string
     */
    protected function getURL(string $path, bool $full = false): string
    {
        $req = $this->getApp()->getHttpRequest();
        return $full ? $req->uriForPath($path) : $req->baseUrl() . '/' . ltrim($path, '/');
    }

    /**
     * @param string $class
     * @param string $method
     * @param bool $static
     * @return ControllerCallback
     */
    protected function controller(string $class, string $method, bool $static = false): ControllerCallback
    {
        return new ControllerCallback($class, $method, $static);
    }

}