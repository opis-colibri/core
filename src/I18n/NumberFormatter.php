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

use NumberFormatter as IntlNumberFormatter;

class NumberFormatter
{
    protected ?IntlNumberFormatter $decimal = null;
    protected ?IntlNumberFormatter $currency = null;
    protected ?IntlNumberFormatter $percent = null;

    public function __construct(
        ?IntlNumberFormatter $decimal = null,
        ?IntlNumberFormatter $currency = null,
        ?IntlNumberFormatter $percent = null,
    )
    {
        $this->decimal = $decimal;
        $this->currency = $currency;
        $this->percent = $percent;
    }

    /**
     * @return IntlNumberFormatter|null
     */
    public function decimal(): ?IntlNumberFormatter
    {
        return $this->decimal;
    }

    /**
     * @return IntlNumberFormatter|null
     */
    public function currency(): ?IntlNumberFormatter
    {
        return $this->currency;
    }

    /**
     * @return IntlNumberFormatter|null
     */
    public function percent(): ?IntlNumberFormatter
    {
        return $this->percent;
    }

    /**
     * @inheritdoc
     */
    public function formatDecimal(int|float|string $value): string
    {
        if ($this->decimal === null) {
            $value = round($value, 3);
            return number_format($value, $value == (int) $value ? 0 : 3);
        }
        return $this->decimal->format($value);
    }

    /**
     * @inheritdoc
     */
    public function formatPercent(int|float|string $value): string
    {
        if ($this->percent === null) {
            $value *= 100;
            return number_format($value, $value == (int) $value ? 0 : 2) . '%';
        }
        return $this->percent->format($value);
    }

    /**
     * @inheritdoc
     */
    public function formatCurrency(int|float|string $value, ?string $currency = null): string
    {
        if ($this->currency === null) {
            $map = [
                'USD' => '$',
                'GBP' => '£',
                'EUR' => '€'
            ];
            if ($currency === null || $currency === '') {
                $currency = 'USD';
            }
            else {
                $currency = strtoupper(trim($currency));
            }
            if (isset($map[$currency])) {
                return $map[$currency] . number_format($value, 2);
            }

            return number_format($value, 2) . $currency;
        }

        if ($currency === null) {
            $currency = $this->currency->getTextAttribute(IntlNumberFormatter::CURRENCY_CODE);
        }

        return $this->currency->formatCurrency($value, $currency);
    }

    public static function create(
        ?string $locale = null,
        string|array|null $number = null,
        string|array|null $currency = null,
        string|array|null $percent = null,
    ): self
    {
        if (!$locale) {
            $locale = Locale::SYSTEM_LOCALE;
        }

        return new static(
            static::createDecimal($locale, $number),
            static::createCurrency($locale, $currency),
            static::createPercent($locale, $percent)
        );
    }

    public static function fromArray(array $number, ?string $locale = null): self
    {
        $locale = $number['locale'] ?? $locale;

        return static::create(
            $locale,
            $number['decimal'] ?? null,
            $number['currency'] ?? null,
            $number['percent'] ?? null
        );
    }

    /**
     * @param IntlNumberFormatter $number
     * @param array $settings
     * @param array|null $map
     * @return IntlNumberFormatter
     */
    protected static function applyNumberSettings(IntlNumberFormatter $number, array $settings, ?array $map = null): IntlNumberFormatter
    {
        if ($map === null) {
            $map = static::getSettingsMap();
        }

        foreach ($settings as $key => $value) {
            $key = strtoupper($key);
            if (isset($map['NUMBER_ATTRS'][$key])) {
                $key = $map['NUMBER_ATTRS'][$key];
                if (!is_int($value)) {
                    if (!is_array($key)) {
                        continue;
                    }
                    $value = strtoupper($value);
                    if (!isset($key[1][$value])) {
                        continue;
                    }
                    $value = $key[1][$value];
                    $key = $key[0];
                }
                $number->setAttribute($key, $value);
            } elseif (isset($map['NUMBER_TEXT_ATTRS'][$key])) {
                $key = $map['NUMBER_TEXT_ATTRS'][$key];
                if (!is_string($value)) {
                    $value .= '';
                }
                $number->setTextAttribute($key, $value);
            } elseif (isset($map['NUMBER_SYMBOLS'][$key])) {
                $key = $map['NUMBER_SYMBOLS'][$key];
                if (!is_string($value)) {
                    $value .= '';
                }
                $number->setSymbol($key, $value);
            }
        }

        return $number;
    }

