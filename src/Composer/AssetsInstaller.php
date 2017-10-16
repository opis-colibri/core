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

class AssetsInstaller extends LibraryInstaller
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
        if(!isset($extra['module']['assets'])){
            return;
        }

        if(!is_array($extra['module']['assets'])){
            if(!is_string($extra['module']['assets'])){
                return;
            }
            $assets = [
                'source' => trim($extra['module']['assets'], DIRECTORY_SEPARATOR),
                'build' => null,
                'build_script' => null
            ];
        } else {
            $assets = $extra['module']['assets'];
            $assets += [
                'source' => null,
                'build' => null,
                'build_script' => 'build'
            ];

            if(!is_string($assets['source'])){
                return;
            }
            if(!is_null($assets['build']) && !is_string($assets['build'])){
                return;
            }
            if(!is_string($assets['build_script'])){
                return;
            }
        }

        $base_dir = $this->getInstallPath($package);
        $cwd = getcwd();

        if($assets['build'] !== null){
            $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['build'];
            if(file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')){
                chdir($dir);
                passthru("yarn install >> /dev/tty");
                if($assets['build_script'] !== null){
                    passthru("yarn run " . escapeshellarg($assets['build_script']) . " >> /dev/tty");
                }
            }
        }

        $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['source'];

        if(!is_dir($dir)){
            return;
        }

        if(file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')){
            $dir = escapeshellarg($dir);
            chdir($this->appInfo->rootDir());
            passthru("yarn add $dir >> /dev/tty");
        } else {
            $fs = new Filesystem();
            $name = str_replace('/', '.', $package->getName());
            $fs->mirror($dir, $this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
        }

        chdir($cwd);
    }

    /**
     * @param PackageInterface $package
     */
    public function uninstallAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if(!isset($extra['module']['assets'])){
            return;
        }

        if(!is_array($extra['module']['assets'])){
            $assets = [
                'source' => trim($extra['module']['assets'], DIRECTORY_SEPARATOR),
                'build' => null,
                'build_script' => null,
            ];
        } else {
            $assets = $extra['module']['assets'];
            $assets += [
                'source' => null,
                'build' => null,
                'build_script' => 'build'
            ];
        }

        $base_dir = $this->getInstallPath($package);
        $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['source'];

        if(file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')){
            $json = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'package.json'), true);
            $pack = escapeshellarg($json['name']);
            $cwd = getcwd();
            chdir($this->appInfo->rootDir());
            passthru("yarn remove $pack >> /dev/tty");
            chdir($cwd);
        } else {
            $fs = new Filesystem();
            $name = str_replace('/', '.', $package->getName());
            $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
        }
    }
}
