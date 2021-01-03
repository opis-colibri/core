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

use Opis\Colibri\I18n\{
    Locale, Plural, DateTimeFormatter, NumberFormatter
};

class LanguageInfo
{
    protected Locale $locale;
    protected Plural $plural;
    protected NumberFormatter $number;
    protected DateTimeFormatter $datetime;
    protected array $fallback;

    /**
     * LanguageInfo constructor.
     * @param Locale $locale
     * @param Plural $plural
     * @param DateTimeFormatter $date
     * @param NumberFormatter $number
     * @param string[] $fallback
     */
    public function __construct(Locale $locale, Plural $plural, DateTimeFormatter $date, NumberFormatter $number, array $fallback = [])
    {
        $this->locale = $locale;
        $this->plural = $plural;
        $this->datetime = $date;
        $this->number = $number;
        $this->fallback = $fallback;
    }

    /**
     * @return Locale
     */
    public function locale(): Locale
    {
        return $this->locale;
    }

    /**
     * @return Plural
     */
    public function plural(): Plural
    {
        return $this->plural;
    }

    /**
     * @return DateTimeFormatter
     */
    public function datetime(): DateTimeFormatter
    {
        return $this->datetime;
    }

    /**
     * @return NumberFormatter
     */
    public function number(): NumberFormatter
    {
        return $this->number;
    }

    /**
     * @return string[]
     */
    public function fallback(): array
    {
        return $this->fallback;
    }

    /**
     * @param array $info
     * @return LanguageInfo
     */
    public static function fromArray(array $info): self
    {
        return static::create(
            $info['locale'] ?? null,
            $info['plural'] ?? null,
            $info['datetime'] ?? null,
            $info['number'] ?? null,
            $info['fallback'] ?? []
        );
    }

    /**
     * @param Locale|array|string|null $locale
     * @param Plural|array|null $plural
     * @param DateTimeFormatter|array|null $datetime
     * @param NumberFormatter|array|null $number
     * @param string[] $fallback
     * @return LanguageInfo
     */
    public static function create($locale = null, $plural = null, $datetime = null, $number = null, array $fallback = []): self
    {
        $locale = static::parseLocale($locale);
        $plural = static::parsePlural($plural, $locale);
        $datetime = static::parseDateTime($datetime, $locale);
        $number = static::parseNumber($number, $locale);

        return new static($locale, $plural, $datetime, $number, $fallback);
    }

    /**
     * @param Locale|array|string|null $locale
     * @return Locale
     */
    public static function parseLocale($locale): Locale
    {
        if ($locale instanceof Locale) {
            return $locale;
        }
        if (is_array($locale)) {
            return Locale::fromArray($locale);
        }

        if (!is_string($locale)) {
            $locale = Locale::SYSTEM_LOCALE;
        }

        return Locale::create($locale);
    }

    /**
     * @param Plural|array|string|null $plural
     * @param Locale|string|array|null $locale
     * @return Plural
     */
    public static function parsePlural($plural, $locale = null): Plural
    {
        if ($plural instanceof Plural) {
            return $plural;
        }

        if (is_array($plural)) {
            return Plural::fromArray($plural);
        }

        $locale = static::parseLocale(is_string($plural) ? $plural : $locale);

        return Plural::create($locale->id());
    }

    /**
     * @param DateTimeFormatter|array|null $datetime
     * @param Locale|string|array|null $locale
     * @return DateTimeFormatter
     */
    public static function parseDateTime($datetime, $locale = null): DateTimeFormatter
    {
        if ($datetime instanceof DateTimeFormatter) {
            return $datetime;
        }

        $locale = static::parseLocale(is_string($datetime) ? $datetime : $locale);

        if (is_array($datetime)) {
            return DateTimeFormatter::fromArray($datetime, $locale->id());
        }

        return DateTimeFormatter::create($locale->id());
    }

    /**
     * @param NumberFormatter|array|null $number
     * @param Locale|string|array|null $locale
     * @return NumberFormatter
     */
    public static function parseNumber($number, $locale = null): NumberFormatter
    {
        if ($number instanceof NumberFormatter) {
            return $number;
        }

        $locale = static::parseLocale(is_string($number) ? $number : $locale);

        if (is_array($number)) {
            return NumberFormatter::fromArray($number, $locale->id());
        }

        return NumberFormatter::create($locale->id());
    }
}