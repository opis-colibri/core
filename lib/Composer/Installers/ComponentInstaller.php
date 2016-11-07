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

/**
 * Component Installer for Composer.
 */
class ComponentInstaller extends BaseAssetsInstaller
{
    /**
     * {@inheritDoc}
     *
     * Components are supported by all packages. This checks wheteher or not the
     * entire package is a "component", as well as injects the script to act
     * on components embedded in packages that are not just "component" types.
     */
    public function supports($packageType)
    {
        return $packageType === 'component';
    }

    /**
     * Gets the destination Component directory.
     *
     * @param PackageInterface $package
     * @return string
     *   The path to where the final Component should be installed.
     */
    public function getAssetsPath(PackageInterface $package)
    {
        // Parse the pretty name for the vendor and package name.
        $name = $prettyName = $package->getPrettyName();

        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
            unset($vendor);
        }

        // First look for an override in root package's extra, then try the package's extra
        $rootPackage = $this->composer->getPackage();
        $rootExtras = $rootPackage ? $rootPackage->getExtra() : array();
        $customComponents = isset($rootExtras['component']) ? $rootExtras['component'] : array();

        if (isset($customComponents[$prettyName]) && is_array($customComponents[$prettyName])) {
            $component = $customComponents[$prettyName];
        }
        else {
            $extra = $package->getExtra();
            $component = isset($extra['component']) ? $extra['component'] : array();
        }

        // Allow the component to define its own name.
        if (isset($component['name'])) {
            $name = $component['name'];
        }
        // Find where the package should be located.
        return $this->getAssetsDir() . DIRECTORY_SEPARATOR . $name;
    }
    
}