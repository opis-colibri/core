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

use Composer\Package\PackageInterface;
use Opis\Colibri\Application;

class AssetsInstaller extends BaseAssetsInstaller
{
    /**
     * @param string $packageType
     * @return bool
     */
    public function supports($packageType)
    {
        return $packageType === Application::COMPOSER_TYPE;
    }

    /**
     * Get destination folder
     *
     * @param PackageInterface $package
     * @return string
     */
    public function getAssetsPath(PackageInterface $package)
    {
        $name = implode(DIRECTORY_SEPARATOR, explode('/', $package->getName()));
        return $this->getAssetsDir() . DIRECTORY_SEPARATOR . 'module' . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * @param PackageInterface $package
     * @return bool
     */
    public function removeAssets(PackageInterface $package)
    {
        if(!parent::removeAssets($package)){
            return false;
        }
        $path = dirname($this->getAssetsPath($package));
        if(count(scandir($path)) == 2){
            return $this->filesystem->remove($path);
        }
        return true;
    }

    /**
     * Init
     */
    public function initializeVendorDir()
    {
        parent::initializeVendorDir();
        $this->filesystem->emptyDirectory($this->getAssetsDir() . DIRECTORY_SEPARATOR . 'module');
    }
}