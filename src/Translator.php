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

namespace Opis\Colibri;

use Opis\Colibri\Serializable\Translations;
use Opis\Intl\{
    Locale,
    Translator\AbstractTranslator,
    Translator\Driver\Memory,
    Translator\IDriver,
    Translator\LanguageInfo
};
use Opis\Colibri\Serializable\ClassList;
use function Opis\Colibri\Functions\app;

class Translator extends AbstractTranslator
{
    /**
     * @inheritDoc
     */
    public function __construct(IDriver $driver = null, $default_language = null)
    {
        if ($driver === null) {
            $driver = new Memory([], []);
        }
        if ($default_language === null) {
            $default_language = Locale::SYSTEM_LOCALE;
        }
        parent::__construct($driver, $default_language);
    }

    /**
     * @inheritDoc
     */
    protected function loadSystemNS(string $ns)
    {
        /** @var Translations $tr */
        $tr = app()->getCollector()->collect('translations');
        return $tr->getTranslations($ns);
    }

    /**
     * @inheritDoc
     */
    protected function getFilter(string $name)
    {
        /** @var ClassList $filters */
        $filters = app()->getCollector()->collect('translationfilters');
        return $filters->get($name);
    }

    /**
     * @param string $key
     * @param array $params
     * @param int $count
     * @param string|LanguageInfo|null $language
     * @return string
     */
    public function __invoke(string $key, array $params = [], int $count = 1, $language = null)
    {
        return $this->translateKey($key, $params, $count, $language);
    }

}
