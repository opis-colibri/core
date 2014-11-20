<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014 Marius Sarca
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

class Module
{
    
    protected static $moduleList;
    
    
    protected function __construct()
    {
        
    }
    
    protected static function toCamelCase($module)
    {
        $module = explode('-', $module);
        $module = array_map(function($value){
            return ucfirst(strtolower($value));
        }, $module);
        
        return implode('', $module);
    }
    
    protected static function toModuleTitle($module)
    {
        $module = explode('-', $module);
        $module = array_map(function($value){
            return strtolower($value);
        }, $module);
        
        return ucfirst(implode(' ', $module));
    }
    
    protected static function toModuleId($module)
    {   
        $module = explode('-', $module);
        $module = array_map(function($value){
            return strtolower($value);
        }, $module);
        
        return implode('-', $module);
    }
    
    
    protected static function recursiveDependencies($module, array &$list = array())
    {
        
        $module = static::toModuleId($module);
        
        if(!isset($list[$module]))
        {
            $list[$module] = 1;
            
            if(null !== $dependencies = static::dependencies($module))
            {
                foreach($dependencies as $dependency)
                {
                    static::recursiveDependencies($dependency, $list);
                }
            }
        }
        
        return array_keys($list);
    }
    
    
    protected static function executeInstallerAction($module, $action, &$info = null)
    {
        if($info === null)
        {
            $info = static::info($module);
        }
        
        if($info['installer'] !== null)
        {
            $installer = '\\Opis\\Colibri\\Module\\Installer\\' . static::toCamelCase($module);
            require_once $info['installer'];
            $reflector = new ReflectionClass($installer);
            if($reflector->isSubclassOf('\\Opis\Colibri\\ModuleInstaller'))
            {
                $installer::instance()->{$action}();
            }
        }
    }
    
    public static function findAll($clear = false)
    {
        if($clear === true)
        {
            static::$moduleList = null;
        }
        elseif(static::$moduleList !== null)
        {
            return static::$moduleList;
        }
        
        static::$moduleList = array();
        
        $search_paths = array(
            COLIBRI_SYSTEM_PATH . '/modules',
            COLIBRI_MODULES_PATH,
        );
        
        foreach($search_paths as $modules_path)
        {
            $iterator = new GlobIterator($modules_path . '/*/*.module.json');
            
            foreach($iterator as $fileInfo)
            {
                //Extract module name
                $name = $fileInfo->getFileName();
                $name = substr($name, 0,  strlen($name) - 12);
                
                //Check if it is a valid module name
                if(!preg_match('/^[a-zA-Z](-?[a-zA-Z0-9]+)*$/', $name))
                {
                    continue;
                }
                
                //Generate a module ID
                $module = static::toModuleId($name);
                
                //Check if module ID was not already registered
                if($module === '' || isset(static::$moduleList[$module]))
                {
                    continue;
                }
                
                //Get the directroy and the JSON file path
                $directory = $fileInfo->getPath();
                $jsonFile = $fileInfo->getPathName();
                
                //Check if the json contained in file is valid
                if(!is_readable($jsonFile) || !is_file($jsonFile) || null === $info = json_decode(file_get_contents($jsonFile), true))
                {
                    continue;
                }
                
                $info['name'] = $name;
                
                $info += array(
                    'title' => static::toModuleTitle($name),
                    'core' => App::version(),
                    'description' => '',
                    'namespace' => 'Colibri\\Module\\' . static::toCamelCase($name),
                    'include' => null,
                    'assets' => null,
                    'source' => null,
                    'dependencies' => array(),
                    'collector' => $name . '.module.php',
                    'installer' => $name . '.install.php',
                    'hidden' => false,
                );
                
                if(!is_string($info['core']))
                {
                    continue;
                }
                
                $info['core'] = trim($info['core']);
                
                if(version_compare(App::version(), $info['core'], '<'))
                {
                    continue;
                }
                
                
                $info['directory'] = $directory;
                
                
                if($info['source'] === null)
                {
                    $info['source'] = $directory;
                }
                else
                {
                    $info['source'] = $directory . '/' . trim($info['source'], '/');
                }
                
                if($info['assets'] !== null)
                {
                    $assets = $directory . '/' . trim($info['assets']);
                    
                    if(!file_exists($assets) || !is_dir($assets) || $assets == $directory)
                    {
                        $assets = null;
                    }
                    
                    $info['assets'] = $assets;
                }
                
                
                if($info['collector'] !== null)
                {
                    $collector = $directory . '/' . trim($info['collector'], '/');
                    
                    if(!file_exists($collector) || is_dir($collector))
                    {
                        $collector = null;
                    }
                    
                    $info['collector'] = $collector;
                }
                
                if($info['installer'] !== null)
                {
                    $installer = $directory . '/' . trim($info['installer'], '/');
                    
                    if(!file_exists($installer) || is_dir($installer))
                    {
                        $installer = null;
                    }
                    
                    $info['installer'] = $installer;
                }
                
                if($info['include'] !== null)
                {
                    $include = $directory . '/' . trim($info['include'], '/');
                    
                    if(!file_exists($include) || is_dir($include))
                    {
                        $include = null;
                    }
                    
                    $info['include'] = $include;
                }
                
                static::$moduleList[$module] = $info;
                
            }
        }
        
        ksort(static::$moduleList);
        return static::$moduleList;
        
    }
    
    public static function exists($module)
    {
        $module = static::toModuleId($module);
        
        if(App::systemConfig()->read('modules.list.' . $module, false) !== false)
        {
            return true;
        }
        
        $list = static::findAll();
        return isset($list[$module]);
    }
    
