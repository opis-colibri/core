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

class DateTimeFormatter
{
    protected ?IntlDateFormatter $formatter;

    public function __construct(?IntlDateFormatter $formatter = null)
    {
        $this->formatter = $formatter;
    }

    public function formatter(): ?IntlDateFormatter
    {
        return $this->formatter;
    }

    public function calendar(): ?IntlCalendar
    {
        if ($this->formatter === null) {
            return null;
        }
        return $this->formatter->getCalendarObject();
    }

    public function timezone(): ?IntlTimeZone
    {
        if ($this->formatter === null) {
            return null;
        }
        return $this->formatter->getTimeZone() ?: null;
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param int|string|null $date_format
     * @param int|string|null $time_format
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string|null
     */
    public function format(
        int|string|DateTimeInterface|IntlCalendar|null $value = null,
        int|string|null $date_format = null,
        int|string|null $time_format = null,
        string|DateTimeZone|IntlTimeZone|null $timezone = null,
    ): ?string {
        $value = $this->parseValue($value, $timezone);

        if ($this->formatter === null) {
            if ($date_format !== null) {
                $format = 'F j, Y';
                if ($time_format !== null) {
                    $format .= ', g:i A';
                }
            } elseif ($time_format !== null) {
                $format = 'g:i A';
            } else {
                $format = DateTimeInterface::ATOM;
            }

            return $value->format($format);
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
    public function formatPattern(
        int|string|DateTimeInterface|IntlCalendar|null $value,
        string $pattern,
        string|DateTimeZone|IntlTimeZone|null $timezone = null,
    ): ?string {
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
    public function formatDate(
        int|string|DateTimeInterface|IntlCalendar|null $value = null,
        int|string|null $format = null,
        string|DateTimeZone|IntlTimeZone|null $timezone = null,
    ): ?string {
        if (is_string($format) && $this->formatter) {
            if (-100 !== $f = static::getFormat($format, -100)) {
                return $this->formatPattern($value, $format, $timezone);
            }
        } else {
            $format = null;
        }

        return $this->format($value, $format, IntlDateFormatter::NONE, $timezone);
    }

    /**
     * @param int|string|DateTimeInterface|IntlCalendar|null $value
     * @param int|string|null $format
     * @param string|DateTimeZone|IntlTimeZone|null $timezone
     * @return string|null
     */
    public function formatTime(
        int|string|DateTimeInterface|IntlCalendar|null $value = null,
        int|string|null $format = null,
        string|DateTimeZone|IntlTimeZone|null $timezone = null,
    ): ?string {
        if ($this->formatter && is_string($format)) {
            if (-100 !== $f = static::getFormat($format, -100)) {
                return $this->formatPattern($value, $format, $timezone);
            }
        } else {
            $format = null;
        }

        return $this->format($value, IntlDateFormatter::NONE, $format, $timezone);
    }

    protected function parseValue(
        int|string|DateTimeInterface|IntlCalendar|null $value,
        string|DateTimeZone|IntlTimeZone|null $timezone = null,
    ): DateTime|IntlCalendar {
        if ($timezone === null || $timezone === 'default') {
            $timezone = $this->timezone() ?? date_default_timezone_get();
        }

        if ($this->formatter === null) {
            if (is_int($value)) {
                $value = (new DateTime())->setTimestamp($value);
            } elseif (is_string($value)) {
                $value = new DateTime($value);
            } elseif (!($value instanceof DateTimeInterface)) {
                $value = new DateTime();
            }

            if ($timezone) {
                if (is_string($timezone)) {
                    $value = $value->setTimezone(new DateTimeZone($timezone));
                } elseif ($timezone instanceof DateTimeZone) {
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
     * @param int|string|null $format
     * @param int $default
     * @return int
     */
    protected static function getFormat(int|string|null $format, int $default = IntlDateFormatter::FULL): int
    {
        if ($format === null) {
            return $default;
        }

        if (is_int($format)) {
            return $format;
        }

        if (is_string($format)) {
            return match (strtolower($format)) {
                'none' => IntlDateFormatter::NONE,
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
                default => $default,
            };
        }

        return $default;
    }

    public static function create(
        string $locale,
        ?string $date = null,
        ?string $time = null,
        ?string $pattern = null,
        int|string|IntlCalendar|null $calendar = null,
        string|DateTimeZone|IntlTimeZone|null $timezone = null
    ): self {
        $date = static::getFormat($date, IntlDateFormatter::SHORT);
        $time = static::getFormat($time, IntlDateFormatter::SHORT);

        if ($calendar === null || $calendar === '') {
            $calendar  = IntlDateFormatter::GREGORIAN;
        } elseif (is_string($calendar)) {
            $calendar = strtoupper($calendar);
            $locale .= '@calendar=' . $calendar;
            $calendar = $calendar === 'GREGORIAN' ? IntlDateFormatter::GREGORIAN : IntlDateFormatter::TRADITIONAL;
        } elseif (!is_int($calendar) && !($calendar instanceof IntlCalendar)) {
            $calendar = null;
        }

        if ($timezone === null || $timezone == 'default') {
            $timezone = date_default_timezone_get();
        }

        return new static(IntlDateFormatter::create($locale, $date, $time, $timezone, $calendar, $pattern));
    }

    public static function fromArray(array $datetime, ?string $locale = null): self
    {
        return static::create(
            $datetime['locale'] ?? $locale ?? Locale::SYSTEM_LOCALE,
            $datetime['date'] ?? null,
            $datetime['time'] ?? null,
            $datetime['pattern'] ?? null,
            $datetime['calendar'] ?? null,
            $datetime['timezone'] ?? null
        );
    }
}