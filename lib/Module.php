<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Composer\Package\CompletePackage;
use Exception;
use function Opis\Colibri\Helpers\{app, config};

class Module
{
    /** @var    array */
    protected $info = array();

    /** @var    string */
    protected $name;

    /** @var    CompletePackage */
    protected $package;

    /** @var  bool */
    protected $exists;

    /** @var  array */
    protected $moduleInfo;

    /**
     * Constructor
     *
     * @param   string $name
     * @param   CompletePackage $package (optional)
     */
    public function __construct(string $name, CompletePackage $package = null)
    {
        $this->name = $name;
        $this->package = $package;
    }

    /**
     * Get the associated package
     *
     * @return CompletePackage
     *
     * @throws Exception
     */
    public function getPackage(): CompletePackage
    {
        if ($this->package === null) {
            $packages = app()->getPackages();
            if (!isset($packages[$this->name])) {
                throw new Exception('Module `' . $this->name . "` doesn't exist");
            }
            $this->package = $packages[$this->name];
        }

        return $this->package;
    }

    /**
     * Get the module's name
     *
     * @return  string
     */
    public function name(): string
    {
        return $this->get(__FUNCTION__);
    }


    /**
     * Get the module's version
     *
     * @return  string
     */
    public function version(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's title
     *
     * @return  string
     */
    public function title(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's description
     *
     * @return  string
     */
    public function description(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's location
     *
     * @return  string
     */
    public function directory(): string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's collector instance
     *
     * @return  string|false
     */
    public function collector()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's collector class
     *
     * @return  string|false
     */
    public function installer()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's assets folder
     *
     * @return  string|false
     */
    public function assets()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Checks if the module is hidden
     *
     * @return  boolean
     */
    public function isHidden(): bool
    {
        return $this->get('hidden');
    }

    /**
     * Checks if the module can be enabled
     *
     * @return  boolean
     */
    public function canBeEnabled(): bool
    {
        if ($this->isEnabled() || !$this->isInstalled()) {
            return false;
        }

        foreach ($this->dependencies() as $module) {
            if (!$module->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the module is enabled
     *
     * @return  boolean
     */
    public function isEnabled(): bool
    {
        if (!$this->exists()) {
            return false;
        }
        
        $list = config()->read('modules.enabled', array());
        return in_array($this->name, $list);
    }

    /**
     * Check if the module exists
     *
     * @return  boolean
     */
    public function exists(): bool
    {
        if ($this->exists === null) {
            $packages = app()->getPackages();
            $this->exists = isset($packages[$this->name]);
        }

        return $this->exists;
    }

    /**
     * Checks if the module is installed
     *
     * @return  boolean
     */
    public function isInstalled(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $list = config()->read('modules.installed', array());
        return in_array($this->name, $list);
    }

    /**
     * Get the module's dependencies
     *
     * @return  Module[]
     */
    public function dependencies(): array
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Checks if the module can be disabled
     *
     * @return  boolean
     */
    public function canBeDisabled(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        foreach ($this->dependents() as $module) {
            if ($module->isInstalled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the module's dependents
     *
     * @return  Module[]
     */
    public function dependents(): array
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Checks if the module can be installed
     *
     * @return  boolean
     */
    public function canBeInstalled(): bool
    {
        if ($this->isInstalled()) {
            return false;
        }

        foreach ($this->dependencies() as $module) {
            if (!$module->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Checks if the module can be uninstalled
     *
     * @return  boolean
     */
    public function canBeUninstalled(): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        foreach ($this->dependents() as $module) {
            if ($module->isInstalled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Enable the module
     *
     * @param bool $recollect
     * @return  boolean
     */
    public function enable(bool $recollect = true): bool
    {
        return app()->enable($this, $recollect);
    }

    /**
     * Disable the module
     *
     * @param bool $recollect
     * @return  boolean
     */
    public function disable(bool $recollect = true): bool
    {
        return app()->disable($this, $recollect);
    }

    /**
     * Install the module
     *
     * @param bool $recollect
     * @return  boolean
     */
    public function install(bool $recollect = true): bool
    {
        return app()->install($this, $recollect);
    }

    /**
     * Uninstall the module
     *
     * @param bool $recollect
     * @return  boolean
     */
    public function uninstall(bool $recollect = true): bool
    {
        return app()->uninstall($this, $recollect);
    }

    /**
     * @param string $property
     * @return bool|mixed|null|Module[]|string
     * @throws Exception
     */
    protected function get(string $property)
    {
        if (array_key_exists($property, $this->info)) {
            return $this->info[$property];
        }

        $value = null;
        $package = $this->getPackage();

        switch ($property) {
            case 'name':
                $value = $package->getName();
                break;
            case 'version':
                $value = $package->getPrettyVersion();
                break;
            case 'title':
                $value = $this->resolveTitle();
                break;
            case 'description':
                $value = $package->getDescription();
                break;
            case 'dependencies':
                $value = $this->resolveDependencies();
                break;
            case 'dependents':
                $value = $this->resolveDependents();
                break;
            case 'directory':
                $value = $this->resolveDirectory();
                break;
            case 'assets':
                $value = $this->resolveAssets();
                break;
            case 'collector':
                $value = $this->resolveCollector();
                break;
            case 'installer':
                $value = $this->resolveInstaller();
                break;
            case 'hidden':
                $value = $this->resolveHidden();
                break;
        }

        return $this->info[$property] = $value;
    }

    /**
     * @return array
     */
    protected function getModuleInfo(): array
    {
        if($this->moduleInfo === null){
            $this->moduleInfo = $this->getPackage()->getExtra()['module'] ?? [];
        }

        return $this->moduleInfo;
    }

    /**
     * Get title
     *
     * @return  string
     */
    protected function resolveTitle(): string
    {
        $title = trim($this->getModuleInfo()['title'] ?? '');

        if (empty($title)) {
            $name = substr($this->name, strpos($this->name, '/') + 1);
            $name = array_map(function ($value) {
                return strtolower($value);
            }, explode('-', $name));
            $title = ucfirst(implode(' ', $name));
        }

        return $title;
    }

    /**
     * Get hidden
     *
     * @return  bool
     */
    protected function resolveHidden(): bool
    {
        return $this->getModuleInfo()['hidden'] ?? false;
    }

    /**
     * Resolve dependencies
     *
     * @return  Module[]
     */
    protected function resolveDependencies(): array
    {
        $dependencies = array();
        $modules = app()->getModules();

        foreach ($this->getPackage()->getRequires() as $dependency) {
            $target = $dependency->getTarget();
            if (isset($modules[$target])) {
                $dependencies[$target] = $modules[$target];
            }
        }

        return $dependencies;
    }

    /**
     * Resolve dependants
     *
     * @return  Module[]
     */
    protected function resolveDependents(): array
    {
        $dependants = array();
        $modules = app()->getModules();

        foreach ($modules as $name => $module) {
            if ($name === $this->name) {
                continue;
            }
            $dependencies = $module->dependencies();
            if (isset($dependencies[$this->name])) {
                $dependants[$name] = $module;
            }
        }

        return $dependants;
    }

    /**
     * Resolve directory
     *
     * @return  string
     */
    protected function resolveDirectory(): string
    {
        return app()->getComposer()
                    ->getInstallationManager()
                    ->getInstallPath($this->getPackage());
    }

    /**
     * Resolve collector class
     *
     * @return  string|false
     */
    protected function resolveCollector()
    {
        $collector = $this->getModuleInfo()['collector'] ?? false;
        if(!is_array($collector) || !isset($collector['class']) || !isset($collector['file'])){
            return false;
        }
        return $collector['class'];
    }

    /**
     * Resolve installer class
     *
     * @return  string|false
     */
    protected function resolveInstaller()
    {
        $installer = $this->getModuleInfo()['installer'] ?? false;
        if(!is_array($installer) || !isset($installer['class']) || !isset($installer['file'])){
            return false;
        }
        return $installer['class'];
    }

    /**
     * Resolve assets
     * 
     * @return  string|false
     */
    protected function resolveAssets()
    {
        $module = $this->getModuleInfo();
        if (!isset($module['assets'])) {
            return false;
        }

        $directory = $this->directory() . '/' . trim($module['assets'], '/');
        return is_dir($directory) ? $directory : false;
    }

}
