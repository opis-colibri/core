<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

use Closure;
use InvalidArgumentException;

class Define
{
    public static function __callStatic($name, $arguments)
    {
        if(!isset($arguments[0]) || !($arguments[0] instanceof Closure))
        {
            $message = 'First argument passed to Opis\Colibri\Define::' . $name . ' should be a Closure.';
            throw new InvalidArgumentException($name);
        }
        
        if(!isset($arguments[1]) || !is_integer($arguments[1]))
        {
            $arguments[1] = 0;
        }
        
        return App::collector()->handle($name, $arguments[0], $arguments[1]);
    }
}
