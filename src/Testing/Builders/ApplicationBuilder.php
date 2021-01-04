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

namespace Opis\Colibri\Testing\Builders;

use stdClass;
use SplFileInfo;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Opis\Colibri\{Module, ApplicationInfo, ModuleManager};
use Opis\Colibri\Testing\Application;

class ApplicationBuilder
{

    protected string $baseRootDir;

    protected ?string $rootDir = null;

    protected ?string $vendorDir = null;

    protected ApplicationInitializerBuilder $builder;

    protected array $autoload = [];

    protected array $modules = [];

    /** @var stdClass[] */
    protected array $pathModules = [];

    protected array $createdModules = [];

    /** @var string[] */
    protected array $dependencies = [];

    protected array $mainComposerContent = ['type' => 'library'];

    protected array $env = [];

    /**
     * AppBuilder constructor.
     * @param string $vendorDir
     * @param null|string $rootDir
     * @param null|ApplicationInitializerBuilder $boot
     */
    public function __construct(string $vendorDir, ?string $rootDir = null, ?ApplicationInitializerBuilder $boot = null)
    {
        $this->baseRootDir = sys_get_temp_dir();
        $this->rootDir = $rootDir;
        $this->vendorDir = $vendorDir;
        $this->builder = $boot ?? new ApplicationInitializerBuilder();
    }

    /**
     * @return Application
     */
    public function build(): Application
    {
        $rootDir = $this->rootDir ?? $this->createRootDir();
        $info = $this->createAppInfo($this->vendorDir, $rootDir, $this->dependencies, $this->env);

        if ($this->createdModules) {
            $this->handleCreatedModules($rootDir, $this->createdModules);
        }

        $this->builder->getConfigDriver()->write(ModuleManager::CONFIG_NAME, $this->modules);

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
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            /** @var SplFileInfo $fileInfo */
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
     * @param array $env
     * @return self
     */
    public function setEnv(array $env): self
    {
        $this->env = $env;
        return $this;
    }

    /**
     * @return array
     */
    public function getEnv(): array
    {
        return $this->env;
    }

    /**
     * @return ApplicationInitializerBuilder
     */
    public function getBootstrapBuilder(): ApplicationInitializerBuilder
    {
        return $this->builder;
    }

    /**
     * @param ApplicationInitializerBuilder $builder
     * @return ApplicationBuilder
     */
    public function setBootstrapBuilder(ApplicationInitializerBuilder $builder): self
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
        $path = rtrim($path, '/') . '/';
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
            $prefix = rtrim($prefix, '/') . '/';
        }

