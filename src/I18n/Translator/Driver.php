<?php
/* ===========================================================================
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

namespace Opis\Colibri\I18n\Translator;

interface Driver
{
    /**
     * An array of available language names
     * @return string[]
     */
    public function listLanguages(): array;

    /**
     * Get an array of language settings
     * @param string $language
     * @return array|null
     */
    public function loadLanguage(string $language): ?array;

    /**
     * Sets language settings
     * @param string $language
     * @param array|null $settings
     * @return bool
     */
    public function saveLanguage(string $language, ?array $settings = null): bool;

    /**
     * @param string $language
     * @return string[]
     */
    public function listNS(string $language): array;

    /**
     * Loads all keys for a specified namespace
     * @param string $language
     * @param string $ns
     * @return array|null
     */
    public function loadNS(string $language, string $ns): ?array;

    /**
     * @param string $language
     * @param string $ns
     * @param array|null $keys
     * @return bool
     */
    public function saveNS(string $language, string $ns, ?array $keys = null): bool;
}
