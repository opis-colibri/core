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

namespace Colibri\Module\System\Views;

use Closure;

class ScriptCollection extends Collection
{
    
    public function link($href, Closure $callback = null)
    {
        $script = new Script();
        
        if($callback !== null)
        {
            $callback($script);
        }
        
        return $this->add($script->src($href), $href);
    }
    
    public function inline($content, Closure $callback = null)
    {
        $script = new Script();
        if($callback !== null)
        {
            $callback($script);
        }
        return $this->add($script->content($content), md5($content));
    }
    
}
