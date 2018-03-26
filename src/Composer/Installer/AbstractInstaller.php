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

namespace Opis\Colibri\Composer\Installer;

use Composer\Composer;
use Composer\IO\IOInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Composer\ModuleInstaller;
use Opis\Colibri\Composer\YarnPackageManager;

abstract class AbstractInstaller
{
    /** @var AppInfo */
    protected $appInfo;

    /** @var ModuleInstaller */
    protected $installer;

    /** @var IOInterface */
    protected $io;

    /** @var Composer */
    protected $composer;

    /** @var YarnPackageManager */
    protected $yarn;

    /**
     * AssetsHandler constructor
     * @param ModuleInstaller $installer
     * @param AppInfo $appInfo
     * @param IOInterface $io
     * @param Composer $composer
     */
    public function __construct(ModuleInstaller $installer, AppInfo $appInfo, IOInterface $io, Composer $composer)
    {
        $this->appInfo = $appInfo;
        $this->installer = $installer;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * @return YarnPackageManager
     */
    protected function yarn(): YarnPackageManager
    {
        if ($this->yarn === null) {
            $this->yarn = new YarnPackageManager();
        }

        return $this->yarn;
    }
}