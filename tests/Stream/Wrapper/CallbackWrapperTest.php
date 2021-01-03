<?php
/* ============================================================================
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

namespace Opis\Colibri\Test\Stream\Wrapper;

use Opis\Colibri\Stream\Wrapper\CallbackStreamWrapper;
use PHPUnit\Framework\TestCase;

class CallbackWrapperTest extends TestCase
{

    public static function funcWithoutParams()
    {
        return strtoupper(__FUNCTION__);
    }

    public static function funcGetSum(array $args = null)
    {
        return $args ? array_sum($args) : 0;
    }

    public static function funcObjReturn(array $args = null)
    {
        $args = $args['data'] ?? null;
        return new class($args)
        {
            private $data;

            public function __construct($data)
            {
                $this->data = (string)$data;
            }

            /**
             * @inheritDoc
             */
            public function __toString()
            {
                return $this->data . ' altered';
            }
        };
    }

    public static function funcSubCallback(array $args = null)
    {
        if (!isset($args['func']) || !is_string($args['func'])) {
            return null;
        }

        $data = CallbackStreamWrapper::execute($args['func'], $args['params'] ?? null);

        if ($data === null) {
            return null;
        }

        if (isset($args['label'])) {
            return $args['label'] . ' ' . $data;
        }

        return $data;
    }

    public function testSimple()
    {
        $this->assertEquals(strtoupper('funcWithoutParams'), $this->getData('funcWithoutParams'));
    }

    public function testParams()
    {
        $this->assertEquals('6', $this->getData('funcGetSum', [
            'a' => 1,
            'b' => 2,
            'c' => 3,
        ]));

        $this->assertEquals('my data altered', $this->getData('funcObjReturn', [
            'data' => 'my data',
        ]));
    }

    public function testSub()
    {
        $this->assertEquals(strtoupper('funcWithoutParams'), $this->getData('funcSubCallback', [
            'func' => static::class . '::funcWithoutParams',
        ]));

        $this->assertEquals('test altered', $this->getData('funcSubCallback', [
            'func' => static::class . '::funcObjReturn',
            'params' => [
                'data' => 'test',
            ],
        ]));

        $this->assertEquals('sub test altered', $this->getData('funcSubCallback', [
            'func' => static::class . '::funcSubCallback',
            'params' => [
                'func' => static::class . '::funcObjReturn',
                'params' => [
                    'data' => 'sub test',
                ],
            ],
        ]));
    }

    /**
     * @param string $method
     * @param array|null $params
     * @return bool|string
     */
    protected function getData(string $method, ?array $params = null)
    {
        return CallbackStreamWrapper::execute(static::class . '::' . $method, $params);
    }
}