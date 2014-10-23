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

namespace Opis\Colibri;

use Opis\Colibri\Module;

class ModuleInfo
{
    protected $info;
    
    protected $module;
    
    public function __construct($module)
    {
        $this->module = strtolower($module);
    }
    
    protected function get($property)
    {
        if($this->info === null)
        {
            if(!$this->exists())
            {
                throw new \Exception("Module $module doesn't exists");
            }
            
            $this->info = Module::info($this->module);
        }
        
        return $this->info[$property];
    }
    
    protected function has($property)
    {
        if($this->info === null)
        {
            if(!$this->exists())
            {
                throw new \Exception("Module $module doesn't exists");
            }
            else
            {
                $this->info = Module::info($this->module);
            }
        }
        
        return isset($this->info[$property]);
    }
    
    protected function set($property, $value)
    {
        if($this->info === null)
        {
            if(!$this->exists())
            {
                throw new \Exception("Module $module doesn't exists");
            }
            else
            {
                $this->info = Module::info($this->module);
            }
        }
        
        $this->info[$property] = $value;
    }
    
    public function exists()
    {
        return Module::exists($this->module);
    }
    
    public function name()
    {
        return $this->get('name');
    }
    
    public function title()
    {
        return $this->get('title');
    }
    
    public function description()
    {
        return $this->get('description');
    }
    
    public function dependencies()
    {
        return $this->get('dependencies');
    }
    
    public function dependents()
    {
        if(!$this->has('dependents'))
        {
            $this->set('dependents', Module::dependents($this->module));
        }
        
        return $this->get('dependents');
    }
    
    public function nspace()
    {
        return $this->get('namespace');
    }
    
    public function source()
    {
        return $this->get('source');
    }
    
    public function directory()
    {
        return $this->get('directory');
    }
    
    public function collector()
    {
        return $this->get('collector');
    }
    
    public function assets()
    {
        return $this->get('assets');
    }
    
    public function resource($name, $absolute = true)
    {
        $path = '/assets/module/' . $this->module . '/' . trim($name, '/');
        
        if($absolute)
        {
            $path = HttpRequest()->uriForPath($path);
        }
        
        return $path;
    }
    
    public function isHidden()
    {
        return $this->get('hidden');
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
