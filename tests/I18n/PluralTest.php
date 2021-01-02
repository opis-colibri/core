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

use Opis\Colibri\I18n\DefaultPlural;
use PHPUnit\Framework\TestCase;

class PluralTest extends TestCase
{

    public function testForms()
    {

        $p = DefaultPlural::create('en_US');
        $this->assertEquals(2, $p->forms());

        $p = DefaultPlural::create('ro_RO');
        $this->assertEquals(3, $p->forms());
    }

    public function testRule()
    {
        $p = DefaultPlural::create('en_US');
        $this->assertEquals(0, $p->form(1));
        $this->assertEquals(1, $p->form(0));
        $this->assertEquals(1, $p->form(2));
        $this->assertEquals(1, $p->form(-2));

        $p = DefaultPlural::create('ro_RO');
        $this->assertEquals(0, $p->form(1));
        $this->assertEquals(1, $p->form(0));
        $this->assertEquals(1, $p->form(2));
        $this->assertEquals(2, $p->form(25));
        $this->assertEquals(2, $p->form(100));
    }
}