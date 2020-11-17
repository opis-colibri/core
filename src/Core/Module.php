<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\Core;

use Composer\Package\CompletePackageInterface;

class Module
{
    const UNINSTALLED = 0;
    const INSTALLED = 1;
    const ENABLED = 2;

    const TYPE = 'opis-colibri-module';

    protected ModuleManager $manager;

    protected string $name;

    protected ?CompletePackageInterface $package = null;

    protected array $info = [];

    protected ?array $moduleInfo = null;

    protected ?bool $exists = null;

    /**
     * @param ModuleManager $manager
     * @param string $name
     * @param CompletePackageInterface|null $package
     */
    public function __construct(ModuleManager $manager, string $name, ?CompletePackageInterface $package = null)
    {
        $this->manager = $manager;
        $this->name = $name;
        $this->package = $package;
    }

    /**
     * @return CompletePackageInterface
     */
    public function package(): CompletePackageInterface
    {
        if ($this->package === null) {
            $this->package = $this->manager->package($this->name);
            if ($this->package === null) {
                throw new \RuntimeException("Module '{$this->name}' cannot be resolved");
            }
        }

        return $this->package;
    }

    /**
     * @return int
     */
    public function status(): int
    {
        return $this->manager->getStatus($this->name);
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return string
     */
    public function version(): string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return string
     */
    public function directory(): string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return null|string
     */
    public function collector(): ?string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return null|string
     */
    public function installer(): ?string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return null|string
     */
    public function assets(): ?string
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return Module[]
     */
    public function dependencies(): array
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return Module[]
     */
    public function dependants(): array
    {
        return $this->getCached(__FUNCTION__);
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->exists === null) {
            $this->exists = false;
            if ($this->package !== null) {
                $this->exists = true;
            } elseif ($this->package = $this->manager->package($this->name)) {
                $this->exists = true;
            }
        }
        return $this->exists;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->status() === self::ENABLED;
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->status() >= self::INSTALLED;
    }

    /**
     * @return bool
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
     * @return bool
     */
    public function canBeDisabled(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        foreach ($this->dependants() as $module) {
            if ($module->isEnabled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
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
     * @return bool
     */
    public function canBeUninstalled(): bool
    {
        if ($this->isEnabled() || !$this->isInstalled()) {
            return false;
        }

        foreach ($this->dependants() as $module) {
            if ($module->isInstalled()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $property
     * @return mixed
     */
    protected function getCached(string $property)
    {
        if (array_key_exists($property, $this->info)) {
            return $this->info[$property];
        }

        $value = null;

        switch ($property) {
            case 'name':
                $value = $this->package()->getName();
                break;
            case 'version':
                $value = $this->package()->getPrettyVersion();
                break;
            case 'title':
                $value = $this->resolveTitle();
                break;
            case 'description':
                $value = $this->package()->getDescription();
                break;
            case 'dependencies':
                $value = $this->resolveDependencies();
                break;
            case 'dependants':
                $value = $this->resolveDependants();
                break;
            case 'directory':
                $value = $this->resolveDirectory($this->name);
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
        }

        return $this->info[$property] = $value;
    }

    /**
     * Resolve module info
     * @return array
     */
    protected function getModuleInfo(): array
    {
        if ($this->moduleInfo === null) {
            $this->moduleInfo = $this->package()->getExtra()['module'] ?? [];
        }

        return $this->moduleInfo;
    }

    /**
     * Resolve module's title
     *
     * @return string
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
     * Resolve module's directory
     *
     * @param string $name
     * @return string
     */
    protected function resolveDirectory(string $name): string
    {
        $dir = rtrim($this->manager->vendorDir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $dir .= rtrim(implode(DIRECTORY_SEPARATOR, explode('/', $name)), DIRECTORY_SEPARATOR);
        return $dir;
    }

    /**
     * Resolve collector class
     *
     * @return string|null
     */
    protected function resolveCollector(): ?string
    {
        $value = $this->getModuleInfo()['collector'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Resolve installer class
     *
     * @return string|null
     */
    protected function resolveInstaller(): ?string
    {
        $value = $this->getModuleInfo()['installer'] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * Resolve assets dir
     *
     * @return string|null
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

    /**
     * Resolve dependencies
     *
     * @return Module[]
     */
    protected function resolveDependencies(): array
    {
        $dependencies = [];
        $modules = $this->manager->modules();

        foreach ($this->package()->getRequires() as $dependency) {
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
     * @return Module[]
     */
    protected function resolveDependants(): array
    {
        $dependants = [];
        $modules = $this->manager->modules();

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
}
