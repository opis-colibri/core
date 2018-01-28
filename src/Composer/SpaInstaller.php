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

namespace Opis\Colibri\Composer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Symfony\Component\Filesystem\Filesystem;

class SpaInstaller extends LibraryInstaller
{
    /* @var AppInfo */
    protected $appInfo;

    /**
     * Installer constructor.
     *
     * @param AppInfo $appInfo
     * @param IOInterface $io
     * @param Composer $composer
     */
    public function __construct(AppInfo $appInfo, IOInterface $io, Composer $composer)
    {
        $this->appInfo = $appInfo;
        parent::__construct($io, $composer);
    }

    /**
     * @inheritdoc
     */
    public function supports($packageType)
    {
        return $packageType === Application::COMPOSER_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->installAssets($package);
    }

    /**
     * @inheritdoc
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        parent::update($repo, $initial, $target);
        $this->updateAssets($target);
    }

    /**
     * @inheritdoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->uninstallAssets($package);
        parent::uninstall($repo, $package);
    }

    /**
     * @param PackageInterface $package
     */
    public function installAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (!isset($extra['module']['spa'])) {
            return;
        }

        $spa = $extra['module']['spa'];
        $spa += [
            'register' => [],
            'extend' => []
        ];

        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $module_dir = $this->getInstallPath($package);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();
        $apps = &$data['apps'];
        $modules = &$data['modules'];

        $fs = new Filesystem();

        foreach ($spa['register'] as $local_app_name => $app) {
            $app += [
                'webpack' => 'webpack.conf.js',
                'source' => 'spa/' . $local_app_name,
                'dist' => 'dist'
            ];

            // normalize
            foreach (['source', 'webpack', 'dist'] as $item) {
                $app[$item] = implode(DIRECTORY_SEPARATOR, explode('/', trim($item, '/')));
            }

            $source_dir = $module_dir . DIRECTORY_SEPARATOR . $app['source'];
            $webpack_file = $source_dir . DIRECTORY_SEPARATOR . $app['webpack'];
            $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';

            if (!file_exists($package_json) || !file_exists($webpack_file)) {
                continue;
            }

            $pkg = json_decode(file_get_contents($package_json), true);

            if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                continue;
            }

            $app_name = $package_name . '.' . $local_app_name;
            $dir = implode(DIRECTORY_SEPARATOR, [$base_dir, $app_name]);


            $apps[$app_name] = [
                'name' => $local_app_name,
                'webpack' => $webpack_file,
                'dir' => $dir,
                'dist' => $dir . DIRECTORY_SEPARATOR . $app['dist'],
                'owner' => $module,
                'modules' => [$module]
            ];

            $modules[$module][$app_name] = $source_dir;


            $fs->mkdir($dir);
            $fs->copy($webpack_file, $dir . DIRECTORY_SEPARATOR . 'webpack.conf.js');
            $target_package_file = $dir . DIRECTORY_SEPARATOR . 'package.json';
            $package_json_content = (object)[];
            $package_json_content->devDependencies = $pkg['devDependencies'] ?? (object)[];
            file_put_contents($target_package_file, json_encode($package_json_content));

            $cwd = getcwd();
            chdir($dir);
            passthru("yarn install >> /dev/tty");
            passthru("yarn add $source_dir >> /dev/tty");
            chdir($cwd);
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($module === $ext_module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source_dir) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                $source_dir = implode(DIRECTORY_SEPARATOR, explode('/', trim($source_dir, '/')));
                $source_dir = $module_dir . DIRECTORY_SEPARATOR . $source_dir;

                $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';
                if (!file_exists($package_json)) {
                    continue;
                }

