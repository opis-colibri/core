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

use Opis\Colibri\Translator;
use Opis\Colibri\I18n\Translator\{Drivers\Memory, Driver, LanguageInfo};
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{

    const SYSTEM_LANG = [
        'ns1' => [
            'key1' => 'Key1 message (SYSTEM)',
            'key1_plural' => 'Key1 messages (SYSTEM)',

            'key2_0' => 'Key2 message (SYSTEM)',
            'key2_1' => 'Key2 messages (SYSTEM)',

            'key3' => 'Key3 has one message (SYSTEM)',
            'key3_plural' => 'Key3 has {count} messages (SYSTEM)',

            'key3_ctx' => 'Key3 has one message in this context (SYSTEM)',
            'key3_ctx_plural' => 'Key3 has {count} messages in this context (SYSTEM)',

            'nested' => [
                'nested1' => 'Nested message (SYSTEM)',
                'nested1_ctx' => 'Nested message with context (SYSTEM)',
            ],
        ],
        'ns2' => [
            'key1' => 'Key1 in ns2 message (SYSTEM)',
            'sysonly' => 'Key only in system language (SYSTEM)',
            'rep1' => 'Replaced one {name | replace:a:A} at {replaced_time | date:long:short} (SYSTEM)',
            'rep1_plural' => 'Replaced {count} {name|replace:a:A} at {replaced_time | date:medium:short} (SYSTEM)',
        ],
    ];

    const EN_LANG = [
        'ns1' => [
            'key1' => 'Key1 message (ENGLISH)',
            'key1_plural' => 'Key1 messages (ENGLISH)',

            'key2_0' => 'Key2 message (ENGLISH)',
            'key2_1' => 'Key2 messages (ENGLISH)',

            'key3' => 'Key3 has one message (ENGLISH)',
            'key3_plural' => 'Key3 has {count} messages (ENGLISH)',

            'key3_ctx' => 'Key3 has one message in this context (ENGLISH)',
            'key3_ctx_plural' => 'Key3 has {count} messages in this context (ENGLISH)',

            'nested' => [
                'nested1' => 'Nested message (ENGLISH)',
                'nested1_ctx' => 'Nested message with context (ENGLISH)',
            ],
        ],
        'ns2' => [
            'key1' => 'Key1 in ns2 message (ENGLISH)',
            //'sysonly'
            'rep1' => 'Replaced one {name | replace:a:A} at {replaced_time | date:long:short} (ENGLISH)',
            'rep1_plural' => 'Replaced {count} {name | replace:a:A} at {replaced_time | date:long:short} (ENGLISH)',
        ],
    ];

    const LANG_SETTINGS = [
        'locale' => 'en_US',
        'datetime' => [
            'timezone' => 'GMT',
        ]
    ];

    protected function getDriver(): Driver
    {
        return new Memory(['en_US' => self::LANG_SETTINGS], ['en_US' => self::EN_LANG]);
    }

    protected function getTranslator(): Translator
    {
        $driver = $this->getDriver();
        $sysns = self::SYSTEM_LANG;

        return new class($driver, $sysns, 'en_US') extends Translator {
            protected array $sysns;
            protected array $filters = [];


            public function __construct(Driver $driver, array $sysns, string $default_language)
            {
                parent::__construct($driver, $default_language);

                $this->sysns = $sysns;
                $this->filters['replace'] = static function ($value, array $args) {
                    return str_replace($args[0] ?? '', $args[1] ?? '', $value);
                };
                $this->filters['date'] = static function ($value, array $args, LanguageInfo $language) {
                    return $language->datetime()->format($value, $args[0] ?? 'short', $args[1] ?? 'short');
                };
            }


            /**
             * @inheritDoc
             */
            protected function loadSystemNS(string $ns): ?array
            {
                return $this->sysns[$ns] ?? null;
            }

            /**
             * @inheritDoc
             */
            protected function getFilter(string $name): ?callable
            {
                return $this->filters[$name] ?? null;
            }
        };
    }

    public function testTranslator()
    {
        $tr = $this->getTranslator();
        // ns1

        $this->assertEquals(
            'Key1 message (ENGLISH)',
            $tr->translate('ns1', 'key1', null, [], 1)
        );

        $this->assertEquals(
            'Key1 messages (ENGLISH)',
            $tr->translate('ns1', 'key1', null, [], 2)
        );

        $this->assertEquals(
            'Key2 message (ENGLISH)',
            $tr->translate('ns1', 'key2', null, [], 1)
        );

        $this->assertEquals(
            'Key2 messages (ENGLISH)',
            $tr->translate('ns1', 'key2', null, [], 2)
        );

        $this->assertEquals(
            'Key3 has one message (ENGLISH)',
            $tr->translate('ns1', 'key3', null, [], 1)
        );

        $this->assertEquals(
            'Key3 has 2 messages (ENGLISH)',
            $tr->translate('ns1', 'key3', null, [], 2)
        );

        $this->assertEquals(
            'Key3 has one message in this context (ENGLISH)',
            $tr->translate('ns1', 'key3', 'ctx', [], 1)
        );

        $this->assertEquals(
            'Key3 has 2 messages in this context (ENGLISH)',
            $tr->translate('ns1', 'key3', 'ctx', [], 2)
        );

        $this->assertEquals(
            'Key3 has 2 messages (ENGLISH)',
            $tr->translate('ns1', 'key3', 'other-context', [], 2)
        );

        $this->assertEquals(
            'Nested message (ENGLISH)',
            $tr->translate('ns1', 'nested.nested1')
        );

        $this->assertEquals(
            'Nested message with context (ENGLISH)',
            $tr->translate('ns1', 'nested.nested1', 'ctx')
        );

        // ns2

        $this->assertEquals(
            'Key1 in ns2 message (ENGLISH)',
            $tr->translate('ns2', 'key1')
        );

        $this->assertEquals(
            'Key1 in ns2 message (ENGLISH)',
            $tr->translate('ns2', 'key1')
        );

        $this->assertEquals(
            'Key only in system language (SYSTEM)',
            $tr->translate('ns2', 'sysonly')
        );

        $this->assertEquals(
            'ns2:otherkey',
            $tr->translate('ns2', 'otherkey')
        );

        $this->assertContains($tr->translate('ns2', 'rep1', null, ['name' => 'apple', 'replaced_time' => 0], 1), [
            'Replaced one Apple at January 1, 1970, 12:00 AM (ENGLISH)', // no intl
            'Replaced one Apple at January 1, 1970 at 12:00 AM (ENGLISH)',
        ]);

        $this->assertContains($tr->translate('ns2', 'rep1', null, ['name' => 'apples', 'replaced_time' => 86400], 2), [
            'Replaced 2 Apples at January 2, 1970, 12:00 AM (ENGLISH)', // no intl
            'Replaced 2 Apples at January 2, 1970 at 12:00 AM (ENGLISH)',
        ]);
    }

}