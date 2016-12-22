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

namespace Opis\Colibri\Composer\Tasks;

use Opis\Colibri\Composer\AssetsManager;
use Opis\Colibri\Composer\ITask;
use Opis\Colibri\Composer\Util\Filesystem;
use Opis\Sassc\File as ScssFile;

class CopyFiles implements ITask
{
    /**
     * @inheritdoc
     */
    public function execute(AssetsManager $assetsManager)
    {
        $manager = $assetsManager->getComposer()->getInstallationManager();
        $componentInstaller = $assetsManager->getComponentInstaller();
        $assetsInstaller = $assetsManager->getAssetsInstaller();
        $enabled = $assetsManager->getEnabledModules();
        $fs = new Filesystem();

        foreach ($assetsManager->getPackages() as $package){
            $extra = $package->getExtra();
            $packageDir = $manager->getInstallPath($package);

            if('component' === $packageType = $package->getType()){
                if(!isset($extra['component']) || !is_array($extra['component'])){
                    continue;
                }
                $moduleDest = $componentInstaller->getAssetsPath($package);
                if(!file_exists($moduleDest)){
                    $this->doCopy($fs, $extra['component'], $packageDir, $moduleDest);
                }
                continue;
            }

            $moduleDest = $assetsInstaller->getAssetsPath($package);

            if(!in_array($package->getName(), $enabled)){
                if(file_exists($moduleDest)){
                    $this->removeDir($fs, $moduleDest);
                }
                continue;
            }

            $module = $extra['module'] ?? [];

            if(!isset($module['assets']) || !is_string($module['assets'])){
                continue;
            }

            $packageDir .= DIRECTORY_SEPARATOR . $module['assets'];

            if(!file_exists($moduleDest)){
                $this->doCopy($fs, $extra['component'] ?? ['files' => ['**']], $packageDir, $moduleDest);
            }
        }
    }

    /**
     * @param Filesystem $fs
     * @param array $component
     * @param string $packageDir
     * @param string $destination
     */
    private function doCopy(Filesystem $fs, array $component, string $packageDir, string $destination)
    {
        $types = ['styles', 'files'];
        foreach ($types as $type){
            if(!isset($component[$type]) || !is_array($component[$type])){
                continue;
            }
            foreach ($component[$type] as $file){
                $source = $packageDir . DIRECTORY_SEPARATOR . $file;
                foreach ($fs->recursiveGlobFiles($source) as $filesource){
                    // Find the final destination without the package directory.
                    $withoutPackageDir = str_replace($packageDir . DIRECTORY_SEPARATOR, '', $filesource);
                    $fileDest = $destination . DIRECTORY_SEPARATOR . $withoutPackageDir;
                    // Ensure the directory is available.
                    $fs->ensureDirectoryExists(dirname($fileDest));
                    // If it is a .scss file
                    if($type == 'styles' && 'scss' == pathinfo($fileDest, PATHINFO_EXTENSION)){
                        $pathInfo = pathinfo($fileDest);
                        $fileDest = $pathInfo['dirname'] .DIRECTORY_SEPARATOR . $pathInfo['filename'];
                        ScssFile::build($filesource, $fileDest . '.css', ScssFile::STYLE_EXPANDED);
                        ScssFile::build($filesource, $fileDest . '.min.css', ScssFile::STYLE_COMPRESSED);
                        continue;
                    }
                    // Copy the file to its destination.
                    copy($filesource, $fileDest);
                }
            }
        }
    }

    /**
     * @param Filesystem $fs
     * @param string $dir
     */
    protected function removeDir(Filesystem $fs, string $dir)
    {
        $fs->removeDirectory($dir);
        $dir = dirname($dir);
        if(count(scandir($dir)) == 2){
            $fs->removeDirectory($dir);
        }
    }
}