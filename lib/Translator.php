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

namespace Opis\Colibri;

use Opis\Utils\Placeholder;

class Translator
{
    /** @var    string */
    protected $language;

    /** @var    \Opis\Utils\Placeholder */
    protected $placeholder;

    /** @var    array */
    protected $translations = array();

    /** @var    \Opis\Colibri\Application */
    protected $app;

    /**
     * Constructor
     * 
     * @param   \Opis\Colibri\Application       $app
     * @param   string                          $language       (optional)
     * @param   \Opis\Utils\Placeholder|null    $placeholder    (optional)
     */
    public function __construct(Application $app, $language = 'en', Placeholder $placeholder = null)
    {
        if ($placeholder === null) {
            $placeholder = new Placeholder();
        }
        $this->app = $app;
        $this->language = $language;
        $this->placeholder = $placeholder;
    }

    /**
     * Get the current language
     * 
     * @return  string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set the current language
     * 
     * @param   string  $language
     * 
     * @return  $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Translate a sentence
     * 
     * @param   string      $sentence
     * @param   array       $placeholders   (optional)
     * @param   string|null $lang           (optional)
     * 
     * @return  string
     */
    public function translate($sentence, $placeholders = array(), $lang = null)
    {
        if ($lang === null) {
            $lang = $this->language;
        }

        if (!isset($this->translations[$lang])) {
            $this->translations[$lang] = $this->app->translations()->read($lang, array());
        }

        $translation = &$this->translations[$lang];

        if (isset($translation[$sentence])) {
            $sentence = $translation[$sentence];
        }

        return $this->placeholder->replace($sentence, $placeholders);
    }

    /**
     * Translate a sentence
     * 
     * @param   string      $sentence
     * @param   array       $placeholders   (optional)
     * @param   string|null $lang           (optional)
     * 
     * @return  string
     */
    public function __invoke($sentence, $placeholders = array(), $lang = null)
    {
        return $this->translate($sentence, $placeholders, $lang);
    }
}
