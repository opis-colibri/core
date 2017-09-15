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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\ItemCollector;
use Opis\Colibri\Serializable\Translations;

/**
 * Class TranslationCollector
 * @package Opis\Colibri\Containers
 * @method Translations data()
 */
class TranslationCollector extends ItemCollector
{
    protected $language;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new Translations());
    }

    /**
     * Add the sentences that will be translated from english to current used language
     *
     * @param   string $language Language
     * @param   array $sentences Translated sentences
     */
    public function translate($language, array $sentences)
    {
        if (empty($sentences)) {
            return;
        }

        $this->dataObject->translate($language, $sentences);
    }
}
