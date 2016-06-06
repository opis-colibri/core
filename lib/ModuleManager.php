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
use Opis\Utils\Dir;
use ReflectionClass;
use Composer\Package\CompletePackage;

class ModuleManager
{
    /** @var    Application */
    protected $app;

    /** @var    array */
    protected $moduleList;

    /** @var    array */
    protected $packages;

    /** @var    array */
    protected $enabledModules;

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
     * Get module packs
     * 
     * @param   bool    $clear  (optional)
     * 
     * @return  array
     */
    public function getPackages($clear = false)
    {
        if ($clear) {
            $this->packages = null;
        }

        if ($this->packages === null) {
            
            $packages = array();
            $composer = $this->app->getComposer();
            $repository = $composer->getRepositoryManager()->getLocalRepository();
            
            foreach ($repository->getPackages() as $package) {
                if (!$package instanceof CompletePackage || $package->getType() !== 'opis-colibri-module') {
                    continue;
                }
                $packages[$package->getName()] = $package;
            }

            $this->packages = $packages;
        }

        return $this->packages;
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
        
        $modules = array();
        
        foreach($this->getPackages($clear) as $module => $package)
        {
            $modules[$module] = new ModuleInfo($app, $module, $package);
        }
        
        return $this->moduleList = $modules;
        
        foreach ($this->app->info()->modulesPaths() as $modules_path) {
            
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

                if (!isset($info['version']) && !isset($composer['version'])) {
                    $packs = $this->getPackages();
                    if (isset($packs[$name])) {
                        $info['version'] = $packs[$name];
                    }
                }

                $className = $this->toCamelCase($name);

                $info += array(
                    'title'          => $this->toModuleTitle($name),
                    'version'        => isset($composer['version']) ? $composer['version'] : null,
                    'description'    => isset($composer['description']) ? $composer['description'] : '',
                    'namespace'      => 'Opis\\Colibri\\Module\\' . $className,
                    'include'        => null,
                    'assets'         => null,
                    'source'         => null,
                    'dependencies'   => array(),
                    'collector'      => 'collect.php',
                    'installer'      => 'install.php',
                    'collectorClass' => 'Opis\\Colibri\\ModuleCollector\\' . $className,
                    'installerClass' => 'Opis\\Colibri\\ModuleInstaller\\' . $className,
                    'hidden'         => false,
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

    
    public function getModule($name)
    {
        return new Module($this->app, $name);
    }

    /**
     * Return a list with modules that are enabled
     * 
     * @return  array
     */
    public function getEnabledModules()
    {
        if ($this->enabledModules === null) {
            $modules = array();
            foreach ($this->app->config()->read('modules.enabled') as $module => $status) {
                if ($status) {
                    $modules[] = $module;
                }
            }
            $this->enabledModules = $modules;
        }

        return $this->enabledModules;
    }

    /**
     * Check if a module is installed
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function isInstalled(Module $module)
    {
        if (!$module->exists()) {
            return false;
        }
        
        $list = $this->app->config()->read('app.modules.installed');
        return in_array($module->name(), $list);
    }

    /**
     * Check if a module is enabled
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function isEnabled(Module $module)
    {
        if (!$module->exists()) {
            return false;
        }
        
        $list = $this->app->config()->read('app.modules.enabled');
        return in_array($module->name(), $list);
    }

    /**
     * Check if a module can be installed
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function canBeInstalled(Module $module)
    {
        if ($this->isInstalled($module)) {
            return false;
        }

        foreach ($module->dependencies() as $dependency) {
            if (!$this->isEnabled($dependency)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Chack if a module can be uninstalled
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function canBeUninstalled(Module $module)
    {
        if ($this->isEnabled($module) || !$this->isInstalled($module)) {
            return false;
        }
        
        foreach ($module->dependents() as $dependent) {
            if ($this->isInstalled($dependent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a module can be disabled
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function canBeDisabled(Module $module)
    {
        if (!$this->isEnabled($module)) {
            return false;
        }
        
        foreach ($module->dependents() as $dependent) {
            if ($this->isInstalled($dependent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if a module can be enabled
     * 
     * @param   Module  $module Module's instance
     * 
     * @return  boolean
     */
    public function canBeEnabled(Module $module)
    {
        if ($this->isEnabled($module) || !$this->isInstalled($module)) {
            return false;
        }
        
        foreach ($module->dependencies() as $dependency) {
            if (!$this->isEnabled($dependency)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Install a module
     * 
     * @param   Module  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function install(Module $module, $recollect = true)
    {
        if (!$this->canBeInstalled($module)) {
            return false;
        }
        
        $config = $this->app->config();
        $modules = $config->read('app.modules.installed', array());
        $modules[] = $module->name();
        $config->write('app.modules.installed', $modules);
        
        if(null !== $installer = $module->installer()) {
            $this->app->make($installer)->install($this->app);
        }

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.installed.' . $module->name());

        return true;
    }

    /**
     * Uninstall a module
     * 
     * @param   Module  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function uninstall(Module $module, $recollect = true)
    {
        if (!$this->canBeUninstalled($module)) {
            return false;
        }
        
        $config = $this->app->config();
        $modules = $config->read('app.modules.installed', array());
        $config->write('app.modules.installed', array_diff($modules, array($module->name())));
        
        if(null !== $installer = $module->installer()) {
            $this->app->make($installer)->uninstall($this->app);
        }
        
        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.uninstalled.' . $module->name());
        
        return true;
    }

    /**
     * Enable a module
     * 
     * @param   Module  $module
     * @param   boolean $recollect  (optional)
     * 
     * @return  boolean
     */
    public function enable(Module $module, $recollect = true)
    {
        if (!$this->canBeEnabled($module)) {
            return false;
        }

        $config = $this->app->config();
        $modules = $config->read('app.modules.enabled', array());
        $modules[] = $module->name();
        $config->write('app.modules.enabled', $modules);
        $this->registerAssets($module);
        
        $this->executeInstallerAction($module, 'enable');

        if ($recollect) {
            $this->app->recollect();
        }

        $this->app->emit('module.enabled.' . $module->name());
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

        $this->enabledModules = null;
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
     * @param   Module  $module
     * 
     * @return  boolean
     */
    public function registerAssets(Module $module)
    {
        $module = $this->toModuleId($module);

        if (!$this->isEnabled($module)) {
            return false;
        }

        $info = $this->info($module);

        if ($info['assets'] === null) {
            return true;
        }

        $path = $this->app->info()->assetsPath() . '/module/' . $module;

        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            return Dir::copy($info['assets'], $path);
        }

        return symlink($info['assets'], $path);
    }

    /**
     * Unregister module's assets
     * 
     * @param   Module   $module
     * 
     * @return  boolean
     */
    public function unregisterAssets(Module $module)
    {
        $module = $this->toModuleId($module);

        if ($this->isEnabled($module)) {
            return false;
        }

        $path = $this->app->info()->assetsPath() . '/module/' . $module;

        if (!file_exists($path)) {
            return false;
        }

        if (is_link($path)) {
            return unlink($path);
        }

        return Dir::remove($path);
    }
}
