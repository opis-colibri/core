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

namespace Opis\Colibri\Collectors\Implementation;

use Opis\Colibri\Application;
use Opis\Colibri\Serializable\Translations;
use Opis\Colibri\Collectors\AbstractCollector;
use Opis\Colibri\Collectors\TranslationCollectorInterface;

class TranslationCollector extends AbstractCollector implements TranslationCollectorInterface
{
    protected $language;

    /**
     * Constructor
     * 
     * @param   Opis\Colibri\Application    $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app, new Translations($app));
    }

    /**
     * Add the sentences that will be translated from english to current used language
     * 
     * @param   string  $language   Language
     * @param   array   $sentences  Trnslated sentences
     */
    public function translate($language, array $sentences)
    {
        if (empty($sentences)) {
            return;
        }

        $this->dataObject->translate($language, $sentences);
    }
}
