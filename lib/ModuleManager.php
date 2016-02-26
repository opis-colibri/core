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

namespace Opis\Colibri;

use GlobIterator;
use ReflectionClass;

class ModuleManager
{
    /** @var    Application */
    protected $app;

    /** @var    array */
    protected $moduleList;

    /**
     * Constructor
     * 
     * @param   Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 
     * @param   string  $module
     * 
     * @return  string
     */
    protected function toCamelCase($module)
    {
        $module = explode('-', $module);
        $module = array_map(function($value) {
            return ucfirst(strtolower($value));
        }, $module);

        return implode('', $module);
    }

    /**
     * 
     * @param   string  $module
     * 
     * @return  string
     */
    protected function toModuleTitle($module)
    {
        $module = explode('-', $module);
        $module = array_map(function($value) {
            return strtolower($value);
        }, $module);

        return ucfirst(implode(' ', $module));
    }

    /**
     * 
     * @param   string  $module
     * 
     * @return  string
     */
    protected function toModuleId($module)
    {
        return strtolower($module);
    }

    /**
     * 
     * @param   string  $module
     * @param   array   $list   (optional)
     * @param   boolean $return (optional)
     * 
     * @return  array
     */
    protected function recursiveDependencies($module, array &$list = array(), $return = true)
    {
        $module = $this->toModuleId($module);

        if (!isset($list[$module])) {
            $list[$module] = 1;

            if (null !== $dependencies = $this->dependencies($module)) {
                foreach ($dependencies as $dependency) {
                    $this->recursiveDependencies($dependency, $list, false);
                }
            }
        }

        if ($return) {
            $list = array_keys($list);
            array_shift($list);
            return $list;
        }
    }

    /**
     * 
     * @param   string      $module
     * @param   string      $action
     * @param   array|null  $info
     */
    protected function executeInstallerAction($module, $action, $info = null)
    {
        if ($info === null) {
            $info = $this->info($module);
        }

        if ($info['installer'] !== null) {
            $installer = $info['installerClass'];
            require_once $info['installer'];
            $reflector = new ReflectionClass($installer);
            if ($reflector->isSubclassOf('\\Opis\Colibri\\ModuleInstaller')) {
                $installer::instance()->{$action}($this->app);
            }
        }
    }

    /**
     * Find all modules
     * 
     * @param   boolean $clear  (optional)
     * 
     * @return  array 
     */
    public function findAll($clear = false)
    {
        if (!$clear && $this->moduleList !== null) {
            return $this->moduleList;
        }

        $this->moduleList = array();

        $search_paths = array(
            $this->app->info()->modulesPath(),
            $this->app->info()->systemModulesPath(),
        );

        foreach ($search_paths as $modules_path) {
            $iterator = new GlobIterator($modules_path . '/*/composer.json');

            foreach ($iterator as $fileInfo) {
                //Get the directroy and the JSON file path
                $directory = $fileInfo->getPath();
                $jsonFile = $fileInfo->getPathName();

                //Check if the json contained in file is valid
                if (!is_readable($jsonFile) ||
                    !is_file($jsonFile) ||
                    null === $composer = json_decode(file_get_contents($jsonFile), true)) {
                    continue;
                }

                // Check if is set the correct type
                if (!isset($composer['type']) || $composer['type'] !== 'opis-colibri-module') {
                    continue;
                }

                // Extract module's name
                $name = substr($composer['name'], strpos($composer['name'], '/') + 1);

                // Check if it is a valid name
                if (!preg_match('/^[a-zA-Z](-?[a-zA-Z0-9]+)*$/', $name)) {
                    continue;
                }

                // Generate a module ID
                $module = $this->toModuleId($name);

                // Check if module ID was not already registered
                if ($module === '' || isset($this->moduleList[$module])) {
                    continue;
                }
                
                $info = isset($composer['extra']['module']) ? $composer['extra']['module'] : array();

                $info['name'] = $name;
                $info['directory'] = $directory;

                $className = $this->toCamelCase($name);

                $info += array(
                    'title' => $this->toModuleTitle($name),
                    'description' => isset($composer['description']) ? $composer['description'] : '',
                    'namespace' => 'Opis\\Colibri\\Module\\' . $className,
                    'include' => null,
                    'assets' => null,
                    'source' => null,
                    'dependencies' => array(),
                    'collector' => 'collect.php',
                    'installer' => 'install.php',
                    'collectorClass' => 'Opis\\Colibri\\ModuleCollector\\' . $className,
                    'installerClass' => 'Opis\\Colibri\\ModuleInstaller\\' . $className,
                    'hidden' => false,
                );
                
                // Handle `source`
                if ($info['source'] === null) {
                    $info['source'] = $directory;
                } else {
                    $info['source'] = $directory . '/' . trim($info['source'], '/');
                }
                // Handle `assets`
                if ($info['assets'] !== null) {
                    $assets = $directory . '/' . trim($info['assets'], '/');
                    if (!file_exists($assets) || !is_dir($assets) || $assets == $directory) {
                        $assets = null;
                    }
                    $info['assets'] = $assets;
                }
                // Handle files
                foreach (array('collector', 'installer', 'include') as $item) {
                    if ($info[$item] !== null) {
                        $itemPath = $directory . '/' . trim($info[$item], '/');
                        if (!file_exists($itemPath) || is_dir($itemPath)) {
                            $itemPath = null;
                        }
                        $info[$item] = $itemPath;
                    }
                }

                $this->moduleList[$module] = $info;
            }
        }

        ksort($this->moduleList);
        return $this->moduleList;
    }

