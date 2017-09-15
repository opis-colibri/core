<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri\ItemCollectors;

use Opis\Colibri\ItemCollector;
use Opis\View\EngineEntry;
use Opis\View\EngineResolver;

/**
 * Class ViewEngineCollector
 *
 * @package Opis\Colibri\ItemCollectors
 *
 * @method EngineResolver   data()
 * @property EngineResolver $dataObject
 */
class ViewEngineCollector extends ItemCollector
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new EngineResolver());
    }

    /**
     * Defines a new view engine
     *
     * @param callable $factory
     * @param int $priority Engine's priority
     * @return EngineEntry
     */
    public function register(callable $factory, $priority = 0): EngineEntry
    {
        return $this->dataObject->register($factory, $priority);
    }
}
