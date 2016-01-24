<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

use Composer\Factory;
use Composer\Json\JsonManipulator;
use Composer\Installer\PackageEvent;

class PostPackageInstall
{

    public static function execute(PackageEvent $event)
    {
        $package = $event->getOperation()->getPackage();

        if ($package->getType() !== 'metapackage') {
            return 0;
        }

        $dkey = 'opis-colibri-package';
        $extra = $package->getExtra();

        if (!isset($extra[$dkey]) || !$extra[$dkey]) {
            return 0;
        }

        $file = Factory::getComposerFile();
        $manipulator = new JsonManipulator(file_get_contents($file));

        if (isset($extra['installer-modules']) && is_array($extra['installer-modules'])) {
            $manipulator->addSubNode('extra', 'installer-modules', $extra['installer-modules']);
        }

        $root = $event->getComposer()->getPackage()->getExtra();
        $paths = isset($root['installer-paths']) ? $root['installer-paths'] : array();

        if (isset($extra['installer-paths']) && is_array($extra['installer-paths'])) {
            foreach ($extra['installer-paths'] as $path => $list) {
                if (!is_array($list)) {
                    continue;
                }

                if (isset($paths[$path])) {
                    $result = array_merge($paths[$path], $list);
                    $result = array_keys(array_flip($result));
                    $paths[$path] = $result;
                } else {
                    $paths[$path] = array_values($list);
                }
            }
        }

        if (isset($extra['system-modules']) && is_array($extra['system-modules'])) {
            $modules = $extra['system-modules'];

            foreach ($modules as &$module) {
                if (strpos($module, '/') === false) {
                    $module = 'opis-colibri/' . $module;
                }
            }

            $path = 'system/modules/{$name}/';

            if (isset($paths[$path])) {
                $result = array_merge($paths[$path], $modules);
                $result = array_keys(array_flip($result));
                $paths[$path] = $result;
            } else {
                $paths[$path] = array_values($modules);
            }
        }

        if (!empty($paths)) {
            $manipulator->addSubNode('extra', 'installer-paths', $paths);
        }

        file_put_contents($file, $manipulator->getContents());

        return 0;
    }
}
