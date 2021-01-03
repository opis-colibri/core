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

namespace Opis\Colibri\I18n\Translator\Drivers;

use Opis\Colibri\I18n\Translator\Driver;

class Memory implements Driver
{
    protected array $data;
    protected array $languages;

    /**
     * Memory constructor.
     * @param array $languages
     * @param array $data
     */
    public function __construct(array $languages = [], array $data = [])
    {
        $this->languages = $languages;
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function listLanguages(): array
    {
        return array_keys($this->languages);
    }

    /**
     * @inheritDoc
     */
    public function loadLanguage(string $language): ?array
    {
        return $this->languages[$language] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function saveLanguage(string $language, array $settings = null): bool
    {
        if ($settings === null) {
            unset($this->data[$language]);

        } else {
            $this->data[$language] = $settings;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function listNS(string $language): array
    {
        if (!isset($this->data[$language])) {
            return [];
        }

        return array_keys($this->data[$language]);
    }

    /**
     * @inheritDoc
     */
    public function loadNS(string $language, string $ns): ?array
    {
        return $this->data[$language][$ns] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function saveNS(string $language, string $ns, array $keys = null): bool
    {
        if ($keys === null) {
            unset($this->data[$language][$ns]);
        } else {
            $this->data[$language][$ns] = $keys;
        }

        return true;
    }

}