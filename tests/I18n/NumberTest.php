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

namespace Opis\Colibri\Test\I18n;

use Opis\Colibri\I18n\{
    IntlChecker, DefaultNumberFormatter
};
use PHPUnit\Framework\TestCase;

class NumberTest extends TestCase
{

    const NUMBER = 987612345.06789;

    public function testFormat()
    {
        $n = DefaultNumberFormatter::create('en_US');

        $this->assertEquals('987,612,345.068', $n->formatDecimal(self::NUMBER));
        $this->assertEquals('$987,612,345.07', $n->formatCurrency(self::NUMBER));
        $this->assertEquals('£987,612,345.07', $n->formatCurrency(self::NUMBER, 'GBP'));
        $this->assertEquals('€987,612,345.07', $n->formatCurrency(self::NUMBER, 'EUR'));
        $this->assertEquals('25%', $n->formatPercent(0.25));
    }

    public function testOptions()
    {
        $n = DefaultNumberFormatter::fromArray([
            'locale' => 'en_US',
            'decimal' => [
                'rounding_mode' => 'down',
            ],
            'currency' => [
                'currency_code' => 'EUR',
            ],
            'percent' => [
                'percent_symbol' => '/100',
            ]
        ]);

        $intl = IntlChecker::extensionExists();

        $this->assertEquals($intl ? '987,612,345.067' : '987,612,345.068', $n->formatDecimal(self::NUMBER));
        $this->assertEquals($intl ? '€987,612,345.07' : '$987,612,345.07', $n->formatCurrency(self::NUMBER));
        $this->assertEquals('£987,612,345.07', $n->formatCurrency(self::NUMBER, 'GBP'));
        $this->assertEquals('$987,612,345.07', $n->formatCurrency(self::NUMBER, 'USD'));
        $this->assertEquals($intl ? '25/100' : '25%', $n->formatPercent(0.25));
    }


}