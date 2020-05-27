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

namespace Opis\Colibri\Core;

use Opis\Colibri\Application;
use Opis\Container\Container as BaseContainer;

class Container extends BaseContainer
{
    public function __construct()
    {
        $this->instances[Application::class] = Application::getInstance();
    }

    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->__construct();
    }

    public function getInstance(string $key)
    {
        return $this->instances[$key] ?? null;
    }
}
