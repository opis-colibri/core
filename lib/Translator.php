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

use function Opis\Colibri\Functions\{app};
use Opis\Validation\Placeholder;

class Translator
{
    /** @var    string */
    protected $language;

    /** @var    Placeholder */
    protected $placeholder;

    /** @var    array */
    protected $translations = array();

    /**
     * Constructor
     *
     * @param   string $language (optional)
     * @param   Placeholder|null $placeholder (optional)
     */
    public function __construct(string $language = 'en', Placeholder $placeholder = null)
    {
        if ($placeholder === null) {
            $placeholder = new Placeholder();
        }

        $this->language = $language;
        $this->placeholder = $placeholder;
    }

    /**
     * Get the current language
     *
     * @return  string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Set the current language
     *
     * @param   string $language
     * @return $this|Translator
     */
    public function setLanguage(string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Translate a sentence
     *
     * @param   string $sentence
     * @param   array $placeholders (optional)
     * @param   string|null $lang (optional)
     *
     * @return  string
     */
    public function __invoke(string $sentence, array $placeholders = array(), string $lang = null): string
    {
        return $this->translate($sentence, $placeholders, $lang);
    }

    /**
     * Translate a sentence
     *
     * @param   string $sentence
     * @param   array $placeholders (optional)
     * @param   string|null $lang (optional)
     *
     * @return  string
     */
    public function translate(string $sentence, array $placeholders = [], string $lang = null): string
    {
        if ($lang === null) {
            $lang = $this->language;
        }

        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = app()->getTranslations()->read($lang, []);
        }

        $translation = &$this->translations[$lang];

        if (isset($translation[$sentence])) {
            $sentence = $translation[$sentence];
        }

        return $this->placeholder->replace($sentence, $placeholders);
    }
}
