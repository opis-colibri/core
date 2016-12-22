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
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Opis\Colibri\Composer\Installers\AssetsInstaller;
use Opis\Colibri\Composer\Installers\ComponentInstaller;
use Opis\Colibri\Composer\Util\Filesystem;

class AssetsManager
{
    /** @var IOInterface  */
    protected $io;

    /** @var Composer  */
    protected $composer;

    /** @var AppInfo  */
    protected $appInfo;

    /** @var ComponentInstaller  */
    protected $componentInstaller;

    /** @var AssetsInstaller  */
    protected $assetsInstaller;

    /** @var  array */
    protected $enabledModules;

    /** @var  PackageInterface[] */
    protected $packages;

    public function __construct(Composer $composer,
                                IOInterface $io,
                                AppInfo $appInfo,
                                ComponentInstaller $componentInstaller,
                                AssetsInstaller $assetsInstaller,
                                array $enabled)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->appInfo = $appInfo;
        $this->componentInstaller = $componentInstaller;
        $this->assetsInstaller = $assetsInstaller;
        $this->enabledModules = $enabled;
    }

    /**
     * @return IOInterface
     */
    public function getIO(): IOInterface
    {
        return $this->io;
    }

    /**
     * @return Composer
     */
    public function getComposer(): Composer
    {
        return $this->composer;
    }

    /**
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->appInfo;
    }

    /**
     * @return ComponentInstaller
     */
    public function getComponentInstaller(): ComponentInstaller
    {
        return $this->componentInstaller;
    }

    /**
     * @return array
     */
    public function getEnabledModules()
    {
        return $this->enabledModules;
    }

    /**
     * @return AssetsInstaller
     */
    public function getAssetsInstaller(): AssetsInstaller
    {
        return $this->assetsInstaller;
    }

    /**
     * @return PackageInterface[]
     */
    public function getPackages(): array
    {
        if($this->packages === null){
            $this->packages = [];
            $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
            foreach ($packages as $package){
                if(!in_array($package->getType(), [Application::COMPOSER_TYPE, 'component'])){
                    continue;
                }
                $this->packages[] = $package;
            }
        }
        return $this->packages;
    }

    /**
     * @param ITask[] ...$tasks
     */
    public function run(ITask ...$tasks)
    {
        foreach ($tasks as $task){
            $task->execute($this);
        }
    }
}