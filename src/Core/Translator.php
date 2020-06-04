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

namespace Opis\Colibri\Core;

use Opis\Colibri\Collectors\{TranslationCollector, TranslationFilterCollector};
use Opis\I18n\{
    Locale,
    Translator\BaseTranslator,
    Translator\Drivers\Memory,
    Translator\Driver
};
use function Opis\Colibri\collect;

class Translator extends BaseTranslator
{
    /**
     * @inheritDoc
     */
    public function __construct(?Driver $driver = null, ?string $default_language = null)
    {
        if ($driver === null) {
            $driver = new Memory([], []);
        }
        parent::__construct($driver, $default_language ?? Locale::SYSTEM_LOCALE);
    }

    /**
     * @inheritDoc
     */
    protected function loadSystemNS(string $ns)
    {
        return collect(TranslationCollector::class)->getTranslations($ns);
    }

    /**
     * @inheritDoc
     */
    protected function getFilter(string $name)
    {
        return collect(TranslationFilterCollector::class)->get($name);
    }
}
