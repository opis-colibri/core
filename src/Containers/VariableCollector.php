<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

namespace Opis\Colibri\Containers;

use Opis\Colibri\CollectingContainer;
use Opis\Colibri\Serializable\VariablesList;

/**
 * Class VariableCollector
 *
 * @package Opis\Colibri\Collectors
 *
 * @method VariablesList    data()
 */
class VariableCollector extends CollectingContainer
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new VariablesList());
    }

    /**
     * Register a new variable
     *
     * @param   string $name Variable's name
     * @param   mixed $value Variable's value
     *
     * @return  self
     */
    public function register($name, $value)
    {
        $this->dataObject->add($name, $value);
        return $this;
    }

    /**
     * Register multiple variable at once
     *
     * @param   array $variables An array of variables that will be registered
     *
     * @return  self
     */
    public function bulkRegister(array $variables)
    {
        foreach ($variables as $name => &$value) {
            $this->dataObject->add($name, $value);
        }

        return $this;
    }
}
