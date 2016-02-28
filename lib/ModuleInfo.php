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

class ModuleInfo
{
    /** @var    array */
    protected $info;

    /** @var    string */
    protected $module;

    /** @var    \Opis\Colibri\Application */
    protected $app;

    /**
     * Constructor
     * 
     * @param   \Opis\Colibri\Application   $app
     * @param   string                      $module
     */
    public function __construct(Application $app, $module)
    {
        $this->app = $app;
        $this->module = $module;
    }

    /**
     * Get the property's value
     * 
     * @param   string  $property
     * 
     * @return  mixed
     * 
     * @throws \Exception
     */
    protected function get($property)
    {
        if ($this->info === null) {
            if (!$this->exists()) {
                throw new \Exception("Module $module doesn't exists");
            }
            $this->info = $this->app->getModuleManager()->info($this->module);
        }
        
        return isset($this->info[$property]) ? $this->info[$property] : null;
    }

    /**
     * Check if the property exists
     * 
     * @param   string  $property
     * 
     * @return  boolean
     * 
     * @throws \Exception
     */
    protected function has($property)
    {
        if ($this->info === null) {
            if (!$this->exists()) {
                throw new \Exception("Module $module doesn't exists");
            } else {
                $this->info = $this->app->getModuleManager()->info($this->module);
                ;
            }
        }
        return isset($this->info[$property]);
    }

    /**
     * Set the value of a property
     * 
     * @param   string  $property
     * @param   mixed   $value
     * 
     * @throws \Exception
     */
    protected function set($property, $value)
    {
        if ($this->info === null) {
            if (!$this->exists()) {
                throw new \Exception("Module $module doesn't exists");
            } else {
                $this->info = $this->app->getModuleManager()->info($this->module);
            }
        }
        $this->info[$property] = $value;
    }

    /**
     * Get information about this module
     * 
     * @return  array
     * 
     * @throws  \Exception
     */
    public function info()
    {
        if ($this->info === null) {
            if (!$this->exists()) {
                throw new \Exception("Module $module doesn't exists");
            }
            $this->info = $this->app->getModuleManager()->info($this->module);
        }
        return $this->info;
    }

    /**
     * Check if the module exists
     * 
     * @return  boolean
     */
    public function exists()
    {
        return $this->app->getModuleManager()->exists($this->module);
    }

    /**
     * Get the module's name
     * 
     * @return  string
     */
    public function name()
    {
        return $this->get('name');
    }
    
    /**
     * Get the module's version
     * 
     * @return  string
     */
    public function version()
    {
        return $this->get('version');
    }

    /**
     * Get the module's title
     * 
     * @return  string
     */
    public function title()
    {
        return $this->get('title');
    }

    /**
     * Get the module's description
     * 
     * @return  string
     */
    public function description()
    {
        return $this->get('description');
    }

    /**
     * Get the module's dependencies
     * 
     * @return  string
     */
    public function dependencies()
    {
        return $this->get('dependencies');
    }

    /**
     * Get the module's dependents
     * 
     * @return  string
     */
    public function dependents()
    {
        if (!$this->has('dependents')) {
            $this->set('dependents', $this->app->getModuleManager()->dependents($this->module));
        }
        return $this->get('dependents');
    }

    /**
     * Get the module's namespace
     * 
     * @return  string
     */
    public function nspace()
    {
        return $this->get('namespace');
    }

    /**
     * Get the module's source folder
     * 
     * @return  string
     */
    public function source()
    {
        return $this->get('source');
    }

    /**
     * Get the module's location
     * 
     * @return  string
     */
    public function directory()
    {
        return $this->get('directory');
    }

    /**
     * Get the module's collector filename
     * 
     * @return  string
     */
    public function collector()
    {
        return $this->get('collector');
    }

    /**
     * Get the module's assets folder
     * 
     * @return  string
     */
    public function assets()
    {
        return $this->get('assets');
    }

    /**
     * Get the HTTP path to a module's resurce
     * 
     * @return  string
     */
    public function resource($name, $absolute = true)
    {
        $path = '/assets/module/' . strtolower($this->module) . '/' . trim($name, '/');

        if ($absolute) {
            $path = $this->app->request()->uriForPath($path);
        }

        return $path;
    }

    /**
     * Checks if the module is hidden
     * 
     * @return  boolean
     */
    public function isHidden()
    {
        return $this->get('hidden');
    }

    /**
     * Checks if the module is enabled
     * 
     * @return  boolean
     */
    public function isEnabled()
    {
        return $this->app->getModuleManager()->isEnabled($this->module);
    }

    /**
     * Checks if the module is installed
     * 
     * @return  boolean
     */
    public function isInstalled()
    {
        return $this->app->getModuleManager()->isInstalled($this->module);
    }

    /**
     * Checks if the module can be enabled
     * 
     * @return  boolean
     */
    public function canBeEnabled()
    {
        return $this->app->getModuleManager()->canBeEnabled($this->module);
    }

    /**
     * Checks if the module can be disabled
     * 
     * @return  boolean
     */
    public function canBeDisabled()
    {
        return $this->app->getModuleManager()->canBeDisabled($this->module);
    }

    /**
     * Checks if the module can be installed
     * 
     * @return  boolean
     */
    public function canBeInstalled()
    {
        return $this->app->getModuleManager()->canBeInstalled($this->module);
    }

    /**
     * Checks if the module can be uninstalled
     * 
     * @return  boolean
     */
    public function canBeUninstalled()
    {
        return $this->app->getModuleManager()->canBeUninstalled($this->module);
    }

    /**
     * Enable the module
     * 
     * @return  boolean
     */
    public function enable()
    {
        return $this->app->getModuleManager()->enable($this->module);
    }

    /**
     * Disable the module
     * 
     * @return  boolean
     */
    public function disable()
    {
        return $this->app->getModuleManager()->disable($this->module);
    }

    /**
     * Install the module
     * 
     * @return  boolean
     */
    public function install()
    {
        return $this->app->getModuleManager()->install($this->module);
    }

    /**
     * Uninstall the module
     * 
     * @return  boolean
     */
    public function uninstall()
    {
        return $this->app->getModuleManager()->uninstall($this->module);
    }
}