    /**
     * Check if a module exists
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function exists($module)
    {
        $module = $this->toModuleId($module);

        if ($this->app->config()->read('modules.list.' . $module, false) !== false) {
            return true;
        }

        $list = $this->findAll();
        return isset($list[$module]);
    }

    /**
     * Check if a module is installed
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function isInstalled($module)
    {
        $module = $this->toModuleId($module);
        return false !== $this->app->config()->read('modules.list.' . $module, false);
    }

    /**
     * Check if a module is enabled
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function isEnabled($module)
    {
        $module = $this->toModuleId($module);
        return $this->app->config()->read('modules.enabled.' . $module, false);
    }

    /**
     * Obtain information about a module
     * 
     * @param   string  $module Module's name
     * 
     * @return  array|null
     */
    public function info($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->exists($module)) {
            return null;
        } elseif ($this->isInstalled($module)) {
            return $this->app->config()->read('modules.list.' . $module);
        }

        $list = $this->findAll();
        return $list[$module];
    }

    /**
     * Find all dependencies of a module
     * 
     * @param   string  $module
     * 
     * @return  array|null
     */
    public function dependencies($module)
    {
        $module = $this->toModuleId($module);

        if (null !== $info = $this->info($module)) {
            return $info['dependencies'];
        }

        return null;
    }

    /**
     * Find all the dependents of a module
     * 
     * @param   string  $module
     * 
     * @return  array|null
     */
    public function dependents($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->exists($module)) {
            return null;
        }

        $list = array();

        foreach ($this->findAll() as $target => $info) {
            if ($module !== $target && in_array($module, $this->dependencies($target))) {
                $list[] = $target;
            }
        }

        return $list;
    }

    /**
     * Check if a module can be installed
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function canBeInstalled($module)
    {
        $module = $this->toModuleId($module);

        if ($this->isEnabled($module) || $this->isInstalled($module)) {
            return false;
        }

        foreach ($this->recursiveDependencies($module) as $dependency) {
            if (!$this->isEnabled($dependency)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Chack if a module can be uninstalled
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function canBeUninstalled($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->isInstalled($module) || $this->isEnabled($module)) {
            return false;
        }

        foreach ($this->dependents($module) as $dependent) {
            if ($this->isInstalled($dependent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a module can be disabled
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function canBeDisabled($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->isEnabled($module)) {
            return false;
        }

        foreach ($this->dependents($module) as $dependent) {
            if ($this->isEnabled($dependent) || $this->isInstalled($dependent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a module can be enabled
     * 
     * @param   string  $module Module's name
     * 
     * @return  boolean
     */
    public function canBeEnabled($module)
    {
        $module = $this->toModuleId($module);

        if ($this->isInstalled($module) && !$this->isEnabled($module)) {
            foreach ($this->recursiveDependencies($module) as $dependency) {
                if (!$this->isEnabled($dependency)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Install a module
     * 
     * @param   string  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function install($module, $recollect = true)
    {
        $module = $this->toModuleId($module);

        if (!$this->canBeInstalled($module)) {
            return false;
        }

        $info = $this->info($module);
        $this->executeInstallerAction($module, 'install', $info);
        $this->app->config()->write('modules.list.' . $module, $info);
        $this->app->config()->write('modules.enabled.' . $module, false);

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.installed.' . $module);

        return true;
    }

    /**
     * Uninstall a module
     * 
     * @param   string  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function uninstall($module, $recollect = true)
    {
        $module = $this->toModuleId($module);

        if (!$this->canBeUninstalled($module)) {
            return false;
        }

        $this->executeInstallerAction($module, 'uninstall');
        $this->app->config()->delete('modules.list.' . $module);
        $this->app->config()->delete('modules.enabled.' . $module);

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.uninstalled.' . $module);

        return true;
    }

    /**
     * Enable a module
     * 
     * @param   string  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function enable($module, $recollect = true)
    {
        $module = $this->toModuleId($module);

        if (!$this->canBeEnabled($module)) {
            return false;
        }

        $this->app->config()->write('modules.enabled.' . $module, true);
        $this->registerAssets($module);
        $this->app->loadModule($module);
        $this->executeInstallerAction($module, 'enable');

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.enabled.' . $module);
        return true;
    }

    /**
     * Disable a module
     * 
     * @param   string  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function disable($module, $recollect = true)
    {
        $module = $this->toModuleId($module);

        if (!$this->canBeDisabled($module)) {
            return false;
        }

        $this->executeInstallerAction($module, 'disable');
        $this->app->config()->write('modules.enabled.' . $module, false);
        $this->unregisterAssets($module);

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.disabled.' . $module);
        return true;
    }

    /**
     * To module class
     * 
     * @param   string  $module
     * 
     * @return  string
     */
    public function toModuleClass($module)
    {
        return $this->toCamelCase($module);
    }

    /**
     * Get the collector's class
     * 
     * @param   string  $module
     * 
     * @return  string|null
     */
    public function collectorClass($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->exists($module)) {
            return null;
        }

        $info = $this->info($module);
        return $info['collectorClass'];
    }

    /**
     * Get the installer's class
     * 
     * @param   string  $module
     * 
     * @return  string|null
     */
    public function installerClass($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->exists($module)) {
            return null;
        }

        $info = $this->info($module);
        return $info['installerClass'];
    }

    /**
     * Register module's assets
     * 
     * @param   string  $module
     * 
     * @return  boolean
     */
    public function registerAssets($module)
    {
        $module = $this->toModuleId($module);

        if (!$this->isEnabled($module)) {
            return false;
        }

        $info = $this->info($module);

        if ($info['assets'] === null) {
            return true;
        }

        $cwd = getcwd();
        chdir($this->app->info()->assetsPath() . '/module');
        $status = symlink($info['assets'], $module);
        chdir($cwd);

        return $status;
    }

    /**
     * Unregister module's assets
     * 
     * @param   string   $module
     * 
     * @return  boolean
     */
    public function unregisterAssets($module)
    {
        $module = $this->toModuleId($module);

        if ($this->isEnabled($module)) {
            return false;
        }

        $path = $this->app->info()->assetsPath() . '/module/' . $module;

        if (!file_exists($path) || !is_link($path)) {
            return false;
        }

        return unlink($path);
    }
}
