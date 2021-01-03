<?php
/* ============================================================================
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

use Throwable, ArrayObject, Generator;
use Composer\Json\JsonFile;
use Composer\Package\CompletePackageInterface;
use Composer\Repository\InstalledFilesystemRepository;
use Opis\Colibri\Config\ConfigStore;

class ModuleManager
{
    const CONFIG_NAME = 'modules';

    protected string $vendorDir;

    /** @var callable */
    protected $config;

    /** @var null|CompletePackageInterface[] */
    protected ?array $packages = null;

    /** @var null|Module[] */
    protected ?array $modules = null;

    /**
     * ModuleManager constructor.
     * @param string $vendorDir
     * @param callable $config
     */
    public function __construct(string $vendorDir, callable $config)
    {
        $this->vendorDir = rtrim($vendorDir, DIRECTORY_SEPARATOR);
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function vendorDir(): string
    {
        return $this->vendorDir;
    }

    /**
     * @param string $name
     * @return Module
     */
    public function module(string $name): Module
    {
        return $this->modules[$name] ?? new Module($this, $name);
    }

    /**
     * @param bool $clear
     * @return Module[]
     */
    public function modules(bool $clear = false): array
    {
        if (!$clear && $this->modules !== null) {
            return $this->modules;
        }

        $modules = [];

        foreach ($this->packages($clear) as $module => $package) {
            $modules[$module] = new Module($this, $module, $package);
        }

        return $this->modules = $modules;
    }

    /**
     * @param string $name
     * @return CompletePackageInterface|null
     */
    public function package(string $name): ?CompletePackageInterface
    {
        return $this->packages()[$name] ?? null;
    }

    /**
     * @param bool $clear
     * @return CompletePackageInterface[]
     */
    public function packages(bool $clear = false): array
    {
        if (!$clear && $this->packages !== null) {
            return $this->packages;
        }

        $packages = [];

        $repository = new InstalledFilesystemRepository(new JsonFile($this->installedJsonFile()));

        foreach ($repository->getCanonicalPackages() as $package) {
            if (!$package instanceof CompletePackageInterface || $package->getType() !== Module::TYPE) {
                continue;
            }
            $packages[$package->getName()] = $package;
        }

        $this->packages = $packages;
        $this->modules = null;

        return $this->packages;
    }

    /**
     * @param Module|string $module
     * @param int $status
     * @return bool
     */
    public function setStatus($module, int $status): bool
    {
        if ($module instanceof Module) {
            $module = $module->name();
        } elseif (!is_string($module)) {
            return false;
        }

        return $this->config()->write([self::CONFIG_NAME, $module], $status);
    }

    /**
     * @param Module|string $module
     * @return int
     */
    public function getStatus($module): int
    {
        if ($module instanceof Module) {
            $module = $module->name();
        } elseif (!is_string($module)) {
            return Module::UNINSTALLED;
        }

        return $this->config()->read([self::CONFIG_NAME, $module], Module::UNINSTALLED);
    }

    /**
     * @return int[]
     */
    public function getStatusList(): array
    {
        return $this->config()->read(self::CONFIG_NAME, []);
    }

    /**
     * @param array $list
     * @return bool
     */
    public function setStatusList(array $list): bool
    {
        return $this->config()->write(self::CONFIG_NAME, $list);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function recursiveDependencies(Module $module, ?callable $filter = null): array
    {
        return $this->filteredDeps($module, static fn (Module $module): array => $module->dependencies(), $filter);
    }

    /**
     * @param Module $module
     * @param callable|null $filter
     * @return Module[]
     */
    public function recursiveDependants(Module $module, ?callable $filter = null): array
    {
        return $this->filteredDeps($module, static fn (Module $module): array => $module->dependants(), $filter);
    }

    /**
     * @param Module $module
     * @param callable $action
     * @param callable|null $callback
     * @return bool
     */
    public function install(Module $module, callable $action, ?callable $callback = null): bool
    {
        if (!$module->canBeInstalled()) {
            return false;
        }

        return $this->changeStatus($module, Module::INSTALLED, $action, $callback);
    }

    /**
     * @param Module $module
     * @param callable $action
     * @param callable|null $callback
     * @return bool
     */
    public function enable(Module $module, callable $action, ?callable $callback = null): bool
    {
        if (!$module->canBeEnabled()) {
            return false;
        }

        return $this->changeStatus($module, Module::ENABLED, $action, $callback);
    }

    /**
     * @param Module $module
     * @param callable $action
     * @param callable|null $callback
     * @return bool
     */
    public function disable(Module $module, callable $action, ?callable $callback = null): bool
    {
        if (!$module->canBeDisabled()) {
            return false;
        }

        return $this->changeStatus($module, Module::INSTALLED, $action, $callback);
    }

    /**
     * @param Module $module
     * @param callable $action
     * @param callable|null $callback
     * @return bool
     */
    public function uninstall(Module $module, callable $action, ?callable $callback = null): bool
    {
        if (!$module->canBeUninstalled()) {
            return false;
        }

        return $this->changeStatus($module, Module::UNINSTALLED, $action, $callback);
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $this->modules = $this->packages = null;
    }

    /**
     * @param Module $module
     * @param int $status
     * @param callable $action
     * @param callable|null $callback
     * @return bool
     */
    protected function changeStatus(Module $module, int $status, callable $action, ?callable $callback = null): bool
    {
        $fp = fopen(__FILE__, 'r');
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            return false;
        }

        $success = false;

        try {
            if (!$action($module)) {
                return false;
            }

            $success = $this->setStatus($module, $status);

            if ($callback) {
                $callback($module);
            }
        } catch (Throwable $e) {
            return $success;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return $success;
    }

    /**
     * @return string
     */
    protected function installedJsonFile(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->vendorDir(),
            'composer',
            'installed.json'
        ]);
    }

    /**
     * @param Module $module
     * @param callable $deps
     * @param callable|null $filter
     * @return Module[]
     */
    protected function filteredDeps(Module $module, callable $deps, ?callable $filter = null): array
    {
        $list = [];

        $checked = new ArrayObject([
            $module->name() => true,
        ]);

        foreach ($this->deps($module, $deps, $checked) as $item) {
            if ($filter === null || $filter($item)) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @param Module $module
     * @param callable $deps
     * @param ArrayObject $checked
     * @return Generator|Module[]
     */
    protected function deps(Module $module, callable $deps, ArrayObject $checked): Generator {
        /** @var Module $dep */
        foreach ($deps($module) as $dep) {
            $name = $dep->name();
            if (isset($checked[$name])) {
                continue;
            }
            $checked[$name] = true;
            yield from $this->deps($dep, $deps, $checked);
            yield $dep;
        }
    }

    /**
     * @return ConfigStore
     */
    protected function config(): ConfigStore
    {
        return ($this->config)();
    }
}