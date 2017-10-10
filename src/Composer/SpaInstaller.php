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
        $this->uninstallAssets($initial);
        parent::update($repo, $initial, $target);
        $this->installAssets($target);
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

        if(!isset($extra['module']['spa'])){
            return;
        }

        $spa = $extra['module']['spa'];
        $module_dir = $this->getInstallPath($package);
        $fs = new Filesystem();
        $base_dir = $this->appInfo->writableDir() . '/spa';
        $data = $this->getSpaData();

        $apps = &$data['apps'];
        $modules = &$data['modules'];

        foreach ($spa as $app => $settings){
            if(!isset($settings['dir']) || !isset($settings['entry'])){
                continue;
            }

            $module = $package->getName();
            $settings += [
                'name' => str_replace('/', '.', $module)
            ];

            $dir = $module_dir . '/' . $settings['dir'];

            if(!is_dir($dir) ||
                !file_exists($dir . '/' . $settings['entry']) ||
                !file_exists($dir . '/package.json')){
                continue;
            }

            $app_dir = $base_dir . '/' . $app;

            if(!is_dir($app_dir)){
                $fs->mkdir($app_dir);
                file_put_contents($app_dir . '/package.json', '{}');
            }

            if(!isset($modules[$module])){
                $modules[$module][$app] = [
                    'name' => $settings['name'],
                    'dir' => $dir,
                    'entry' => $dir . '/' . $settings['entry']
                ];
            }

            if(!isset($apps[$app])){
                $apps[$app] = [
                    'name' => $app,
                    'dir' => $app_dir,
                    'entries' => [],
                    'modules' => [],
                ];
            }

            if(!in_array($module, $apps[$app]['modules'])){
                $apps[$app]['modules'][] = $module;
            }

            $cwd = getcwd();

            chdir($app_dir);
            passthru("npm install $dir --save --loglevel=error >> /dev/tty");
            chdir($cwd);
        }

        $this->setSpaData($data);
    }

    /**
     * @param PackageInterface $package
     */
    public function uninstallAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if(!isset($extra['module']['spa'])){
            return;
        }

        $spa = $extra['module']['spa'];
        $module = $package->getName();
        $base_dir = $this->appInfo->writableDir() . '/spa';
        $data = $this->getSpaData();

        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        foreach ($spa as $app => $settings){
            $settings += [
                'name' => str_replace('/', '.', $module)
            ];

            $entry = $settings['name'];
            $app_dir = $base_dir . '/' . $app;

            unset($modules[$module]);

            if(isset($apps[$app])){
                if(in_array($module, $apps[$app]['modules'])){
                    $key = array_search($module, $apps[$app]['modules']);
                    unset($apps[$app]['modules'][$key]);
                    if(in_array($module, $apps[$app]['entries'])){
                        if(!in_array($module, $rebuild)){
                            $rebuild[] = $module;
                        }
                    }
                }
            }

            $cwd = getcwd();

            chdir($app_dir);
            passthru("npm uninstall $entry --save --loglevel=error >> /dev/tty");
            chdir($cwd);
        }

        $this->setSpaData($data);
    }

    private function getSpaData(): array
    {
        $file = $this->appInfo->writableDir() . '/spa/data.json';
        if(file_exists($file)){
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
        $file = $this->appInfo->writableDir() . '/spa/data.json';
        file_put_contents($file, json_encode($data));
    }
}