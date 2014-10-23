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

class TemplateStream
{
    const STREAM_PROTO = 'template';
    
    protected static $isRegistred = false;
    
    protected $content;
    
    protected $length;
    
    protected $pointer = 0;
    
    function stream_open($path, $mode, $options, &$opened_path)
    {
        list($class, $method) = explode('@', trim(substr($path, strlen(static::STREAM_PROTO . '://'))));
        
        $this->content = Using($class)->{$method}();
        
        $this->length = strlen($this->content);
        return true;
    }
     
    public function stream_read($count)
    {
        $value = substr($this->content, $this->pointer, $count);
        $this->pointer += $count;
        return $value;
    }
 
    public function stream_eof()
    {
        return $this->pointer >= $this->length;
    }
    
    public function stream_stat()
    {
        $stat = stat(__FILE__);
        $stat[7] = $stat['size'] = $this->length;
        return $stat;
    }
    
    public function url_stat($path, $flags)
    {
        $stat = stat(__FILE__);
        $stat[7] = $stat['size'] = $this->length;
        return $stat;
    }
    
    public function stream_seek($offset , $whence = SEEK_SET)
    {
        $crt = $this->pointer;
        
        switch ($whence)
        {
            case SEEK_SET:
                $this->pointer = $offset;
                break;
            case SEEK_CUR:
                $this->pointer += $offset;
                break;
            case SEEK_END:
                $this->pointer = $this->length + $offset;
                break;
        }
        
        if($this->pointer < 0 || $this->pointer >= $this->length)
        {
            $this->pointer = $crt;
            return false;
        }
        
        return true;
    }
    
    public function stream_tell()
    {
        return $this->pointer;
    }
    
    public static function register()
    {
        if(!static::$isRegistred)
        {
            static::$isRegistred = stream_wrapper_register(static::STREAM_PROTO, __CLASS__);
        }
    }
 
}
