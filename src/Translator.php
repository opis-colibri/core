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

namespace Opis\Colibri;

use Opis\Colibri\I18n\Translator\Driver;
use Opis\Colibri\I18n\Translator\Drivers\Memory;
use Opis\Colibri\I18n\Translator as BaseTranslator;
use Opis\Colibri\Collectors\{TranslationCollector, TranslationFilterCollector};

class Translator extends BaseTranslator
{
    public function __construct(?Driver $driver = null, ?string $default_language = null)
    {
        parent::__construct($driver ?? new Memory(), $default_language);
    }

    protected function loadSystemNS(string $ns): ?array
    {
        return collect(TranslationCollector::class)->getTranslations($ns);
    }

    protected function getFilter(string $name): ?callable
    {
        return collect(TranslationFilterCollector::class)->get($name);
    }
}
