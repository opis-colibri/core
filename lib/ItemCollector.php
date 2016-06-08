<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri;

use ReflectionObject;
use ReflectionMethod;
use Opis\Colibri\Collectors\AbstractCollector;

abstract class ItemCollector
{

    /**
     * Constructor
     */
    final public function __construct()
    {

    }

    /**
     * Factory
     *
     * @return  \static
     */
    final public static function newInstance()
    {
        return new static();
    }

    /**
     * Collect items
     *
     * @param   \Opis\Colibri\Collectors\AbstractCollector $collector
     * @param   \Opis\Colibri\Application $app
     * @param   mixed|null $extra (optional)
     */
    public function collect(AbstractCollector $collector, Application $app, $extra = null)
    {
        $reflection = new ReflectionObject($this);
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            if ($method->getShortName() == __FUNCTION__ ||
                $method->isStatic() ||
                $method->isConstructor() || $method->isDestructor()
            ) {
                continue;
            }

            $method->invoke($this, $collector, $app, $extra);
        }
    }
}
