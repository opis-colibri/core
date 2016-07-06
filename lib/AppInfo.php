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

use Composer\Composer;
use Exception;

class AppInfo
{
    const ROOT_DIR = 1;
    const PUBLIC_DIR = 2;
    const ASSETS_DIR = 3;
    const WRITABLE_DIR = 4;
    const CLI_MODE = 5;
    const INSTALL_MODE = 6;
    const VENDOR_DIR = 7;
    const COMPOSER_FILE = 8;
    const ASSETS_PATH = 9;

    /** @var    Application */
    protected $app;

    /**  @var   Composer */
    protected $composer;

    /** @var    array */
    protected $info = array();

    /**
     * Constructor
     *
     * @param   array $info App root folder
     * @param   Composer $composer Composer instance
     */
    public function __construct(array $info, Composer $composer = null)
    {
        $this->info = $info;
        $this->composer = $composer;
    }

    /**
     * @param Application $app
     */
    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get assets path
     *
     * @return  string
     */
    public function assetsDir(): string
    {
        if (!isset($this->info[static::ASSETS_DIR])) {
            $this->info[static::ASSETS_DIR] = $this->rootDir() . '/assets';
        }

        return $this->info[static::ASSETS_DIR];
    }

    /**
     * Get public path
     *
     * @return  string
     */
    public function publicDir(): string
    {
        if (!isset($this->info[static::PUBLIC_DIR])) {
            $this->info[static::PUBLIC_DIR] = $this->rootDir() . '/public';
        }

        return $this->info[static::PUBLIC_DIR];
    }

    /**
     * Get root path
     * @return string
     * @throws Exception
     */
    public function rootDir(): string
    {
        if (!isset($this->info[static::ROOT_DIR])) {
            throw new Exception('Root directory must be set');
        }
        return $this->info[static::ROOT_DIR];
    }

    /**
     * Get the path to the app's writable directory
     *
     * @return  string
     */
    public function writableDir(): string
    {
        if (!isset($this->info[static::WRITABLE_DIR])) {
            $this->info[static::WRITABLE_DIR] = $this->rootDir() . '/storage';
        }

        return $this->info[static::WRITABLE_DIR];
    }

    /**
     * Vendor dir
     *
     * @return  string
     */
    public function vendorDir(): string
    {
        if (!isset($this->info[static::VENDOR_DIR])) {
            $this->info[static::VENDOR_DIR] = $this->app->getComposer()->getConfig()->get('vendor-dir');;
        }

        return $this->info[static::VENDOR_DIR];
    }

    /**
     * @return string
     */
    public function assetsPath(): string
    {
        if(!isset($this->info[static::ASSETS_PATH])){
            $this->info[static::ASSETS_PATH] = '/assets';
        }
        return $this->info[static::ASSETS_PATH];
    }

    /**
     * Composer file
     *
     * @return string
     * @throws Exception
     */
    public function composerFile(): string
    {
        if(!isset($this->info[static::COMPOSER_FILE])) {
            $this->info[static::COMPOSER_FILE] = $this->rootDir() . '/composer.json';
        }

        return $this->info[static::COMPOSER_FILE];
    }

    /**
     * Install mode
     *
     * @return  boolean
     */
    public function installMode(): bool
    {
        if (!isset($this->info[static::INSTALL_MODE])) {
            $this->info[static::INSTALL_MODE] = !$this->app->getConfig()->read('app.installed', false);
        }

        return $this->info[static::INSTALL_MODE];
    }

    /**
     * CLI mode
     *
     * @return  boolean
     */
    public function cliMode(): bool 
    {
        if (!isset($this->info[static::CLI_MODE])) {
            $this->info[static::CLI_MODE] = php_sapi_name() === 'cli';
        }

        return $this->info[static::CLI_MODE];
    }

}
