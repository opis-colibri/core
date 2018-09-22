<?php
/* ============================================================================
 * Copyright © 2016-2018 ZINDEX™ CONCEPT SRL
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

namespace Opis\Colibri\Testing\Builders;

use stdClass;
use Opis\Colibri\Module;
use Opis\Colibri\Core\AppInfo;
use Opis\Colibri\Testing\{Application, InstalledAppInfo};

class ApplicationBuilder
{
    /** @var string */
    protected $baseRootDir;

    /** @var string */
    protected $rootDir;

    /** @var null|string */
    protected $vendorDir;

    /** @var BootstrapBuilder */
    protected $builder = null;

    /** @var array */
    protected $autoload = [];

    /** @var int[] */
    protected $modules = [];

    /** @var stdClass[] */
    protected $pathModules = [];

    /** @var array[] */
    protected $createdModules = [];

    /** @var string[] */
    protected $dependencies = [];

    /** @var array */
    protected $mainComposerContent = ['type' => 'library'];

    /**
     * AppBuilder constructor.
     * @param string $vendorDir
     * @param null|string $rootDir
     * @param null|BootstrapBuilder $boot
     */
    public function __construct(string $vendorDir, ?string $rootDir = null, ?BootstrapBuilder $boot = null)
    {
        $this->baseRootDir = sys_get_temp_dir();
        $this->rootDir = $rootDir;
        $this->vendorDir = $vendorDir;
        $this->builder = $boot ?? new BootstrapBuilder();
    }

    /**
     * @return Application
     */
    public function build(): Application
    {
        $rootDir = $this->rootDir ?? $this->createRootDir();
        $info = $this->createAppInfo($this->vendorDir, $rootDir);

        if ($this->createdModules) {
            $this->handleCreatedModules($rootDir, $this->createdModules);
        }

        $this->builder->getConfigDriver()->write('modules', $this->modules);

        $bootstrap = $this->builder->build();

        $app = new Application(
            $bootstrap,
            $info,
            $this->createInstalledDataFile($info),
            $this->autoload ? $this->createAutoloader($this->autoload) : null
        );

        $app->bootstrap();

        if ($this->dependencies) {
            $this->resolveDependencies($app, $this->dependencies);
        }

        return $app;
    }

    /**
     * @param Application $app
     * @param bool $hard
     * @return ApplicationBuilder
     */
    public function destroy(Application $app, bool $hard = true): self
    {
        $info = $app->getAppInfo();

        $app->destroy($hard);

        if (!$hard) {
            return $this;
        }

        $dir = $info->rootDir();

        if ($dir && is_dir($dir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var \SplFileInfo $fileInfo */
            foreach ($files as $fileInfo) {
                if ($fileInfo->isDir()) {
                    rmdir($fileInfo->getRealPath());
                } else {
                    unlink($fileInfo->getRealPath());
                }
            }

            rmdir($dir);
        }

        return $this;
    }

    /**
     * @return BootstrapBuilder
     */
    public function getBootstrapBuilder(): BootstrapBuilder
    {
        return $this->builder;
    }

    /**
     * @param BootstrapBuilder|null $builder
     * @return ApplicationBuilder
     */
    public function setBootstrapBuilder(BootstrapBuilder $builder): self
    {
        $this->builder = $builder;
        return $this;
    }

    /**
     * @param array $content
     * @return ApplicationBuilder
     */
    public function setMainComposerContent(array $content): self
    {
        $this->mainComposerContent = $content;
        return $this;
    }

    /**
     * @return array
     */
    public function getMainComposerContent(): array
    {
        return $this->mainComposerContent;
    }

    /**
     * @param string $path
     * @param string $ns
     * @return ApplicationBuilder
     */
    public function addAutoloadPath(string $path, string $ns): self
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $ns = trim($ns, '\\') . '\\';

        $this->autoload[$path] = $ns;

        return $this;
    }

    /**
     * @param string[] $paths
     * @param string $prefix
     * @return ApplicationBuilder
     */
    public function addAutoloadPaths(array $paths, string $prefix = ''): self
    {
        if ($prefix !== '') {
            $prefix = rtrim($prefix, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }

        foreach ($paths as $path => $ns) {
            if ($prefix !== '') {
                $path = ltrim($path, DIRECTORY_SEPARATOR);
            }
            $this->addAutoloadPath($prefix . $path, $ns);
        }

        return $this;
    }

    /**
     * @param string ...$dependencies
     * @return ApplicationBuilder
     */
    public function addDependencies(string ...$dependencies): self
    {
        $this->dependencies = array_unique(array_merge($this->dependencies, $dependencies));
        return $this;
    }

    /**
     * @param string ...$dependencies
     * @return ApplicationBuilder
     */
    public function removeDependencies(string ...$dependencies): self
    {
        $this->dependencies = array_diff($this->dependencies, $dependencies);
        return $this;
    }

    /**
     * @return string[]
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string[] $info
     * @return ApplicationBuilder
     */
    public function createUninstalledTestModule(string $name, string $ns, string $path, array $info = []): self
    {
        return $this->createTestModule($name, $ns, $path, $info, Module::UNINSTALLED);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string[] $info
     * @return ApplicationBuilder
     */
    public function createInstalledTestModule(string $name, string $ns, string $path, array $info = []): self
    {
        return $this->createTestModule($name, $ns, $path, $info, Module::INSTALLED);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string[] $info
     * @return ApplicationBuilder
     */
    public function createEnabledTestModule(string $name, string $ns, string $path, array $info = []): self
    {
        return $this->createTestModule($name, $ns, $path, $info, Module::ENABLED);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string[] $info
     * @param int $status
     * @return ApplicationBuilder
     */
    protected function createTestModule(string $name, string $ns, string $path, array $info, int $status): self
    {
        $this->createdModules[$name] = [
            'name' => $name,
            'ns' => $ns,
            'path' => $path,
            'info' => $info,
            'status' => $status,
        ];
        return $this;
    }

    /**
     * @param string ...$modules
     * @return ApplicationBuilder
     */
    public function markModulesAsUninstalled(string ...$modules): self
    {
        foreach ($modules as $module) {
            $this->modules[$module] = Module::UNINSTALLED;
        }
        return $this;
    }

    /**
     * @param string ...$modules
     * @return ApplicationBuilder
     */
    public function markModulesAsInstalled(string ...$modules): self
    {
        foreach ($modules as $module) {
            $this->modules[$module] = Module::INSTALLED;
        }
        return $this;
    }

    /**
     * @param string ...$modules
     * @return ApplicationBuilder
     */
    public function markModulesAsEnabled(string ...$modules): self
    {
        foreach ($modules as $module) {
            $this->modules[$module] = Module::ENABLED;
        }
        return $this;
    }

    /**
     * @param string $dir
     * @param bool $autoload
     * @return bool
     */
    public function addUninstalledModuleFromPath(string $dir, bool $autoload = true): bool
    {
        return $this->addModuleFromPath($dir, Module::UNINSTALLED, $autoload);
    }

    /**
     * @param string $dir
     * @param bool $autoload
     * @return bool
     */
    public function addInstalledModuleFromPath(string $dir, bool $autoload = true): bool
    {
        return $this->addModuleFromPath($dir, Module::INSTALLED, $autoload);
    }

    /**
     * @param string $dir
     * @param bool $autoload
     * @return bool
     */
    public function addEnabledModuleFromPath(string $dir, bool $autoload = true): bool
    {
        return $this->addModuleFromPath($dir, Module::ENABLED, $autoload);
    }

    /**
     * @param string $dir
     * @param int $status
     * @param bool $autoload
     * @return bool
     */
    protected function addModuleFromPath(string $dir, int $status, bool $autoload = true): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $dir = realpath($dir);

        $dir = rtrim($dir, DIRECTORY_SEPARATOR);
        $file = $dir . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($file)) {
            return false;
        }

        $data = json_decode(file_get_contents($file), false);
        if (!($data instanceof stdClass) || !isset($data->name)) {
            return false;
        }

        // Add to modules list
        $this->modules[$data->name] = $status;

        if (!isset($data->version)) {
            $data->version = 'dev-master';
        }

        if (!isset($data->version_normalized)) {
            $data->version_normalized = '9999999-dev';
        }

        if (!isset($data->{'notification-url'})) {
            $data->{'notification-url'} = 'https://packagist.org/downloads/';
        }

        if (!isset($data->time)) {
            $data->time = (new \DateTime())->format(DATE_ATOM);
        }

        $this->pathModules[$data->name] = $data;

        if ($autoload && isset($data->autoload)) {
            if (isset($data->autoload->{'psr-4'})) {
                foreach ($data->autoload->{'psr-4'} as $ns => $path) {
                    if ($path && $path[0] !== '/') {
                        $path = $dir . DIRECTORY_SEPARATOR . $path;
                    }
                    $this->addAutoloadPath($path, $ns);
                }
            }
        }

        return true;
    }

    /**
     * @param array $map
     * @return callable
     */
    protected function createAutoloader(array $map): callable
    {
        return function (string $class) use ($map): bool {
            $class = ltrim($class, '\\');

            foreach ($map as $dir => $namespace) {
                if (strpos($class, $namespace) === 0) {
                    $class = substr($class, strlen($namespace));
                    $path = '';
                    if (($pos = strripos($class, '\\')) !== false) {
                        $path = str_replace('\\', '/', substr($class, 0, $pos)) . '/';
                        $class = substr($class, $pos + 1);
                    }
                    $path .= str_replace('_', '/', $class) . '.php';
                    $path = $dir . $path;
                    if (is_file($path)) {
                        /** @noinspection PhpIncludeInspection */
                        include $path;
                        return true;
                    }
                    return false;
                }
            }

            return false;
        };
    }

    /**
     * @param AppInfo $info
     * @return string
     */
    protected function createInstalledDataFile(AppInfo $info): string
    {
        $list = array_values($this->pathModules);

        $file = $info->vendorDir() . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'installed.json';
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file));
            if (is_array($data)) {
                $list = array_merge($list, $data);
            }
        }

        $file = $info->rootDir() . DIRECTORY_SEPARATOR . 'installed.json';

        file_put_contents($file, json_encode($list, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $file;
    }

    /**
     * @return string
     */
    protected function createRootDir(): string
    {
        do {
            $dir = $this->baseRootDir . DIRECTORY_SEPARATOR . uniqid('opis_app_');
        } while (is_dir($dir));

        mkdir($dir, 0777, true);

        return $dir;
    }

    /**
     * @param string $vendorDir
     * @param string $rootDir
     * @return AppInfo
     */
    protected function createAppInfo(string $vendorDir, string $rootDir): AppInfo
    {
        $rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);

        $vendorDir = rtrim($vendorDir, DIRECTORY_SEPARATOR);

        file_put_contents($rootDir . DIRECTORY_SEPARATOR . 'composer.json',
            json_encode((object) $this->getMainComposerContent(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        file_put_contents($rootDir . DIRECTORY_SEPARATOR . 'package.json', '{}');

        foreach (['public', 'assets', 'storage'] as $dir) {
            if (!is_dir($rootDir . DIRECTORY_SEPARATOR . $dir)) {
                mkdir($rootDir . DIRECTORY_SEPARATOR . $dir, 0777, true);
            }
        }

        // Create temp dir
        if (!is_dir($rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp')) {
            mkdir($rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'tmp', 0777, true);
        }

        return new InstalledAppInfo($rootDir, [
            AppInfo::VENDOR_DIR => $vendorDir,
            AppInfo::PUBLIC_DIR => 'public',
            AppInfo::ASSETS_DIR => 'assets',
            AppInfo::WRITABLE_DIR => 'storage',
            AppInfo::TEMP_DIR => 'tmp',
        ]);
    }

    /**
     * @param Application $app
     * @param string[] $dependencies
     */
    protected function resolveDependencies(Application $app, array $dependencies)
    {
        foreach ($dependencies as $dependency) {
            $modules = $app->getModules();

            if (!isset($modules[$dependency])) {
                continue;
            }

            $module = $modules[$dependency];

            if (!$module->isInstalled()) {
                $app->install($module);
            }

            if (!$module->isEnabled()) {
                $app->enable($module);
            }
        }
    }

    /**
     * @param string $rootDir
     * @param array $modules
     */
    protected function handleCreatedModules(string $rootDir, array $modules)
    {
        $rootDir = realpath($rootDir);
        $rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'modules';
        if (!is_dir($rootDir)) {
            mkdir($rootDir, 0777, true);
        }

        foreach ($modules as $module) {
            $ns = trim($module['ns'], '\\') . '\\';
            $path = rtrim($module['path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if (!is_dir($path)) {
                continue;
            }

            $data = [
                'name' => $module['name'],
                'type' => AppInfo::MODULE_TYPE,
                'autoload' => [
                    'psr-4' => [
                        $ns => $path
                    ]
                ],
                'extra' => [
                    'module' => $module['info'] + [
                        'title' => 'Autogenerated module ' . $module['name'],
                    ]
                ]
            ];

            $dir = $rootDir . DIRECTORY_SEPARATOR . str_replace(DIRECTORY_SEPARATOR, '-', $module['name']);
            mkdir($dir);

            $file = $dir . DIRECTORY_SEPARATOR . 'composer.json';
            file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            $this->addModuleFromPath($dir, $module['status'], true);
        }
    }
}