        foreach ($paths as $path => $ns) {
            if ($prefix !== '') {
                $path = ltrim($path, '/');
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
     * @param string $collector
     * @param string[] $require
     * @return ApplicationBuilder
     */
    public function createUninstalledTestModule(
        string $name,
        string $ns,
        string $path,
        string $collector,
        array $require = []
    ): self {
        return $this->createTestModule($name, $ns, $path, $collector, Module::UNINSTALLED, $require);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string $collector
     * @param string[] $require
     * @return ApplicationBuilder
     */
    public function createInstalledTestModule(
        string $name,
        string $ns,
        string $path,
        string $collector,
        array $require = []
    ): self {
        return $this->createTestModule($name, $ns, $path, $collector, Module::INSTALLED, $require);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string $collector
     * @param string[] $require
     * @return ApplicationBuilder
     */
    public function createEnabledTestModule(
        string $name,
        string $ns,
        string $path,
        string $collector,
        array $require = []
    ): self {
        return $this->createTestModule($name, $ns, $path, $collector, Module::ENABLED, $require);
    }

    /**
     * @param string $name
     * @param string $ns
     * @param string $path
     * @param string[] $info
     * @param int $status
     * @param null|string[] $require
     * @return ApplicationBuilder
     */
    protected function createTestModule(
        string $name,
        string $ns,
        string $path,
        string $collector,
        int $status,
        ?array $require = null
    ): self {
        $this->createdModules[$name] = [
            'name' => $name,
            'ns' => $ns,
            'path' => $path,
            'collector' => $collector,
            'status' => $status,
            'require' => $require ?: null,
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

        $dir = rtrim($dir, '/');
        $file = $dir . '/composer.json';

        if (!is_file($file)) {
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
            $data->time = date_create()->format(DATE_ATOM);
        }

        $this->pathModules[$data->name] = $data;

        if ($autoload && isset($data->autoload)) {
            if (isset($data->autoload->{'psr-4'})) {
                foreach ($data->autoload->{'psr-4'} as $ns => $path) {
                    if ($path && $path[0] !== '/') {
                        $path = $dir . '/' . $path;
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
        return static function (string $class) use ($map): bool {
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
     * @param ApplicationInfo $info
     * @return string
     */
    protected function createInstalledDataFile(ApplicationInfo $info): string
    {
        $list = array_values($this->pathModules);

        $file = $info->vendorDir() . '/composer/installed.json';
        if (is_file($file)) {
            $data = json_decode(file_get_contents($file));
            if (is_array($data)) {
                $list = array_merge($list, $data);
            }
        }

        $file = $info->rootDir() . '/installed.json';

        file_put_contents($file, json_encode($list, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $file;
    }

    /**
     * @return string
     */
    protected function createRootDir(): string
    {
        do {
            $dir = $this->baseRootDir . '/' . uniqid('opis_app_');
        } while (is_dir($dir));

        mkdir($dir, 0777, true);

        return $dir;
    }

    /**
     * @param string $vendorDir
     * @param string $rootDir
     * @param array $dependencies
     * @param array $env
     * @return ApplicationInfo
     */
    protected function createAppInfo(string $vendorDir, string $rootDir, array $dependencies, array $env = []): ApplicationInfo
    {
        $rootDir = rtrim($rootDir, '/');

        $vendorDir = rtrim($vendorDir, '/');

        $composer = $this->getMainComposerContent();

        if ($dependencies) {
            if (!isset($composer['require'])) {
                $composer['require'] = [];
            }
            foreach ($dependencies as $name) {
                if (!isset($composer['require'][$name])) {
                    $composer['require'][$name] = 'dev-master';
                }
            }
        }

        file_put_contents($rootDir . '/composer.json',
            json_encode((object)$composer, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        unset($composer);

        file_put_contents($rootDir . '/package.json', '{}');

        // Create public dir, assets dir, storage & temp dir
        foreach (['public', 'assets', 'storage', 'storage/tmp'] as $dir) {
            if (!is_dir($rootDir . '/' . $dir)) {
                mkdir($rootDir . '/' . $dir, 0777, true);
            }
        }

        // Write env variables
        file_put_contents(
            $rootDir . '/storage/env.php',
            '<?php return ' . var_export($env, true) . ';'
        );

        return new ApplicationInfo($rootDir, [
            ApplicationInfo::VENDOR_DIR => $vendorDir,
            ApplicationInfo::PUBLIC_DIR => 'public',
            ApplicationInfo::ASSETS_DIR => 'assets',
            ApplicationInfo::WRITABLE_DIR => 'storage',
            ApplicationInfo::TEMP_DIR => 'tmp',
            ApplicationInfo::ENV_FILE => 'env.php',
        ]);
    }

    /**
     * @param Application $app
     * @param string[] $dependencies
     */
    protected function resolveDependencies(Application $app, array $dependencies): void
    {
        $modules = $app->getModules();

        foreach ($dependencies as $dependency) {
            if (!isset($modules[$dependency])) {
                continue;
            }

            $module = $modules[$dependency];

            if (!$module->isEnabled()) {
                $app->enable($module, true, true);
            }
        }
    }

    /**
     * @param string $rootDir
     * @param array $modules
     */
    protected function handleCreatedModules(string $rootDir, array $modules): void
    {
        $rootDir = realpath($rootDir);
        $rootDir = rtrim($rootDir, '/') . '/modules';
        if (!is_dir($rootDir)) {
            mkdir($rootDir, 0777, true);
        }

        foreach ($modules as $module) {
            $ns = trim($module['ns'], '\\') . '\\';
            $path = rtrim($module['path'], '/') . '/';
            if (!is_dir($path)) {
                continue;
            }

            $data = [
                'name' => $module['name'],
                'type' => Module::TYPE,
                'autoload' => [
                    'psr-4' => [
                        $ns => $path,
                    ],
                ],
                'extra' => [
                    'collector' => $module['collector'],
                ],
            ];

            if ($module['require']) {
                $data['require'] = [];
                foreach ($module['require'] as $name => $version) {
                    if (is_int($name)) {
                        $data['require'][$version] = 'dev-master';
                    } else {
                        $data['require'][$name] = $version ?: 'dev-master';
                    }
                }
            }

            $dir = $rootDir . '/' . str_replace('/', '-', $module['name']);
            mkdir($dir);

            $file = $dir . '/composer.json';
            file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            $this->addModuleFromPath($dir, $module['status'], true);
        }
    }
}