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

namespace Opis\Colibri\Composer\Installers;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Opis\Colibri\AppInfo;

abstract class BaseAssetsInstaller extends LibraryInstaller
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
     * Get destination folder
     *
     * @param PackageInterface $package
     * @return string
     */
    abstract public function getAssetsPath(PackageInterface $package);

    /**
     * Retrieves the Installer's provided component directory.
     */
    public function getAssetsDir()
    {
        return $this->appInfo->assetsDir();
    }

    /**
     * Remove a Component's files from the Component directory.
     *
     * @param PackageInterface $package
     * @return bool
     */
    public function removeAssets(PackageInterface $package)
    {
        $path = $this->getAssetsPath($package);
        return $this->filesystem->remove($path);
    }

    /**
     * Remove both the installed code and files from the assets directory.
     *
     * @param PackageInterface $package
     */
    public function removeCode(PackageInterface $package)
    {
        $this->removeAssets($package);
        parent::removeCode($package);
    }

    /**
     * Before installing the Component, be sure its destination is clear first.
     *
     * @param PackageInterface $package
     */
    public function installCode(PackageInterface $package)
    {
        $this->removeAssets($package);
        parent::installCode($package);
    }

}