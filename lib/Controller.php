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

namespace Opis\Colibri;

class Controller
{
    /** @var    string */
    protected $method;

    /** @var    string */
    protected $className;

    /** @var    boolean */
    protected $isstatic;

    /**
     * Constructor
     * 
     * @param   string  $class
     * @param   string  $method
     * @param   boolean $static (optional)
     */
    public function __construct($class, $method, $static = false)
    {
        $this->className = $class;
        $this->method = $method;
        $this->isstatic = $static;
    }

    /**
     * Make the instances of this class being a valid callable value
     */
    public function __invoke()
    {
        
    }

    /**
     * Returns the class name
     * 
     * @return  string
     */
    public function getClass()
    {
        return $this->className;
    }

    /**
     * Returns the param's name that references the method
     * 
     * @return  string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Indicates if the referenced method is static or not
     * 
     * @return  boolan
     */
    public function isStatic()
    {
        return $this->isstatic;
    }
}
