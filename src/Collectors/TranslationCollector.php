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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Serializable\Translations;

/**
 * @property Translations $data
 */
class TranslationCollector extends BaseCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new Translations());
    }

    /**
     * @param string $ns
     * @param string $key
     * @param string|null $comment
     * @param string|null $translator_comment
     * @return TranslationCollector
     */
    public function addComment(string $ns, string $key, string $comment = null, string $translator_comment = null): self
    {
        $this->data->addComment($ns, $key, $comment, $translator_comment);
        return $this;
    }

    /**
     * @param string $ns
     * @param array $data
     * @return TranslationCollector
     */
    public function addTranslations(string $ns, array $data): self
    {
        $this->data->addTranslations($ns, $data);
        return $this;
    }
}
