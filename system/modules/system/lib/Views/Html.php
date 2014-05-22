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

use Opis\Colibri\View;

class Html extends View
{
    
    public function __construct($content, $title = null, $suggestion = null)
    {
        $name = $suggestion === null ? 'html' : 'html.' . $suggestion;
        
        parent::__construct($name, array(
            'title' => $title,
            'content' => $content,
            'icon' => null,
            'styles' => new CSSCollection(),
            'scripts' => new ScriptCollection(),
            'meta' => new MetaCollection(),
        ));
    }
    
    public function content($content)
    {
        return $this->set('content', $content);
    }
    
    public function title($title)
    {
        return $this->set('title', $title);
    }
    
    public function icon($path)
    {
        return $this->set('icon', $path);
    }
    
    public function css()
    {
        return $this->get('styles');
    }
    
    public function script()
    {
        return $this->get('scripts');
    }
    
    public function meta()
    {
        return $this->get('meta');
    }
}
