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

class BuildRequireJS implements ITask
{
    /** @var array */
    protected $requireJS = [];

    /**
     * @inheritDoc
     */
    public function execute(AssetsManager $assetsManager)
    {
        $appInfo = $assetsManager->getAppInfo();
        $assetsDir = $appInfo->assetsDir();
        $requireJs = [
            'baseUrl' => $appInfo->assetsPath()
        ];

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

            if($package->getType() === 'component'){
                $name = $component['name'] ?? explode('/', $packageName)[1];
                $location = $main = $name;
            } else {
                if(!in_array($packageName, $assetsManager->getEnabledModules())){
                    continue;
                }
                $name = $component['name'] ?? $packageName;
                $location = 'module/' . $packageName;
                $main = str_replace('/', '--', $name);
            }

            $main .= '--build.js';

            foreach (['shim', 'config'] as $item){
                if(isset($component[$item]) && is_array($component[$item]) && !empty($component[$item])){
                    $requireJs[$item][$name] = $component[$item];
                }
            }

            $ds = DIRECTORY_SEPARATOR;

            if(file_exists($assetsDir . $ds . implode($ds, explode('/', $location)) . $ds . $main)){
                $requireJs['packages'][] = [
                    'name' => $name,
                    'location' => $location,
                    'main' => $main
                ];
            }
        }

        $oldmask = umask(0002);
        copy(__DIR__ . '/../../../res/require.js', $assetsDir . '/require.js');
        file_put_contents($assetsDir . '/require.js', $this->getFileContent($requireJs), FILE_APPEND);
        umask($oldmask);
    }

    private function getFileContent($json): string
    {
        // Encode the array to a JSON array.
        $js = json_encode($json);
        // Construct the JavaScript output.
        $output = <<<EOT

//Components
var components = $js;
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}
EOT;
        return $output;
    }
}