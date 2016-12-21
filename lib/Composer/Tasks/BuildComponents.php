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

use MatthiasMullie\Minify\JS;
use Opis\Colibri\Composer\AssetsManager;
use Opis\Colibri\Composer\ITask;
use Opis\Colibri\Composer\Util\Filesystem;

class BuildComponents implements ITask
{
    /**
     * @inheritDoc
     */
    public function execute(AssetsManager $assetsManager)
    {
        $manager = $assetsManager->getComposer()->getInstallationManager();
        $componentInstaller = $assetsManager->getComponentInstaller();
        $assetsInstaller = $assetsManager->getAssetsInstaller();
        $rebuild = $assetsManager->getAppInfo()->getSettings()['rebuild-components'] ?? true;
        $fs = new Filesystem();

        foreach ($assetsManager->getPackages() as $package){
            $extra = $package->getExtra();

            if(!isset($extra['component']) || !is_array($extra['component'])){
                continue;
            }

            $component = $extra['component'];

            if(!isset($component['scripts']) || !is_array($component['scripts']) || empty($component['scripts'])){
                continue;
            }

            $packageName = $package->getName();
            $packageDir = $manager->getInstallPath($package);

            if($package->getType() === 'component'){
                $name = $component['name'] ?? explode('/', $packageName)[1];
                $destination = $componentInstaller->getAssetsPath($package);
            } else {
                if(!in_array($packageName, $assetsManager->getEnabledModules())){
                    continue;
                }
                if(!isset($extra['module']['assets']) || !is_string($extra['module']['assets'])){
                    continue;
                }
                $packageDir .= DIRECTORY_SEPARATOR . $extra['module']['assets'];
                $name = str_replace('/', '--', $packageName);
                $destination = $assetsInstaller->getAssetsPath($package);
            }

            //$destination .= DIRECTORY_SEPARATOR . $name . '--build.js';

            $bundles = [];

            $mainBundle = [
                'files' => [],
                'output' => $name . '--build.js',
                'actions' => ['minify']
            ];

            foreach ($component['scripts'] as $item){
                if(is_string($item)){
                    $mainBundle['files'][] = $item;
                } elseif (is_array($item) && isset($item['files']) && is_array($item['files'])){
                    $bundles[] = $item;
                }
            }

            array_unshift($bundles, $mainBundle);

            foreach ($bundles as $bundle){
                if(empty($bundle['files'])){
                    continue;
                }

                $fileDestination = $destination . DIRECTORY_SEPARATOR . $bundle['output'];

                if(!$rebuild && file_exists($fileDestination)){
                    continue;
                }

                $content = [];

                foreach ($bundle['files'] as $file){
                    $source = $packageDir . DIRECTORY_SEPARATOR . $file;
                    foreach ($fs->recursiveGlobFiles($source) as $filesource){
                        $content[] = file_get_contents($filesource);
                    }
                }

                foreach ($bundle['actions'] as $action){
                    if(!is_array($action)){
                        $action = [
                            'action' => $action
                        ];
                    }
                    switch ($action['action']){
                        case 'minify':
                            $this->contentMinify($content);
                            break;
                        case 'concat':
                            $this->contentConcat($content, $action);
                            break;
                        case 'wrap':
                            $this->contentWrap($content, $action);
                            break;
                    }
                }

                file_put_contents($fileDestination, implode("", $content));
            }
        }
    }

    /**
     * @param array $content
     */
    private function contentMinify(array &$content)
    {
        foreach ($content as &$script){
            $minifier = new JS();
            $minifier->add($script);
            $script = $minifier->minify();
        }
    }

    /**
     * @param array $content
     * @param array $settings
     */
    private function contentWrap(array &$content, array $settings)
    {
        $settings += [
            'prefix' => '',
            'suffix' => '',
        ];

        foreach ($content as &$script){
            $script = $settings['prefix'] . $script . $settings['suffix'];
        }
    }

    /**
     * @param array $content
     * @param array $settings
     */
    private function contentConcat(array &$content, array $settings)
    {
        $settings += [
            'glue' => ''
        ];

        $content = (array) implode($settings['glue'], $content);
    }
}