    /**
     * @return array
     */
    protected static function getSettingsMap(): array
    {
        return [
            'NUMBER_ATTRS' => [
                'PARSE_INT_ONLY'          => IntlNumberFormatter::PARSE_INT_ONLY,
                'GROUPING_USED'           => IntlNumberFormatter::GROUPING_USED,
                'DECIMAL_ALWAYS_SHOWN'    => IntlNumberFormatter::DECIMAL_ALWAYS_SHOWN,
                'MAX_INTEGER_DIGITS'      => IntlNumberFormatter::MAX_INTEGER_DIGITS,
                'MIN_INTEGER_DIGITS'      => IntlNumberFormatter::MIN_INTEGER_DIGITS,
                'INTEGER_DIGITS'          => IntlNumberFormatter::INTEGER_DIGITS,
                'MAX_FRACTION_DIGITS'     => IntlNumberFormatter::MAX_FRACTION_DIGITS,
                'MIN_FRACTION_DIGITS'     => IntlNumberFormatter::MIN_FRACTION_DIGITS,
                'FRACTION_DIGITS'         => IntlNumberFormatter::FRACTION_DIGITS,
                'MULTIPLIER'              => IntlNumberFormatter::MULTIPLIER,
                'GROUPING_SIZE'           => IntlNumberFormatter::GROUPING_SIZE,
                'ROUNDING_MODE'           => [IntlNumberFormatter::ROUNDING_MODE, [
                    'CEILING'  => IntlNumberFormatter::ROUND_CEILING,
                    'DOWN'     => IntlNumberFormatter::ROUND_DOWN,
                    'FLOOR'    => IntlNumberFormatter::ROUND_FLOOR,
                    'HALFDOWN' => IntlNumberFormatter::ROUND_HALFDOWN,
                    'HALFEVEN' => IntlNumberFormatter::ROUND_HALFEVEN,
                    'HALFUP'   => IntlNumberFormatter::ROUND_HALFUP,
                    'UP'       => IntlNumberFormatter::ROUND_UP,
                ]],
                'ROUNDING_INCREMENT'      => IntlNumberFormatter::ROUNDING_INCREMENT,
                'FORMAT_WIDTH'            => IntlNumberFormatter::FORMAT_WIDTH,
                'PADDING_POSITION'        => [IntlNumberFormatter::PADDING_POSITION, [
                    'AFTER_PREFIX'  => IntlNumberFormatter::PAD_AFTER_PREFIX,
                    'AFTER_SUFFIX'  => IntlNumberFormatter::PAD_AFTER_SUFFIX,
                    'BEFORE_PREFIX' => IntlNumberFormatter::PAD_BEFORE_PREFIX,
                    'BEFORE_SUFFIX' => IntlNumberFormatter::PAD_BEFORE_SUFFIX,
                ]],
                'SECONDARY_GROUPING_SIZE' => IntlNumberFormatter::SECONDARY_GROUPING_SIZE,
                'SIGNIFICANT_DIGITS_USED' => IntlNumberFormatter::SIGNIFICANT_DIGITS_USED,
                'MIN_SIGNIFICANT_DIGITS'  => IntlNumberFormatter::MIN_SIGNIFICANT_DIGITS,
                'MAX_SIGNIFICANT_DIGITS'  => IntlNumberFormatter::MAX_SIGNIFICANT_DIGITS,
            ],
            'NUMBER_TEXT_ATTRS' => [
                'POSITIVE_PREFIX'   => IntlNumberFormatter::POSITIVE_PREFIX,
                'POSITIVE_SUFFIX'   => IntlNumberFormatter::POSITIVE_SUFFIX,
                'NEGATIVE_PREFIX'   => IntlNumberFormatter::NEGATIVE_PREFIX,
                'NEGATIVE_SUFFIX'   => IntlNumberFormatter::NEGATIVE_SUFFIX,
                'PADDING_CHARACTER' => IntlNumberFormatter::PADDING_CHARACTER,
                'CURRENCY_CODE'     => IntlNumberFormatter::CURRENCY_CODE,
            ],
            'NUMBER_SYMBOLS' => [
                'DECIMAL_SEPARATOR_SYMBOL'           => IntlNumberFormatter::DECIMAL_SEPARATOR_SYMBOL,
                'GROUPING_SEPARATOR_SYMBOL'          => IntlNumberFormatter::GROUPING_SEPARATOR_SYMBOL,
                'PERCENT_SYMBOL'                     => IntlNumberFormatter::PERCENT_SYMBOL,
                'DIGIT_SYMBOL'                       => IntlNumberFormatter::DIGIT_SYMBOL,
                'MINUS_SIGN_SYMBOL'                  => IntlNumberFormatter::MINUS_SIGN_SYMBOL,
                'PLUS_SIGN_SYMBOL'                   => IntlNumberFormatter::PLUS_SIGN_SYMBOL,
                'CURRENCY_SYMBOL'                    => IntlNumberFormatter::CURRENCY_SYMBOL,
                'MONETARY_SEPARATOR_SYMBOL'          => IntlNumberFormatter::MONETARY_SEPARATOR_SYMBOL,
                'EXPONENTIAL_SYMBOL'                 => IntlNumberFormatter::EXPONENTIAL_SYMBOL,
                'PERMILL_SYMBOL'                     => IntlNumberFormatter::PERMILL_SYMBOL,
                'PAD_ESCAPE_SYMBOL'                  => IntlNumberFormatter::PAD_ESCAPE_SYMBOL,
                'INFINITY_SYMBOL'                    => IntlNumberFormatter::INFINITY_SYMBOL,
                'NAN_SYMBOL'                         => IntlNumberFormatter::NAN_SYMBOL,
                'SIGNIFICANT_DIGIT_SYMBOL'           => IntlNumberFormatter::SIGNIFICANT_DIGIT_SYMBOL,
                'MONETARY_GROUPING_SEPARATOR_SYMBOL' => IntlNumberFormatter::MONETARY_GROUPING_SEPARATOR_SYMBOL,
            ],
        ];
    }

