<?php
/* ===========================================================================
 * Copyright 2020-2021 Zindex Software
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

namespace Opis\Colibri\Routing;

use ArrayAccess;

abstract class Invoker
{
    private mixed $result;
    private ArrayAccess $defaults;
    private ?ArgumentResolver $argumentResolver = null;

    public function __construct(ArrayAccess $defaults)
    {
        $this->defaults = $defaults;
        $this->result = $this;
    }

    public abstract function getValues(): array;

    /**
     * @return callable[]
     */
    public abstract function getBindings(): array;

    public function invokeAction(): mixed
    {
        if ($this->result === $this) {
            $callback = $this->getCallback();
            $arguments = $this->getArgumentResolver()->resolve($callback);
            $this->result = $callback(...$arguments);
        }

        return $this->result;
    }

    public function getArgumentResolver(): ArgumentResolver
    {
        if ($this->argumentResolver === null) {

            $resolver = new ArgumentResolver($this->defaults);

            foreach ($this->getValues() as $key => $value) {
                $resolver->addValue($key, $value);
            }

            foreach ($this->getBindings() as $key => $callback) {
                $resolver->addBinding($key, $callback);
            }

            $this->argumentResolver = $resolver;
        }

        return $this->argumentResolver;
    }

    abstract protected function getCallback(): callable;
}