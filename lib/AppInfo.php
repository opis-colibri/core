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

use Exception;

class AppInfo
{
    const ROOT_PATH = 1;
    const PUBLIC_PATH = 2;
    const ASSETS_PATH = 3;
    const MODULES_PATH = 4;
    const STORAGES_PATH = 5;
    const SYSTEM_PATH = 6;
    const SYSTEM_MODULES_PATH = 7;
    const INSTALL_MODE = 8;
    const CLI_MODE = 9;
    const MAIN_APP_FILE = 10;
    const USER_APP_FILE = 11;
    const APP_CLASS = 12;
    const VENDOR_PATH = 13;

    /**  @var   array */
    protected $info;

    /**
     * Constructor
     * 
     * @param   array   $info   App info
     */
    public function __construct(array $info)
    {
        $this->info = $info;
    }

    /**
     * Get root path
     * 
     * @return  string
     */
    public function rootPath()
    {
        if (!isset($this->info[static::ROOT_PATH])) {
            throw new Exception('Root path must be set');
        }
        
        return $this->info[static::ROOT_PATH];
    }

    /**
     * Get public path
     * 
     * @return  string
     */
    public function publicPath()
    {
        if (!isset($this->info[static::PUBLIC_PATH])) {
            $this->info[static::PUBLIC_PATH] = $this->rootPath() . '/public';
        }

        return $this->info[static::PUBLIC_PATH];
    }

    /**
     * Get assets path
     * 
     * @return  string
     */
    public function assetsPath()
    {
        if (!isset($this->info[static::ASSETS_PATH])) {
            $this->info[static::ASSETS_PATH] = $this->publicPath() . '/assets';
        }

        return $this->info[static::ASSETS_PATH];
    }

    /**
     * Get modules path
     * 
     * @return  string
     */
    public function modulesPath()
    {
        if (!isset($this->info[static::MODULES_PATH])) {
            $this->info[static::MODULES_PATH] = $this->rootPath() . '/modules';
        }

        return $this->info[static::MODULES_PATH];
    }

    /**
     * Get storages path
     * 
     * @return  string
     */
    public function storagesPath()
    {
        if (!isset($this->info[static::STORAGES_PATH])) {
            $this->info[static::STORAGES_PATH] = $this->rootPath() . '/storage';
        }

        return $this->info[static::STORAGES_PATH];
    }

    /**
     * Get system path
     * 
     * @return  string
     */
    public function systemPath()
    {
        if (!isset($this->info[static::SYSTEM_PATH])) {
            $this->info[static::SYSTEM_PATH] = $this->rootPath() . '/system';
        }

        return $this->info[static::SYSTEM_PATH];
    }

    /**
     * Get system modules path
     * 
     * @return  string
     */
    public function systemModulesPath()
    {
        if (!isset($this->info[static::SYSTEM_MODULES_PATH])) {
            $this->info[static::SYSTEM_MODULES_PATH] = $this->systemPath() . '/modules';
        }

        return $this->info[static::SYSTEM_MODULES_PATH];
    }

    /**
     * Install mode
     * 
     * @return  boolean
     */
    public function installMode()
    {
        if (!isset($this->info[static::INSTALL_MODE])) {
            $this->info[static::INSTALL_MODE] = !file_exists($this->mainAppFile());
        }

        return $this->info[static::INSTALL_MODE];
    }

    /**
     * CLI mode
     * 
     * @return  boolean
     */
    public function cliMode()
    {
        if (!isset($this->info[static::CLI_MODE])) {
            $this->info[static::CLI_MODE] = php_sapi_name() === 'cli';
        }

        return $this->info[static::CLI_MODE];
    }

    /**
     * Main app file
     * 
     * @return  string
     */
    public function mainAppFile()
    {
        if (!isset($this->info[static::MAIN_APP_FILE])) {
            $this->info[static::MAIN_APP_FILE] = $this->storagesPath() . '/app.php';
        }

        return $this->info[static::MAIN_APP_FILE];
    }

    /**
     * User app file
     * 
     * @return  string
     */
    public function userAppFile()
    {
        if (!isset($this->info[static::USER_APP_FILE])) {
            $this->info[static::USER_APP_FILE] = $this->rootPath() . '/app.php';
        }

        return $this->info[static::USER_APP_FILE];
    }

    /**
     * App class
     * 
     * @return  string
     */
    public function appClass()
    {
        if (!isset($this->info[static::APP_CLASS])) {
            $this->info[static::APP_CLASS] = 'Opis\Colibri\App';
        }

        return $this->info[static::APP_CLASS];
    }

    /**
     * Vendor path
     * 
     * @return  string
     */
    public function vendorPath()
    {
        if (!isset($this->info[static::VENDOR_PATH])) {
            $this->info[static::VENDOR_PATH] = $this->rootPath() . '/vendor';
        }

        return $this->info[static::VENDOR_PATH];
    }
}
