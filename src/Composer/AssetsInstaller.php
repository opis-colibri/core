<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

    public function supports($packageType)
    {
        return $packageType === Application::COMPOSER_TYPE;
    }

    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->installAssets($package);
    }

    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->uninstallAssets($initial);
        parent::update($repo, $initial, $target);
        $this->installAssets($target);
    }

    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->uninstallAssets($package);
        parent::uninstall($repo, $package);
    }

    /**
     * @param PackageInterface $package
     */
    protected function installAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if(!isset($extra['module']['assets'])){
            return;
        }
        $dirname = trim($extra['module']['assets'], DIRECTORY_SEPARATOR);
        $dir = $this->getInstallPath($package) . DIRECTORY_SEPARATOR . $dirname;

        if(!is_dir($dir)){
            return;
        }

        $cwd = getcwd();

        if(file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')){
            chdir($dir);
            passthru("npm install --loglevel=error >> /dev/tty");
            $pack = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'package.json'), true);
            if(isset($pack['scripts'])){
                foreach ($pack['scripts'] as $script){
                    if(substr($script, 0, 4) !== 'test'){
                        echo $script, PHP_EOL;
                        chdir($dir);
                        passthru("npm run-script $script -- --log-level=error >> /dev/tty");
                    }
                }
            }
        }

        if(file_exists($dir . DIRECTORY_SEPARATOR . 'bower.json')){
            $dir = escapeshellarg($dir);
            chdir($this->appInfo->rootDir());
            passthru("bower install $dir --save --production >> /dev/tty");
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
    protected function uninstallAssets(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if(!isset($extra['module']['assets'])){
            return;
        }
        $dirname = trim($extra['module']['assets'], DIRECTORY_SEPARATOR);
        $dir = $this->getInstallPath($package) . DIRECTORY_SEPARATOR . $dirname;

        if(file_exists($dir . DIRECTORY_SEPARATOR . 'bower.json')){
            $bower = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'bower.json'), true);
            $pack = escapeshellarg($bower['name']);
            $cwd = getcwd();
            chdir($this->appInfo->rootDir());
            passthru("bower uninstall $pack --save >> /dev/tty");
            chdir($cwd);
        } else {
            $fs = new Filesystem();
            $name = str_replace('/', '.', $package->getName());
            $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
        }
    }
}