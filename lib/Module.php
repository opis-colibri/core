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

use Composer\Package\CompletePackage;
use Exception;

class Module
{
    /** @var    array */
    protected $info = array();

    /** @var    string */
    protected $name;

    /** @var    CompletePackage */
    protected $package;

    /** @var    Application */
    protected $app;

    protected $exists;

    /**
     * Constructor
     *
     * @param   Application $app
     * @param   string $name
     * @param   CompletePackage $package (optional)
     */
    public function __construct(Application $app, $name, CompletePackage $package = null)
    {
        $this->app = $app;
        $this->name = $name;
    }

    /**
     * Get the associated package
     *
     * @return CompletePackage
     *
     * @throws Exception
     */
    public function getPackage()
    {
        if ($this->package === null) {
            $packages = $this->app->getPackages();
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
    public function name()
    {
        return $this->get(__FUNCTION__);
    }


    /**
     * Get the module's version
     *
     * @return  string
     */
    public function version()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's title
     *
     * @return  string
     */
    public function title()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's description
     *
     * @return  string
     */
    public function description()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's location
     *
     * @return  string
     */
    public function directory()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's collector instance
     *
     * @return  string
     */
    public function collector()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's collector class
     *
     * @return  string
     */
    public function installer()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's assets folder
     *
     * @return  string
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
    public function isHidden()
    {
        return $this->get('hidden');
    }

    /**
     * Checks if the module can be enabled
     *
     * @return  boolean
     */
    public function canBeEnabled()
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
    public function isEnabled()
    {
        if (!$this->exists()) {
            return false;
        }

        $list = $this->app->config()->read('app.modules.enabled', array());
        return in_array($this->name, $list);
    }

    /**
     * Check if the module exists
     *
     * @return  boolean
     */
    public function exists()
    {
        if ($this->exists === null) {
            $packages = $this->app->getPackages();
            $this->exists = isset($packages[$this->name]);
        }

        return $this->exists;
    }

    /**
     * Checks if the module is installed
     *
     * @return  boolean
     */
    public function isInstalled()
    {
        if (!$this->exists()) {
            return false;
        }

        $list = $this->app->config()->read('app.modules.installed', array());
        return in_array($this->name, $list);
    }

    /**
     * Get the module's dependencies
     *
     * @return  Module[]
     */
    public function dependencies()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Checks if the module can be disabled
     *
     * @return  boolean
     */
    public function canBeDisabled()
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
    public function dependents()
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Checks if the module can be installed
     *
     * @return  boolean
     */
    public function canBeInstalled()
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
    public function canBeUninstalled()
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
     * @return  boolean
     */
    public function enable()
    {
        return $this->app->enable($this);
    }

    /**
     * Disable the module
     *
     * @return  boolean
     */
    public function disable()
    {
        return $this->app->disable($this);
    }

    /**
     * Install the module
     *
     * @return  boolean
     */
    public function install()
    {
        return $this->app->install($this);
    }

    /**
     * Uninstall the module
     *
     * @return  boolean
     */
    public function uninstall()
    {
        return $this->app->uninstall($this);
    }

    /**
     * @param string $property
     * @return bool|mixed|null|Module[]|string
     * @throws Exception
     */
    protected function get($property)
    {
        if (array_key_exists($property, $this->info)) {
            return $this->info[$property];
        }

        $value = null;
        $package = $this->getPackage();
        $extra = $package->getExtra();

        switch ($property) {
            case 'name':
                $value = $package->getName();
                break;
            case 'version':
                $value = $package->getName();
                break;
            case 'title':
                $value = $this->resolveTitle($package, $extra);
                break;
            case 'description':
                $value = $package->getDescription();
                break;
            case 'dependencies':
                $value = $this->resolveDependencies($package, $extra);
                break;
            case 'dependants':
                $value = $this->resolveDependants($package, $extra);
                break;
            case 'directory':
                $value = $this->resolveDirectory($package, $extra);
                break;
            case 'collector':
                $value = $this->resolveCollector($package, $extra);
                break;
            case 'installer':
                $value = $this->resolveInstaller($package, $extra);
                break;
            case 'hidden':
                $value = isset($extra['hidden']) ? (bool)$extra['hidden'] : false;
                break;
        }

        return $this->info[$property] = $value;
    }

    /**
     * Get title
     *
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  string
     */
    protected function resolveTitle($package, $extra)
    {
        $title = isset($extra['title']) ? trim($extra['title']) : '';

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
     * Resolve dependencies
     *
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  Module[]
     */
    protected function resolveDependencies($package, $extra)
    {
        $dependencies = array();
        $modules = $this->app->getModules();

        foreach ($package->getRequires() as $dependency) {
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
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  Module[]
     */
    protected function resolveDependants($package, $extra)
    {
        $dependants = array();
        $modules = $this->app->getModules();

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
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  string
     */
    protected function resolveDirectory($package, $extra)
    {
        return $this->app->getComposer()
            ->getInstallationManager()
            ->getInstallPath($package);
    }

    /**
     * Resolve collector class
     *
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  string
     */
    protected function resolveCollector($package, $extra)
    {
        if (!isset($extra['collector'])) {
            return null;
        }

        $subject = $extra['collector'];
        $pattern = '`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`';

        return preg_match($pattern, $subject) ? $subject : null;
    }

    /**
     * Resolve installer class
     *
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  string
     */
    protected function resolveInstaller($package, $extra)
    {
        if (!isset($extra['installer'])) {
            return null;
        }

        $subject = $extra['installer'];
        $pattern = '`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`';

        return preg_match($pattern, $subject) ? $subject : null;
    }

    /**
     * Resolve assets
     *
     * @param   CompletePackage $package
     * @param   array $extra
     *
     * @return  string
     */
    protected function resolveAssets($package, $extra)
    {
        if (!isset($extra['assetes'])) {
            return null;
        }

        $directory = $this->directory() . '/' . trim($extra['assets'], '/');
        return is_dir($directory) ? $directory : null;
    }

}
