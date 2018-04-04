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
use Opis\Colibri\SPA\DataHandler;
use Opis\Colibri\SPA\DefaultSpaHandler;
use Opis\Colibri\SPA\SpaHandler;
use Symfony\Component\Filesystem\Filesystem;

class SpaInstaller extends AbstractInstaller
{
    /** @var DataHandler|null */
    private $dataHandler;

    /**
     * @param PackageInterface $package
     */
    public function install(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (!isset($extra['module']['spa'])) {
            return;
        }

        $spa = $extra['module']['spa'];
        $spa += [
            'register' => [],
            'extend' => [],
        ];

        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $module_dir = $this->installer->getInstallPath($package);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();
        $apps = &$data['apps'];
        $modules = &$data['modules'];

        $fs = new Filesystem();

        foreach ($spa['register'] as $local_app_name => $app) {
            $app += [
                'source' => 'spa/' . $local_app_name,
                'dist' => 'dist',
                'template' => 'spa/' . $local_app_name . 'template',
                'handler' => DefaultSpaHandler::class,
            ];

            if (!is_subclass_of($app['handler'], SpaHandler::class)) {
                continue;
            }

            // normalize
            foreach (['source', 'dist', 'template'] as $item) {
                $app[$item] = implode(DIRECTORY_SEPARATOR, explode('/', trim($app[$item], '/')));
            }

            $source_dir = $module_dir . DIRECTORY_SEPARATOR . $app['source'];
            $template_dir = $module_dir . DIRECTORY_SEPARATOR . $app['template'];
            $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';
            if (!file_exists($package_json) || !is_dir($template_dir)) {
                continue;
            }

            $pkg = json_decode(file_get_contents($package_json), true);

            if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                continue;
            }

            $app_name = $package_name . '.' . $local_app_name;
            $dir = implode(DIRECTORY_SEPARATOR, [$base_dir, $app_name]);

            $apps[$app_name] = [
                'name' => $local_app_name,
                'dir' => $dir,
                'dist' => $dir . DIRECTORY_SEPARATOR . $app['dist'],
                'template' => $template_dir,
                'handler' => $app['handler'],
                'owner' => $module,
                'modules' => [$module],
            ];

            $modules[$module][$app_name] = $source_dir;

            if ($fs->exists($dir)) {
                $fs->remove($dir);
            }
            $fs->mirror($template_dir, $dir);

            $this->yarn()->install(null, $dir);
            $this->yarn()->addPackage($source_dir, $dir);
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($module === $ext_module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source_dir) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                $source_dir = implode(DIRECTORY_SEPARATOR, explode('/', trim($source_dir, '/')));
                $source_dir = $module_dir . DIRECTORY_SEPARATOR . $source_dir;

                $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';
                if (!file_exists($package_json)) {
                    continue;
                }

                $pkg = json_decode(file_get_contents($package_json), true);
                if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                    continue;
                }

                $app['modules'][] = $module;
                $modules[$module][$app_name] = $source_dir;

