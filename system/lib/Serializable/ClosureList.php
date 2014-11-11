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

namespace Opis\Colibri\Serializable;

use Opis\Closure\SerializableClosure;

class ClosureList
{
    protected static $instance;
    
    protected static $closureList;
    
    protected $closures = array();
    
    protected $index = 0;
    

    protected function __construct()
    {
    }
    
    public function __destruct()
    {
        $entries = array();
        
        foreach($this->closures as $key => $value)
        {
            $entries[] = $this->getEntry($key, $value->getReflector()->getCode());
        }
        
        $entries = implode("\n", $entries);
        $entries = $this->getArray($entries);
        file_put_contents(COLIBRI_STORAGES_PATH . '/closures.php', $entries);
    }
    
    public function set(SerializableClosure $closure)
    {
        $this->closures[] = $closure;
        return $this->index++;
    }
    
    protected function getEntry($id, $code)
    {
        $entry = <<<'ENTRY'
    '{{i}}' => function(&$__u__s__e){
        if($__u__s__e)
        {
            extract($__u__s__e, EXTR_OVERWRITE | EXTR_REFS);   
        }
        return {{f}};
    },
ENTRY;
        $entry = str_replace('{{i}}', $id, $entry);
        return str_replace('{{f}}', $code, $entry);
    }
    
    protected function getArray($entries)
    {
        $code = <<<'CODE'
<?php
return array(
    {{c}}
);
CODE;
        return str_replace('{{c}}', $entries, $code);
    }
    
    public static function instance()
    {
        if(static::$instance === null)
        {
            static::$instance = new static();
        }
        
        return static::$instance;
    }
    
    public static function get($index, &$use)
    {
        if(static::$closureList === null)
        {
            static::$closureList = require_once COLIBRI_STORAGES_PATH . '/closures.php';
        }
        
        $wrapper = static::$closureList[$index];
        return $wrapper($use);
    }
    
}
