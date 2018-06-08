<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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
use Opis\Colibri\{
    AppInfo
};
use Opis\Colibri\Composer\Installer\{
    AssetsInstaller, SpaInstaller
};

class ModuleInstaller extends LibraryInstaller
{
    /** @var AssetsInstaller */
    protected $assetsHandler;

    /** @var SpaInstaller */
    protected $spaHandler;

    /**
     * Installer constructor.
     *
     * @param AppInfo $appInfo
     * @param IOInterface $io
     * @param Composer $composer
     */
    public function __construct(AppInfo $appInfo, IOInterface $io, Composer $composer)
    {
        $this->assetsHandler = new AssetsInstaller($this, $appInfo, $io, $composer);
        $this->spaHandler = new SpaInstaller($this, $appInfo, $io, $composer);
        parent::__construct($io, $composer);
    }

    /**
     * @inheritdoc
     */
    public function supports($packageType)
    {
        return $packageType === AppInfo::MODULE_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        parent::install($repo, $package);
        $this->assetsHandler->install($package);
        $this->spaHandler->install($package);
    }

    /**
     * @inheritdoc
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->assetsHandler->uninstall($initial);
        parent::update($repo, $initial, $target);
        $this->assetsHandler->install($target);
        $this->spaHandler->update($target);
    }

    /**
     * @inheritdoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $this->assetsHandler->uninstall($package);
        $this->spaHandler->uninstall($package);
        parent::uninstall($repo, $package);
    }

    /**
     * @return AssetsInstaller
     */
    public function getAssetsInstaller(): AssetsInstaller
    {
        return $this->assetsHandler;
    }

    /**
     * @return SpaInstaller
     */
    public function getSpaInstaller(): SpaInstaller
    {
        return $this->spaHandler;
    }
}