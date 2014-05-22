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

use Opis\Colibri\Module;

class ModuleInfo
{
    protected $info;
    
    protected $module;
    
    public function __construct($module)
    {
        if(!Module::exists($module))
        {
            throw new \Exception("Module $module doesn't exists");
        }
        
        $this->info = Module::info($module);
        $this->module = strtolower($module);
    }
    
    public function name()
    {
        return $this->info['name'];
    }
    
    public function title()
    {
        return $this->info['title'];
    }
    
    public function description()
    {
        return $this->info['description'];
    }
    
    public function dependencies()
    {
        return $this->info['dependencies'];
    }
    
    public function dependents()
    {
        if(!isset($this->info['dependents']))
        {
            $this->info['dependents'] = Module::dependents($this->module);
        }
        
        return $this->info['dependents'];
    }
    
    public function nspace()
    {
        return $this->info['namespace'];
    }
    
    public function source()
    {
        return $this->info['source'];
    }
    
    public function directory()
    {
        return $this->info['directory'];
    }
    
    public function collector()
    {
        return $this->info['collector'];
    }
    
    public function assets()
    {
        return $this->info['assets'];
    }
    
    public function resource($name, $absolute = true)
    {
        $path = '/assets/module/' . $this->module . '/' . trim($name, '/');
        
        if($absolute)
        {
            $path = Request()->uriForPath($path);
        }
        
        return $path;
    }
    
    public function isHidden()
    {
        return $this->info['hidden'];
    }
    
    public function isEnabled()
    {
        return Module::isEnabled($this->module);
    }
    
    public function isInstalled()
    {
        return Module::isInstalled($this->module);
    }
    
    public function canBeEnabled()
    {
        return Module::canBeEnabled($this->module);
    }
    
    public function canBeDisabled()
    {
        return Module::canBeDisabled($this->module);
    }
    
    public function canBeInstalled()
    {
        return Module::canBeInstalled($this->module);
    }
    
    public function canBeUninstalled()
    {
        return Module::canBeUninstalled($this->module);
    }
    
    public function enable()
    {
        return Module::enable($this->module);
    }
    
    public function disable()
    {
        return Module::disable($this->module);
    }
    
    public function install()
    {
        return Module::install($this->module);
    }
    
    public function uninstall()
    {
        return Module::uninstall($this->module);
    }
    
    
}
