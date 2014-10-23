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

namespace Colibri\Module\System\Views;

use Closure;

class MetaCollection extends Collection
{
    
    protected function createContentMeta($entry, $name, $content, Closure $callback = null)
    {
        $meta = new Meta();
        $meta->attributes(array(
            $entry => $name,
            'content' => $content,
        ));
        
        if($callback !== null)
        {
            $callback($meta);
        }
        
        return $meta;
    }
    
    public function custom($entry, Closure $callback)
    {
        $meta = new Meta();
        $callback($meta);
        return $this->add($meta, $entry);
    }
    
    public function httpEquiv($type, $value, Closure $callback = null)
    {
        $meta = new Meta();
        
        $meta->attributes(array(
            'http-equiv' => $type,
            'content' => $value,
        ));
        
        if($callback !== null)
        {
            $callback($meta);
        }
        
        return $this->add($meta);
    }
    
    public function charset($charset = 'UTF-8', Closure $callback = null)
    {
        $meta = new Meta();
        $meta->attribute('charset', $charset);
        
        if($callback !== null)
        {
            $callback($meta);
        }
        
        return $this->add($meta, 'charset');
    }
    
    
    public function contentType($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('http-equiv', 'content-type', $value, $callback), 'content-type');
    }
    
    public function defaultStyle($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('http-equiv', 'default-style', $value, $callback), 'default-style');
    }
    
    public function refresh($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('http-equiv', 'refresh', $value, $callback), 'refresh');
    }
    
    public function applicationName($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('name', 'application-name', $value, $callback), 'application-name');
    }
    
    public function author($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('name', 'author', $value, $callback), 'author');
    }
    
    public function description($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('name', 'description', $value, $callback), 'description');
    }
    
    public function generator($value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('name', 'generator', $value, $callback), 'generator');
    }
    
    public function keywords(array $value, Closure $callback = null)
    {
        return $this->add($this->createContentMeta('name', 'keywords', implode(', ', $value), $callback), 'keywords');
    }
}