                $this->yarn()->addPackage($source_dir, $app['dir']);
            }
        }

        $this->setSpaData($data);
    }

    /**
     * @param PackageInterface $package
     */
    public function update(PackageInterface $package)
    {
        $extra = $package->getExtra();
        $spa = $extra['module']['spa'] ?? [];
        $spa += ['register' => [], 'extend' => []];
        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $module_dir = $this->installer->getInstallPath($package);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();
        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $fs = new Filesystem();

        if (isset($modules[$module])) {
            foreach (array_keys($modules[$module]) as $app_name) {
                $app = &$apps[$app_name];
                if ($app['owner'] === $module) {
                    if (!isset($spa['register'][$app['name']])) {
                        //remove spa
                        foreach ($app['modules'] as $module_name) {
                            unset($modules[$module_name][$app_name]);
                        }
                        $fs->remove($app['dir']);
                        $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $app_name);
                        unset($apps[$app_name]);
                    } else {
                        unset($spa['register'][$app['name']]);
                        if (!in_array($app_name, $rebuild)) {
                            $rebuild[] = $app_name;
                        }
                    }
                } else {
                    if (!isset($spa['extend'][$app['owner']][$app['name']])) {
                        $app_modules = $app['modules'];
                        if (false !== $key = array_search($module, $app_modules)) {
                            unset($app_modules[$key]);
                        }
                        $app['modules'] = array_values($app_modules);
                        $this->yarn()->removePackage($package_name, $app['dir']);
                    } else {
                        unset($spa['extend'][$app['owner']][$app['name']]);
                    }

                    if (!in_array($app_name, $rebuild)) {
                        $rebuild[] = $app_name;
                    }
                }
            }
        }

        foreach ($spa['register'] as $local_app_name => $app) {
            $app += [
                'source' => 'spa/' . $local_app_name,
                'dist' => 'dist',
                'template' => 'spa/' . $local_app_name . 'template',
                'handler' => DefaultSpaHandler::class,
                'config' => [],
            ];

            if (!is_subclass_of($app['handler'], SpaHandler::class)) {
                continue;
            }

            // normalize
            foreach (['source', 'dist', 'template'] as $item) {
                $app[$item] = implode(DIRECTORY_SEPARATOR, explode('/', trim($item, '/')));
            }

            $source_dir = $module_dir . DIRECTORY_SEPARATOR . $app['source'];
            $template_dir = $module_dir . DIRECTORY_SEPARATOR . $app['template'];
            $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';

            if (!file_exists($package_json) || !is_dir($template_dir)) {
                continue;
            }

            $pkg = json_decode(file_get_contents($package_json), true);

            if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                continue;
            }

            $app_name = $package_name . '.' . $local_app_name;
            $dir = implode(DIRECTORY_SEPARATOR, [$base_dir, $app_name]);

            $apps[$app_name] = [
                'name' => $local_app_name,
                'dir' => $dir,
                'dist' => $dir . DIRECTORY_SEPARATOR . $app['dist'],
                'template' => $template_dir,
                'owner' => $module,
                'modules' => [$module],
            ];

            $modules[$module][$app_name] = $source_dir;

            if ($fs->exists($dir)) {
                $fs->remove($dir);
            }
            $fs->mirror($template_dir, $dir);

            $this->yarn()->install(null, $dir);
            $this->yarn()->addPackage($source_dir, $dir);

            if (!in_array($app_name, $rebuild)) {
                $rebuild[] = $app_name;
            }
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($module === $ext_module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source_dir) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                $source_dir = implode(DIRECTORY_SEPARATOR, explode('/', trim($source_dir, '/')));
                $source_dir = $module_dir . DIRECTORY_SEPARATOR . $source_dir;

                $package_json = $source_dir . DIRECTORY_SEPARATOR . 'package.json';
                if (!file_exists($package_json)) {
                    continue;
                }

                $pkg = json_decode(file_get_contents($package_json), true);
                if (!isset($pkg['name']) || $pkg['name'] !== $package_name) {
                    continue;
                }

                $app['modules'][] = $module;
                $modules[$module][$app_name] = $source_dir;

                $this->yarn()->addPackage($source_dir, $app['dir']);

                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        $this->setSpaData($data);
    }

    /**
     * @param PackageInterface $package
     */
    public function uninstall(PackageInterface $package)
    {
        $extra = $package->getExtra();

        if (!isset($extra['module']['spa'])) {
            return;
        }

        $spa = $extra['module']['spa'] + ['register' => [], 'extend' => []];
        $module = $package->getName();
        $package_name = str_replace('/', '.', $module);
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data = $this->getSpaData();

        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $fs = new Filesystem();

        foreach ($spa['register'] as $local_app_name => $app_data) {
            $app_name = $package_name . '.' . $local_app_name;
            if (!isset($apps[$app_name])) {
                continue;
            }
            $fs->remove($base_dir . DIRECTORY_SEPARATOR . $app_name);
            $fs->remove($this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $app_name);
            $app = $apps[$app_name];
            foreach ($app['modules'] as $app_module) {
                unset($modules[$app_module][$app_name]);
            }
            unset($apps[$app_name]);
        }

        foreach ($spa['extend'] as $ext_module => $ext_app) {
            if ($ext_module === $module) {
                continue;
            }
            foreach ($ext_app as $local_app_name => $source) {
                $app_name = str_replace('/', '.', $ext_module) . '.' . $local_app_name;
                if (!isset($apps[$app_name])) {
                    continue;
                }
                $app = &$apps[$app_name];
                if (!in_array($module, $app['modules'])) {
                    continue;
                }
                if (false !== $key = array_search($module, $app['modules'])) {
                    unset($app['modules'][$key]);
                }

                $this->yarn()->removePackage($package_name, $app['dir']);

                if (!in_array($app_name, $rebuild)) {
                    $rebuild[] = $app_name;
                }
            }
        }

        $this->setSpaData($data);
    }

    /**
     * @return array
     */
    private function getSpaData(): array
    {
        if ($this->dataHandler == null) {
            $this->dataHandler = new DataHandler($this->appInfo);
        }
        return $this->dataHandler->getData();
    }

    /**
     * @param array $data
     */
    private function setSpaData(array $data)
    {
        if ($this->dataHandler == null) {
            $this->dataHandler = new DataHandler($this->appInfo);
        }
        $this->dataHandler->setData($data);
    }
}