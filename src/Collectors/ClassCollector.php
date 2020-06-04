<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Serializable\ClassList;

/**
 * @property ClassList $data
 */
abstract class ClassCollector extends BaseCollector
{
    /**
     * ClassContainer constructor.
     */
    public function __construct()
    {
        parent::__construct(new ClassList($this->singletonClasses()));
    }

    /**
     * @param string $name Type name
     * @param string $class Class name
     * @return bool
     */
    public function register(string $name, string $class): bool
    {
        if (!class_exists($class) || !is_subclass_of($class, $this->getClass(), true)) {
            return false;
        }
        $this->data->add($name, $class);
        return true;
    }

    /**
     * @return bool
     */
    protected function singletonClasses(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    abstract protected function getClass(): string;
}
