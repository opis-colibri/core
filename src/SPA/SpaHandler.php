<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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

namespace Opis\Colibri\SPA;

abstract class SpaHandler
{
    /** @var SpaInfo */
    protected $spa;

    /**
     * Handler constructor.
     */
    final public function __construct(SpaInfo $spa)
    {
        $this->spa = $spa;
    }

    /**
     * @param string $package
     * @param array|null $conf
     * @return mixed
     */
    abstract public function importPackage(string $package, array $conf = null);

    /**
     * @return mixed
     */
    abstract public function prepare();
}