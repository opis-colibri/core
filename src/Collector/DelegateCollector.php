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

namespace Opis\Colibri\Collector;

use Opis\Colibri\ItemCollector;
use ReflectionMethod;
use ReflectionObject;

abstract class DelegateCollector
{
    /** @var string[] */
    private $ignore;

    /**
     * DelegateCollector constructor.
     * @param string[] $ignore
     */
    public function __construct(array $ignore = [])
    {
        $this->ignore = $ignore;
    }

    /**
     * Collect items
     *
     * @param   ItemCollector $collector
     */
    public function collect(ItemCollector $collector)
    {
        $reflection = new ReflectionObject($this);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            if ($method->getShortName() == __FUNCTION__ || in_array($method->getShortName(), $this->ignore) ||
                $method->isStatic() || $method->isConstructor() || $method->isDestructor()
            ) {
                continue;
            }

            $method->invoke($this, $collector);
        }
    }
}