    /**
     * @param string $locale
     * @param string|array|null $number
     * @return IntlNumberFormatter
     */
    protected static function createDecimal(string $locale, string|array|null $number = null): IntlNumberFormatter
    {
        $pattern = null;
        if (is_string($number)) {
            $pattern = $number;
            $number = null;
        }
        $n = static::createIntlNumber($locale, IntlNumberFormatter::DECIMAL, $pattern);
        if (is_array($number)) {
            static::applyNumberSettings($n, $number);
        }

        return $n;
    }

    /**
     * @param string $locale
     * @param string|array|null $percent
     * @return IntlNumberFormatter
     */
    protected static function createPercent(string $locale, string|array|null $percent = null): IntlNumberFormatter
    {
        $pattern = null;
        if (is_string($percent)) {
            $pattern = $percent;
            $percent = null;
        }
        $n = static::createIntlNumber($locale, IntlNumberFormatter::PERCENT, $pattern);
        if (is_array($percent)) {
            static::applyNumberSettings($n, $percent);
        }

        return $n;
    }

    /**
     * @param string $locale
     * @param string|array|null $currency
     * @return IntlNumberFormatter
     */
    protected static function createCurrency(string $locale, string|array|null $currency = null): IntlNumberFormatter
    {
        if (is_string($currency)) {
            $locale .= '@currency=' . $currency;
        }
        $n = static::createIntlNumber($locale, IntlNumberFormatter::CURRENCY);
        if (is_array($currency)) {
            static::applyNumberSettings($n, $currency);
        }

        return $n;
    }

    /**
     * @param string $locale
     * @param int $type
     * @param string|null $pattern
     * @return IntlNumberFormatter
     */
    protected static function createIntlNumber(string $locale, int $type, ?string $pattern = null): IntlNumberFormatter
    {
        if ($pattern !== null && $pattern !== '') {
            $type = IntlNumberFormatter::PATTERN_DECIMAL;
        } else {
            $pattern = null;
        }

        return new IntlNumberFormatter($locale, $type, $pattern);
    }
}
