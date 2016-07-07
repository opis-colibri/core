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
    public function __construct(Application $app, string $name, CompletePackage $package = null)
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
    public function getPackage(): CompletePackage
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
        
        $list = $this->app->getConfig()->read('modules.enabled', array());
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
    public function isInstalled(): bool
    {
        if (!$this->exists()) {
            return false;
        }

        $list = $this->app->getConfig()->read('modules.installed', array());
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
     * @return  boolean
     */
    public function enable(): bool
    {
        return $this->app->enable($this);
    }

    /**
     * Disable the module
     *
     * @return  boolean
     */
    public function disable(): bool
    {
        return $this->app->disable($this);
    }

    /**
     * Install the module
     *
     * @return  boolean
     */
    public function install(): bool
    {
        return $this->app->install($this);
    }

    /**
     * Uninstall the module
     *
     * @return  boolean
     */
    public function uninstall(): bool
    {
        return $this->app->uninstall($this);
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
                $value = $package->getName();
                break;
            case 'title':
                $value = $this->resolveTitle($package);
                break;
            case 'description':
                $value = $package->getDescription();
                break;
            case 'dependencies':
                $value = $this->resolveDependencies($package);
                break;
            case 'dependants':
                $value = $this->resolveDependants($package);
                break;
            case 'directory':
                $value = $this->resolveDirectory($package);
                break;
            case 'collector':
                $value = $this->resolveCollector($package);
                break;
            case 'installer':
                $value = $this->resolveInstaller($package);
                break;
            case 'hidden':
                $value = $this->resolveHidden($package);
                break;
        }

        return $this->info[$property] = $value;
    }

    /**
     * Get title
     *
     * @param   CompletePackage $package
     *
     * @return  string
     */
    protected function resolveTitle(CompletePackage $package): string
    {
        $extra = $package->getExtra();
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
     * Get hiffen
     *
     * @param   CompletePackage $package
     *
     * @return  bool
     */
    protected function resolveHidden(CompletePackage $package): bool
    {
        return (bool) $package->getExtra()['hidden'] ?? false;
    }

    /**
     * Resolve dependencies
     *
     * @param   CompletePackage $package
     *
     * @return  Module[]
     */
    protected function resolveDependencies(CompletePackage $package): array
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
     *
     * @return  Module[]
     */
    protected function resolveDependants(CompletePackage $package): array
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
     *
     * @return  string
     */
    protected function resolveDirectory(CompletePackage $package): string
    {
        return $this->app->getComposer()
            ->getInstallationManager()
            ->getInstallPath($package);
    }

    /**
     * Resolve collector class
     *
     * @param   CompletePackage $package
     *
     * @return  string|false
     */
    protected function resolveCollector(CompletePackage $package)
    {
        $extra = $package->getExtra();
        if (!isset($extra['collector'])) {
            return false;
        }

        $subject = $extra['collector'];
        $pattern = '`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`';

        return preg_match($pattern, $subject) ? $subject : false;
    }

    /**
     * Resolve installer class
     *
     * @param   CompletePackage $package
     *
     * @return  string|false
     */
    protected function resolveInstaller(CompletePackage $package)
    {
        $extra = $package->getExtra();
        if (!isset($extra['installer'])) {
            return false;
        }

        $subject = $extra['installer'];
        $pattern = '`^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$`';

        return preg_match($pattern, $subject) ? $subject : false;
    }

    /**
     * Resolve assets
     *
     * @param   CompletePackage $package
     *
     * @return  string|false
     */
    protected function resolveAssets(CompletePackage $package)
    {
        $extra = $package->getExtra();
        if (!isset($extra['assetes'])) {
            return false;
        }

        $directory = $this->directory() . '/' . trim($extra['assets'], '/');
        return is_dir($directory) ? $directory : false;
    }

}