                $pkg = json_decode(file_get_contents($package_json), true);
                if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                    continue;
                }

                $app['modules'][] = $module;
                $modules[$module][$app_name] = $source_dir;

                $cwd = getcwd();
                chdir($app['dir']);
                passthru("yarn add $source_dir >> /dev/tty");
                chdir($cwd);
            }
        }

        $this->setSpaData($data);
    }

    /**
     * @param PackageInterface $package
     */
    public function updateAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();
        $spa = $extra['module']['spa'] ?? ['register' => [], 'extend' => []];
        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $module_dir = $this->getInstallPath($package);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();
        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $fs = new Filesystem();

        foreach (array_keys($modules[$module]) as $app_name) {
            $app = &$apps[$app_name];
            if ($app['owner'] === $module) {
                if (!isset($spa['register'][$app['name']])) {
                    //remove spa
                    foreach ($app['modules'] as $module_name) {
                        unset($modules[$module_name][$app_name]);
                    }
                    $fs->remove($app['dir']);
                    $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $app_name);
                    unset($apps[$app_name]);
                } else {
                    unset($spa['register'][$app['name']]);
                    if (!in_array($app_name, $rebuild)) {
                        $rebuild[] = $app_name;
                    }
                }
            } else {
                if (!isset($spa['extend'][$app['owner']][$app['name']])) {
                    $app_modules = $app['modules'];
                    if (false !== $key = array_search($module, $app_modules)) {
                        unset($app_modules[$key]);
                    }
                    $app['modules'] = array_values($app_modules);
                    $cwd = getcwd();
                    chdir($app['dir']);
                    passthru("yarn remove $package_name >> /dev/tty");
                    chdir($cwd);
                } else {
                    unset($spa['extend'][$app['owner']][$app['name']]);
                }

                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        foreach ($spa['register'] as $local_app_name => $app) {
            $app += [
                'webpack' => 'webpack.conf.js',
                'source' => 'spa/' . $local_app_name,
                'dist' => 'dist'
            ];

            // normalize
            foreach (['source', 'webpack', 'dist'] as $item) {
                $app[$item] = implode(DIRECTORY_SEPARATOR, explode('/', trim($item, '/')));
            }

            $source_dir = $module_dir . DIRECTORY_SEPARATOR . $app['source'];
            $webpack_file = $source_dir . DIRECTORY_SEPARATOR . $app['webpack'];
            $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';

            if (!file_exists($package_json) || !file_exists($webpack_file)) {
                continue;
            }

            $pkg = json_decode(file_get_contents($package_json), true);

            if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                continue;
            }

            $app_name = $package_name . '.' . $local_app_name;
            $dir = implode(DIRECTORY_SEPARATOR, [$base_dir, $app_name]);


            $apps[$app_name] = [
                'name' => $local_app_name,
                'webpack' => $webpack_file,
                'dir' => $dir,
                'dist' => $dir . DIRECTORY_SEPARATOR . $app['dist'],
                'owner' => $module,
                'modules' => [$module]
            ];

            $modules[$module][$app_name] = $source_dir;


            $fs->mkdir($dir);
            $fs->copy($webpack_file, $dir . DIRECTORY_SEPARATOR . 'webpack.conf.js');
            $target_package_file = $dir . DIRECTORY_SEPARATOR . 'package.json';
            $package_json_content = (object)[];
            $package_json_content->devDependencies = $pkg['devDependencies'] ?? (object)[];
            file_put_contents($target_package_file, json_encode($package_json_content));

            $cwd = getcwd();
            chdir($dir);
            passthru("yarn install >> /dev/tty");
            passthru("yarn add $source_dir >> /dev/tty");
            chdir($cwd);

            if (!in_array($app_name, $rebuild)) {
                $rebuild[] = $app_name;
            }
        }


        foreach ($spa['extend'] as $ext_module => $ext_app) {
            foreach ($ext_app as $local_app_name => $source_dir) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                $source_dir = implode(DIRECTORY_SEPARATOR, explode('/', trim($source_dir, '/')));
                $source_dir = $module_dir . DIRECTORY_SEPARATOR . $source_dir;

                $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';
                if (!file_exists($package_json)) {
                    continue;
                }

                $pkg = json_decode(file_get_contents($package_json), true);
                if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                    continue;
                }

                $app['modules'][] = $module;
                $modules[$module][$app_name] = $source_dir;

                $cwd = getcwd();
                chdir($app['dir']);
                passthru("yarn add $source_dir >> /dev/tty");
                chdir($cwd);

                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        $this->setSpaData($data);
    }

    /**
     * @param PackageInterface $package
     */
    public function uninstallAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (!isset($extra['module']['spa'])) {
            return;
        }

        $spa = $extra['module']['spa'] + ['register' => [], 'extend' => []];
        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();

        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $fs = new Filesystem();

        foreach ($spa['register'] as $local_app_name => $app_data) {
            $app_name = $package_name . '.' . $local_app_name;
            if (!isset($apps[$app_name])) {
                continue;
            }
            $fs->remove($base_dir . DIRECTORY_SEPARATOR . $app_name);
            $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $app_name);
            $app = $apps[$app_name];
            foreach ($app['modules'] as $app_module) {
                unset($modules[$app_module][$app_name]);
            }
            unset($apps[$app_name]);
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($ext_module === $module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                if (!in_array($module, $app['modules'])) {
                    continue;
                }
                if (false !== $key = array_search($module, $app['modules'])) {
                    unset($app['modules'][$key]);
                }

                $cwd = getcwd();
                chdir($app['dir']);
                passthru("yarn remove $package_name >> /dev/tty");
                chdir($cwd);

                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        $this->setSpaData($data);
    }

    private function getSpaData(): array
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->appInfo->writableDir(), 'spa', 'data.json']);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return [
            'apps' => [],
            'modules' => [],
            'rebuild' => [],
        ];
    }

    private function setSpaData(array $data)
    {
        $file = $file = implode(DIRECTORY_SEPARATOR, [$this->appInfo->writableDir(), 'spa', 'data.json']);
        file_put_contents($file, json_encode($data));
    }
}