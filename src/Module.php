<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

use Composer\Package\CompletePackageInterface;

class Module
{
    const UNINSTALLED = 0;

    const INSTALLED = 1;

    const ENABLED = 2;

    /** @var Application */
    protected $app;

    /** @var    array */
    protected $info = [];

    /** @var    string */
    protected $name;

    /** @var CompletePackageInterface */
    protected $package;

    /** @var  bool */
    protected $exists;

    /** @var  array */
    protected $moduleInfo;

    /**
     * Module constructor.
     * @param Application $app
     * @param string $name
     * @param CompletePackageInterface|null $package
     */
    public function __construct(Application $app, string $name, CompletePackageInterface $package = null)
    {
        $this->app = $app;
        $this->name = $name;
        $this->package = $package;
    }

    /**
     * Get the associated package
     *
     * @return CompletePackageInterface
     */
    public function getPackage(): CompletePackageInterface
    {
        if ($this->package === null) {
            $packages = $this->app->getPackages();
            if (!isset($packages[$this->name])) {
                throw new \RuntimeException('Module `' . $this->name . "` doesn't exist");
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
     * @return  string|null
     */
    public function collector(): ?string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's collector class
     *
     * @return  string|null
     */
    public function installer(): ?string
    {
        return $this->get(__FUNCTION__);
    }

    /**
     * Get the module's assets folder
     *
     * @return  string|null
     */
    public function assets(): ?string
    {
        return $this->get(__FUNCTION__);
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
     * Get the module's dependents
     *
     * @return  Module[]
     */
    public function dependents(): array
    {
        return $this->get(__FUNCTION__);
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
     * Checks if the module is hidden
     *
     * @return  boolean
     */
    public function isApplicationInstaller(): bool
    {
        return $this->get('is-app-installer');
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

        return $this->app->getConfig()->read(['modules', $this->name], self::UNINSTALLED) === self::ENABLED;
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

        return $this->app->getConfig()->read(['modules', $this->name], self::UNINSTALLED) >= self::INSTALLED;
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
            if ($module->isEnabled()) {
                return false;
            }
        }

        return true;
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
            if (!$module->isInstalled()) {
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
        if ($this->isEnabled() || !$this->isInstalled()) {
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
     * @param string $property
     * @return bool|mixed|null|Module[]|string
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
            case 'is-app-installer':
                $value = $this->resolveIsAppInstaller();
                break;
        }

        return $this->info[$property] = $value;
    }

    /**
     * @return array
     */
    protected function getModuleInfo(): array
    {
        if ($this->moduleInfo === null) {
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
    protected function resolveIsAppInstaller(): bool
    {
        return $this->getModuleInfo()['is-app-installer'] ?? false;
    }

    /**
     * Resolve dependencies
     *
     * @return  Module[]
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [];
        $modules = $this->app->getModules();

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
        $dependants = [];
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
     * @return  string
     */
    protected function resolveDirectory(): string
    {
        return $this->app->getAppInfo()->vendorDir() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR,
                explode('/', $this->name));
    }

    /**
     * Resolve collector class
     *
     * @return  string|null
     */
    protected function resolveCollector(): ?string
    {
        $value = $this->getModuleInfo()['collector'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Resolve installer class
     *
     * @return  string|null
     */
    protected function resolveInstaller(): ?string
    {
        $value = $this->getModuleInfo()['installer'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Resolve assets
     *
     * @return  string|null
     */
    protected function resolveAssets(): ?string
    {
        $module = $this->getModuleInfo();
        if (!isset($module['assets'])) {
            return null;
        }

        $directory = $this->directory() . DIRECTORY_SEPARATOR . trim($module['assets'], DIRECTORY_SEPARATOR);
        return is_dir($directory) ? $directory : null;
    }

}
