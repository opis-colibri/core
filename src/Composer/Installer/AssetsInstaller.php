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

use Composer\Package\PackageInterface;
use Symfony\Component\Filesystem\Filesystem;

class AssetsInstaller extends AbstractInstaller
{
    /**
     * @param PackageInterface $package
     */
    public function install(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (!isset($extra['module']['assets'])) {
            return;
        }

        if (!is_array($extra['module']['assets'])) {
            if (!is_string($extra['module']['assets'])) {
                return;
            }
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
                'build_script' => 'build',
            ];

            if (!is_string($assets['source'])) {
                return;
            }
            if (!is_null($assets['build']) && !is_string($assets['build'])) {
                return;
            }
            if (!is_string($assets['build_script'])) {
                return;
            }
        }

        $base_dir = $this->installer->getInstallPath($package);

        if ($assets['build'] !== null) {
            $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['build'];
            if (file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')) {
                $this->yarn()->install(null, $dir);
                if ($assets['build_script'] !== null) {
                    $this->yarn()->run($assets['build_script'], $dir);
                }
            }
        }

        $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['source'];

        if (!is_dir($dir)) {
            return;
        }

        $fs = new Filesystem();
        if (file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')) {
            $root = $this->appInfo->rootDir();
            // Make dir relative
            $dir = $fs->makePathRelative($dir, $root);
            if (isset($dir[0]) && $dir[0] !== '.' && $dir[0] !== DIRECTORY_SEPARATOR) {
                $dir = '.' . DIRECTORY_SEPARATOR . $dir;
            }
            $this->yarn()->command('add', [
                $dir,
                '--modules-folder' => $this->appInfo->assetsDir(),
            ], $root);
        } else {
            $name = str_replace('/', '.', $package->getName());
            $fs->mirror($dir, $this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
        }
    }

    /**
     * @param PackageInterface $package
     */
    public function uninstall(PackageInterface $package)
    {
        $extra = $package->getExtra();
        if (!isset($extra['module']['assets'])) {
            return;
        }

        if (!is_array($extra['module']['assets'])) {
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
                'build_script' => 'build',
            ];
        }

        $base_dir = $this->installer->getInstallPath($package);
        $dir = $base_dir . DIRECTORY_SEPARATOR . $assets['source'];

        if (file_exists($dir . DIRECTORY_SEPARATOR . 'package.json')) {
            $json = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'package.json'), true);
            $this->yarn()->command('remove', [
                $json['name'],
                '--modules-folder' => $this->appInfo->assetsDir(),
            ], $this->appInfo->rootDir());
        } else {
            $fs = new Filesystem();
            $name = str_replace('/', '.', $package->getName());
            $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
        }
    }
}