    public static function isInstalled($module)
    {
        $module = static::toModuleId($module);
        return false !== App::systemConfig()->read('modules.list.' . $module, false);
    }
    
    public static function isEnabled($module)
    {
        $module = static::toModuleId($module);
        return App::systemConfig()->read('modules.enabled.' . $module, false);
    }
    
    public static function info($module)
    {
        $module = static::toModuleId($module);
        
        if(!static::exists($module))
        {
            return null;
        }
        elseif(static::isInstalled($module))
        {
            return App::systemConfig()->read('modules.list.' . $module);
        }
        
        $list = static::findAll();
        return $list[$module];
    }
    
    public static function dependencies($module)
    {
        $module = static::toModuleId($module);
        
        if(null !== $info = static::info($module))
        {
            return $info['dependencies'];
        }
        
        return null;
    }
    
    public static function dependents($module)
    {
        $module = static::toModuleId($module);
        
        if(!static::exists($module))
        {
            return null;
        }
        
        $list = array();
        
        foreach(static::findAll() as $target => $info)
        {
            if($module !== $target && in_array($module, static::dependencies($target)))
            {
                $list[] = $target;
            }
        }
        
        return $list;
    }
    
    
    public static function canBeInstalled($module)
    {
        $module = static::toModuleId($module);
        
        if(static::isEnabled($module) || static::isInstalled($module))
        {
            return false;
        }
        
        foreach(static::recursiveDependencies($module) as $dependency)
        {
            if(!static::exists($dependency))
            {
                return false;
            }
        }
        
        return true;
        
    }
    
    public static function canBeUninstalled($module)
    {
        $module = static::toModuleId($module);
        
        if(!static::isInstalled($module) || static::isEnabled($module))
        {
            return false;
        }
        
        return true;
    }
    
    public static function canBeDisabled($module)
    {
        $module = static::toModuleId($module);
        
        if(!static::isEnabled($module))
        {
            return false;
        }
        
        foreach(App::systemConfig()->read('modules.enabled') as $resource => $status)
        {
            if($resource != $module && $status)
            {
                if(array_search($module, static::dependencies($resource)) !== false)
                {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    
    public static function canBeEnabled($module)
    {
        $module = static::toModuleId($module);
        
        if(static::isInstalled($module))
        {
            return !static::isEnabled($module);
        }
        
        return false;
    }
    
    public static function install($module, $clearCache = true)
    {
        $module = static::toModuleId($module);
        
        if(!static::canBeInstalled($module))
        {
            return false;
        }
        
        $info = static::info($module);
        
        App::systemConfig()->write('modules.list.' . $module, $info);
        App::systemConfig()->write('modules.enabled.' . $module, false);
        
        if($clearCache)
        {
            App::systemCache()->clear();
        }
        
        static::executeInstallerAction($module, 'install', $info);
        Emit('module.installed.' . $module);
        
        return true;
    }
    
    public static function uninstall($module, $clearCache = true)
    {
        $module = static::toModuleId($module);
        
        if(!static::canBeUninstalled($module))
        {
            return false;
        }
        
        
        App::systemConfig()->delete('modules.list.' . $module);
        App::systemConfig()->delete('modules.enabled.' . $module);
        
        if($clearCache)
        {
            App::systemCache()->clear();
        }
        
        static::executeInstallerAction($module, 'uninstall');
        Emit('module.uninstalled.' . $module);
        
        return true;
    }
    
    public static function enable($module, $clearCache = true)
    {
        $module = static::toModuleId($module);
        
        if(!static::canBeEnabled($module))
        {
            return false;
        }
        
        App::systemConfig()->write('modules.enabled.' . $module, true);
        
        static::registerAssets($module);
        
        if($clearCache)
        {
            App::systemCache()->clear();
        }
        
        static::executeInstallerAction($module, 'enable');
        Emit('module.enabled.' . $module);
        
        return true;
    }
    
    public static function disable($module, $clearCache = true)
    {
        $module = static::toModuleId($module);
        
        if(!static::canBeDisabled($module))
        {
            return false;
        }
        
        App::systemConfig()->write('modules.enabled.' . $module, false);
        
        static::unregisterAssets($module);
        
        if($clearCache)
        {
            App::systemCache()->clear();
        }
        
        static::executeInstallerAction($module, 'disable');
        Emit('module.disabled.' . $module);
        
        return true;
    }
    
    public static function title($module)
    {
        $module = static::toModuleId($module);
        
        if(null === $info = static::info($module))
        {
            return null;
        }
        
        return $info['title'];
    }
    
    public static function description($module)
    {
        $module = static::toModuleId($module);
        
        if(null === $info = static::info($module))
        {
            return null;
        }
        
        return $info['description'];
    }
    
    public static function registerAssets($module)
    {
        $module = static::toModuleId($module);
        
        if(!static::isEnabled($module))
        {
            return false;
        }
        
        $info = static::info($module);
        
        if($info['assets'] === null)
        {
            return true;
        }
        
        $target = '../../..' . substr($info['assets'], strlen(COLIBRI_ROOT));
        
        $cwd = getcwd();
        chdir(COLIBRI_PUBLIC_ASSETS_PATH . '/module');
        $status = symlink($target, $module);
        chdir($cwd);
        
        return $status;
        
    }
    
    public static function unregisterAssets($module)
    {
        $module = static::toModuleId($module);
        
        if(static::isEnabled($module))
        {
            return false;
        }
       
        $path = COLIBRI_PUBLIC_ASSETS_PATH . '/module/' . $module;
        
        if(!file_exists($path) || !is_link($path))
        {
            return false;
        }
        
        return unlink($path);
        
    }
    
    
}
