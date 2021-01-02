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

namespace Opis\Colibri\I18n;

use IntlDateFormatter,
    IntlCalendar,
    IntlTimeZone,
    Locale as IntlLocale,
    DateTime,
    DateTimeInterface,
    DateTimeZone;

class DefaultDateTimeFormatter implements DateTimeFormatter
{

    protected ?IntlDateFormatter $formatter;

    /**
     * DateTimeFormatter constructor.
     * @param IntlDateFormatter|null $formatter
     */
    public function __construct(?IntlDateFormatter $formatter = null)
    {
        $this->formatter = $formatter;
    }

    /**
     * @return IntlDateFormatter|null
     */
    public function formatter(): ?IntlDateFormatter
    {
        return $this->formatter;
    }

    /**
     * @return IntlCalendar|null
     */
    public function calendar(): ?IntlCalendar
    {
        if ($this->formatter === null) {
            return null;
        }
        return $this->formatter->getCalendarObject();
    }

    /**
     * @return IntlTimeZone|null
     */
    public function timezone(): ?IntlTimeZone
    {
        if ($this->formatter === null) {
            return null;
        }
        return $this->formatter->getTimeZone() ?: null;
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return DateTime|IntlCalendar
     */
    protected function parseValue($value, $timezone = null)
    {
        if ($this->formatter === null) {
            if (is_int($value)) {
                $value = (new DateTime())->setTimestamp($value);
            }
            elseif (is_string($value)) {
                $value = new DateTime($value);
            }
            elseif (!($value instanceof DateTimeInterface)) {
                $value = new DateTime();
            }

            if ($timezone) {
                if (is_string($timezone)) {
                    $value = $value->setTimezone(new DateTimeZone($timezone));
                }
                elseif ($timezone instanceof DateTimeZone) {
                    $value = $value->setTimezone($timezone);
                }
            }

            return $value;
        }

        if ($value instanceof IntlCalendar) {
            if ($timezone !== null) {
                $value = clone $value;
                $value->setTimeZone($timezone);
            }

            return $value;
        }


        $calendar = clone $this->formatter->getCalendarObject();

        if ($value === null) {
            $value = new DateTime();
        }
        if (is_int($value)) {
            $calendar->setTime($value * 1000);
        } elseif (is_string($value)) {
            $value = new DateTime($value);
        }

        if ($value instanceof DateTimeInterface) {
            if ($timezone === null) {
                $timezone = $value->getTimezone();
            }
            $calendar->setTime($value->getTimestamp() * 1000);
        }

        if ($timezone !== null) {
            $calendar->setTimeZone($timezone);
        }

        return $calendar;
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param int|string|null $date_format
     * @param int|string|null $time_format
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string|null
     */
    public function format($value = null, $date_format = null, $time_format = null, $timezone = null): ?string
    {
        if ($timezone === true || $timezone === 'default') {
            $timezone = date_default_timezone_get();
        }

        $value = $this->parseValue($value, $timezone);
        if ($this->formatter === null) {
            if ($date_format !== false) {
                $format = 'F j, Y';
                if ($time_format !== false) {
                    $format .= ', g:i A';
                }
            }
            elseif ($time_format !== false) {
                $format = 'g:i A';
            }
            else {
                $format = DateTimeInterface::ATOM;
            }

            return $value->format($format);
        }

        if ($date_format === null && $time_format === null && $timezone == null) {
            return $this->formatter->format($value);
        }

        $format = [
            static::getFormat($date_format, $this->formatter->getDateType()),
            static::getFormat($time_format, $this->formatter->getTimeType()),
        ];

        return IntlDateFormatter::formatObject($value, $format,
            $this->formatter->getLocale(IntlLocale::VALID_LOCALE)) ?: null;
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param string $pattern
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string|null
     */
    public function formatPattern($value, string $pattern, $timezone = null): ?string
    {
        if ($timezone === true || $timezone === 'default') {
            $timezone = date_default_timezone_get();
        }
        $value = $this->parseValue($value, $timezone);

        if ($this->formatter === null) {
            return $value->format(DateTimeInterface::ATOM);
        }

        return $this->formatter->formatObject($value, $pattern,
            $this->formatter->getLocale(IntlLocale::VALID_LOCALE)) ?: null;
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param int|string|null $format
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string|null
     */
    public function formatDate($value = null, $format = null, $timezone = null): ?string
    {
        if (is_string($format) && $this->formatter) {
            if (-100 !== $f = static::getFormat($format, -100)) {
                return $this->formatPattern($value, $format, $timezone);
            }
        }
        else {
            $format = null;
        }

        return $this->format($value, $format, false, $timezone);
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param int|string|null $format
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string
     */
    public function formatTime($value = null, $format = null, $timezone = null): ?string
    {
        if ($this->formatter && is_string($format)) {
            if (-100 !== $f = static::getFormat($format, -100)) {
                return $this->formatPattern($value, $format, $timezone);
            }
        }
        else {
            $format = null;
        }

        return $this->format($value, false, $format, $timezone);
    }

    /**
     * @param int|string|null $format
     * @param int $default
     * @return int
     */
    protected static function getFormat($format, int $default = 0): int
    {
        if ($format === false) {
            return IntlDateFormatter::NONE;
        } elseif ($format === null) {
            return $default;
        } elseif (is_int($format)) {
            return $format;
        } elseif (is_string($format)) {
            switch ($format) {
                case 'none':
                    return IntlDateFormatter::NONE;
                case 'short':
                    return IntlDateFormatter::SHORT;
                case 'medium':
                    return IntlDateFormatter::MEDIUM;
                case 'long':
                    return IntlDateFormatter::LONG;
                case 'full':
                    return IntlDateFormatter::FULL;
            }
        }

        return $default;
    }

    /**
     * Create a new formatter
     * @param string $locale
     * @param string|null $date
     * @param string|null $time
     * @param string|null $pattern
     * @param string|null|IntlCalendar $calendar
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return DefaultDateTimeFormatter
     */
    public static function create(string $locale, ?string $date = null, ?string $time = null,
                                  ?string $pattern = null, $calendar = null, $timezone = null): self
    {
        if (!IntlChecker::extensionExists()) {
            return new static();
        }

        $date = static::getFormat($date, IntlDateFormatter::SHORT);
        $time = static::getFormat($time, IntlDateFormatter::SHORT);

        if (is_null($calendar) || $calendar === '' || ($calendar = strtoupper($calendar)) === 'GREGORIAN') {
            $calendar = IntlDateFormatter::GREGORIAN;
        } elseif (is_string($calendar)) {
            $locale .= '@calendar=' . $calendar;
            $calendar = IntlDateFormatter::TRADITIONAL;
        } elseif (!($calendar instanceof IntlCalendar)) {
            $calendar = null;
        }

        if ($timezone === true || $timezone == 'default') {
            $timezone = date_default_timezone_get();
        }

        return new static(IntlDateFormatter::create($locale, $date, $time, $timezone, $calendar, $pattern));
    }

    /**
     * @param array $datetime
     * @param string|null $locale
     * @return DefaultDateTimeFormatter
     */
    public static function fromArray(array $datetime, string $locale = null): self
    {
        if (!IntlChecker::extensionExists()) {
            return new static();
        }

        $locale = $datetime['locale'] ?? $locale ?? Locale::SYSTEM_LOCALE;

        return static::create(
            $locale,
            $datetime['date'] ?? null,
            $datetime['time'] ?? null,
            $datetime['pattern'] ?? null,
            $datetime['calendar'] ?? null,
            $datetime['timezone'] ?? null
        );
    }
}