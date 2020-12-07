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

use RuntimeException;
use Composer\Package\CompletePackageInterface;
use Opis\Colibri\{Collector, Installer, Attributes\Module as ModuleAttribute};

class Module
{
    const UNINSTALLED = 0;
    const INSTALLED = 1;
    const ENABLED = 2;

    const TYPE = 'opis-colibri-module';

    protected ModuleManager $manager;
    protected string $name;
    protected ?CompletePackageInterface $package = null;
    protected ?Collector $collector = null;
    protected ?Installer $installer = null;
    protected string $title = '';
    protected ?string $description = null;
    protected ?string $assets = null;
    protected ?string $directory = null;
    protected ?array $dependencies = null;
    protected ?array $dependants = null;
    protected bool $loaded = false;

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

    public function package(): CompletePackageInterface
    {
        if ($this->package === null) {
            $this->package = $this->manager->package($this->name);
            if ($this->package === null) {
                throw new RuntimeException("Package '{$this->name}' cannot be resolved");
            }
        }

        return $this->package;
    }

    public function status(): int
    {
        return $this->manager->getStatus($this->name);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->package()->getPrettyVersion();
    }

    public function title(): string
    {
        return $this->load()->title;
    }

    public function description(): ?string
    {
        return $this->load()->description;
    }

    public function directory(): string
    {
        if ($this->directory === null) {
            $this->directory = rtrim($this->manager->vendorDir(), '/') . '/' . $this->name;
        }

        return $this->directory;
    }

    public function collector(): Collector
    {
        return $this->load()->collector;
    }

    public function installer(): ?Installer
    {
        return $this->load()->installer;
    }

    public function assets(): ?string
    {
        return $this->load()->assets;
    }

    /**
     * @return Module[]
     */
    public function dependencies(): array
    {
        if ($this->dependencies === null) {
            $this->dependencies = $this->resolveDependencies();
        }

        return $this->dependencies;
    }

    /**
     * @return Module[]
     */
    public function dependants(): array
    {
        if ($this->dependants === null) {
            $this->dependants = $this->resolveDependants();
        }

        return $this->dependants;
    }

    public function exists(): bool
    {
        try {
            $this->load();
        } catch (RuntimeException) {
            return false;
        }

        return true;
    }

    public function isEnabled(): bool
    {
        return $this->status() === self::ENABLED;
    }

    public function isInstalled(): bool
    {
        return $this->status() >= self::INSTALLED;
    }

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

    protected function load(): static
    {
        if ($this->loaded) {
            return $this;
        }

        $collector_class = $this->package()->getExtra()['collector'] ?? throw new RuntimeException('No collector specified');

        if (!class_exists($collector_class) || !is_subclass_of($collector_class, Collector::class, true)) {
            throw new RuntimeException("Invalid module '{$this->name}");
        }

        $this->collector = new $collector_class();

        $reflection = new \ReflectionObject($this->collector);
        $attr = $reflection->getAttributes(ModuleAttribute::class)[0] ?? null;
        $args = $attr?->getArguments() ?? [];

        $this->title = $this->resolveTitle($args);
        $this->description = $this->resolveDescription($args);
        $this->installer = $this->resolveInstaller($args);
        $this->assets = $this->resolveAssets($args);

        return $this;
    }

    protected function resolveTitle(array $args): string
    {
        if (null !== $title = $args[0] ?? $args['title'] ?? null) {
            return $title;
        }

        $name = substr($this->name, strpos($this->name, '/') + 1);
        $name = array_map(static function ($value) {
            return strtolower($value);
        }, explode('-', $name));

        return ucfirst(implode(' ', $name));
    }

    protected function resolveDescription(array $args): ?string
    {
        if (null !== $description = $args[1] ?? $args['description'] ?? null) {
            return $description;
        }

        return $this->package()->getDescription();
    }

    protected function resolveInstaller(array $args): ?Installer
    {
        if (null === $installer_class = $args[2] ?? $args['installer'] ?? null) {
            return null;
        }

        if (!class_exists($installer_class) || !is_subclass_of($installer_class, Installer::class)) {
            return null;
        }

        return new $installer_class($this);
    }

    protected function resolveAssets(array $args): ?string
    {
        if (null === $assets = $args[3] ?? $args['assets'] ?? null) {
            return null;
        }

        $directory = $this->directory() . '/' . trim($this->removeDots($assets), '/');

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

    protected function removeDots(string $path): string
    {
        $root = ($path[0] === '/') ? '/' : '';

        $segments = explode('/', trim($path, '/'));

        $return = [];

        foreach($segments as $segment){
            if (($segment == '.') || empty($segment)) {
                continue;
            }
            if ($segment == '..') {
                array_pop($return);
            } else {
                array_push($return, $segment);
            }
        }

        return $root . implode('/', $return);
    }
}
