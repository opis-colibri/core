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

namespace Opis\Colibri\Collectors;

use Closure;

interface LoggerCollectorInterface
{

    /**
     * Register a new storage
     *
     * @param   string      $storage        Storage name
     * @param   \Closure    $constructor    Storage constructor callback
     * @param   boolean     $default        (optional) Default flag
     *
     * @return  mixed
     */
    public function register($storage, Closure $constructor, $default = false);
}