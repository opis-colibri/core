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

namespace Colibri\Module\System;

use Opis\Routing\Contracts\PathInterface;
use Opis\Routing\Contracts\RouteInterface;
use Opis\Routing\Dispatcher as BaseDispatcher;
use Colibri\Module\System\Views\Html;

class HtmlDispatcher extends BaseDispatcher
{
    
    public function dispatch(PathInterface $path, RouteInterface $route)
    {
        
        $content = parent::dispatch($path, $route);
        
        if($content === false || $content === null)
        {
            return null;
        }
        elseif($content instanceof Html)
        {
            $page = $content;
        }
        else
        {
            $page = new Html($content);
        }
        
        Dispatch(new HtmlEvent($page, $content, 'system.html.init', true));
        
        if(null !== $callback = $route->get('callback'))
        {
            $result = $callback($page, $content, $path, $route);
            
            if($result !== null)
            {
                return $result;
            }
        }
        
        return Dispatch(new HtmlEvent($page, $content, 'system.html.load', true))->html();
    }
    
}
