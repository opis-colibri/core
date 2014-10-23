<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

use Opis\Colibri\Event;
use Colibri\Module\System\Views\Html;

class HtmlEvent extends Event
{
    
    protected $html;
    
    protected $content;
    
    public function __construct(Html $html, $content, $name, $cancelable = false)
    {
        $this->html = $html;
        $this->content = $content;
        parent::__construct($name, $cancelable);
    }
    
    
    public function html()
    {
        return $this->html;
    }
    
    public function content()
    {
        return $this->content;
    }
    
}
