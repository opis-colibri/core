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

namespace Opis\Colibri\Test;

use Opis\Colibri\Testing\Builders\ApplicationBuilder;

class RestTest extends BaseAppTestCase
{
    protected static function setupApp(ApplicationBuilder $builder): void
    {
        $builder->addEnabledModuleFromPath(__DIR__ . '/modules/RestTest');
    }

    public function test_actionGetList(): void
    {
        $result = $this->execJSON('/api/custom-controller', 'GET');

        $this->assertEquals(200, $result->getStatusCode());

        $this->assertEquals([1, 2, 3], $this->getJSONBody($result));
    }

    public function test_actionAddToList_ok(): void
    {
        $result = $this->execJSON('/api/custom-controller', 'POST', 5);

        $this->assertEquals(204, $result->getStatusCode());
    }

    public function test_actionAddToList_error_type(): void
    {
        $result = $this->execJSON('/api/custom-controller', 'POST', "5");

        $this->assertEquals(422, $result->getStatusCode());
        $this->assertEquals(['/' => "I expected 'integer' not 'string'"], $this->getRestErrors($result));
    }

    public function test_actionAddToList_error_minimum(): void
    {
        $result = $this->execJSON('/api/custom-controller', 'POST', 3);

        $this->assertEquals(422, $result->getStatusCode());
        //$this->assertEquals(['/' => "Send at least 4"], $this->getRestErrors($result));
    }
}