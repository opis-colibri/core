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

class AppInfo
{
    const ROOT_PATH = 1;
    const CORE_PATH = 2;
    const PUBLIC_PATH = 3;
    const ASSETS_PATH = 4;
    const MODULES_PATH = 5;
    const STORAGES_PATH = 6;
    const SYSTEM_PATH = 7;
    const SYSTEM_MODULES_PATH = 8;
    const INSTALL_MODE = 9;
    const CLI_MODE = 10;
    const MAIN_APP_FILE = 11;
    const USER_APP_FILE = 12;
    const APP_CLASS = 13;

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
        return $this->info[static::ROOT_PATH];
    }

    /**
     * Get core path
     * 
     * @return  string
     */
    public function corePath()
    {
        return $this->info[static::CORE_PATH];
    }

    /**
     * Get public path
     * 
     * @return  string
     */
    public function publicPath()
    {
        return $this->info[static::PUBLIC_PATH];
    }

    /**
     * Get assets path
     * 
     * @return  string
     */
    public function assetsPath()
    {
        return $this->info[static::ASSETS_PATH];
    }

    /**
     * Get modules path
     * 
     * @return  string
     */
    public function modulesPath()
    {
        return $this->info[static::MODULES_PATH];
    }

    /**
     * Get storages path
     * 
     * @return  string
     */
    public function storagesPath()
    {
        return $this->info[static::STORAGES_PATH];
    }

    /**
     * Get system path
     * 
     * @return  string
     */
    public function systemPath()
    {
        return $this->info[static::SYSTEM_PATH];
    }

    /**
     * Get system modules path
     * 
     * @return  string
     */
    public function systemModulesPath()
    {
        return $this->info[static::SYSTEM_MODULES_PATH];
    }

    /**
     * Install mode
     * 
     * @return  boolean
     */
    public function installMode()
    {
        return $this->info[static::INSTALL_MODE];
    }

    /**
     * CLI mode
     * 
     * @return  boolean
     */
    public function cliMode()
    {
        return $this->info[static::CLI_MODE];
    }

    /**
     * Main app file
     * 
     * @return  string
     */
    public function mainAppFile()
    {
        return $this->info[static::MAIN_APP_FILE];
    }

    /**
     * User app file
     * 
     * @return  string
     */
    public function userAppFile()
    {
        return $this->info[static::USER_APP_FILE];
    }

    /**
     * App class
     * 
     * @return  string
     */
    public function appClass()
    {
        return $this->info[static::APP_CLASS];
    }
}
