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
use RuntimeException;

class AppInfo
{
    const ROOT_DIR = 'root-dir';
    const PUBLIC_DIR = 'public-dir';
    const ASSETS_DIR = 'assets-dir';
    const WRITABLE_DIR = 'writable-dir';
    const CLI_MODE = 'cli-mode';
    const INSTALL_MODE = 'install-mode';
    const VENDOR_DIR = 'vendor-dir';
    const COMPOSER_FILE = 'composer-file';
    const ASSETS_PATH = 'assets-path';
    const BOOTSTRAP_FILE = 'bootstrap-file';

    /** @var    array */
    protected $cache = [];

    /** @var  array */
    protected $settings = [];

    /** @var  string */
    protected $rootDir;

    /**
     * AppInfo constructor.
     * @param string $rootDir
     * @param array $settings
     */
    public function __construct(string $rootDir, array $settings)
    {
        $this->rootDir = $rootDir;
        $this->settings = $settings;

        $this->settings += [
            static::VENDOR_DIR => 'vendor',
            static::PUBLIC_DIR => 'public',
            static::ASSETS_DIR => 'assets',
            static::WRITABLE_DIR => 'storage',
            static::BOOTSTRAP_FILE => 'bootstrap.php',
            static::ASSETS_PATH => '/assets',
        ];
    }

    /**
     * Get root path
     * @return string
     */
    public function rootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * Get assets path
     *
     * @return  string
     */
    public function assetsDir(): string
    {
        if (!isset($this->cache[static::ASSETS_DIR])) {
            if($this->settings[static::ASSETS_DIR][0] === '/') {
                $this->cache[static::ASSETS_DIR] = $this->settings[static::ASSETS_DIR];
            } else {
                $this->cache[static::ASSETS_DIR] = $this->rootDir() . '/' . $this->settings[static::ASSETS_DIR];
            }
        }

        return $this->cache[static::ASSETS_DIR];
    }

    /**
     * Get public path
     *
     * @return  string
     */
    public function publicDir(): string
    {
        if (!isset($this->cache[static::PUBLIC_DIR])) {
            if($this->settings[static::PUBLIC_DIR][0] === '/') {
                $this->cache[static::PUBLIC_DIR] = $this->settings[static::PUBLIC_DIR];
            } else {
                $this->cache[static::PUBLIC_DIR] = $this->rootDir() . '/' . $this->settings[static::PUBLIC_DIR];
            }
        }

        return $this->cache[static::PUBLIC_DIR];
    }

    /**
     * Get the path to the app's writable directory
     *
     * @return  string
     */
    public function writableDir(): string
    {
        if (!isset($this->cache[static::WRITABLE_DIR])) {
            if($this->settings[static::WRITABLE_DIR][0] === '/') {
                $this->cache[static::WRITABLE_DIR] = $this->settings[static::WRITABLE_DIR];
            } else {
                $this->cache[static::WRITABLE_DIR] = $this->rootDir() . '/' . $this->settings[static::WRITABLE_DIR];
            }
        }

        return $this->cache[static::WRITABLE_DIR];
    }

    /**
     * Vendor dir
     *
     * @return  string
     */
    public function vendorDir(): string
    {
        if (!isset($this->cache[static::VENDOR_DIR])) {
            if($this->settings[static::VENDOR_DIR][0] === '/') {
                $this->cache[static::WRITABLE_DIR] = $this->settings[static::VENDOR_DIR];
            } else {
                $this->cache[static::VENDOR_DIR] = $this->rootDir() . '/' . $this->settings[static::VENDOR_DIR];
            }
        }

        return $this->cache[static::VENDOR_DIR];
    }

    /**
     * @return string
     */
    public function assetsPath(): string
    {
        if (!isset($this->cache[static::ASSETS_PATH])) {
            $this->cache[static::ASSETS_PATH] = '/' . trim($this->settings[static::ASSETS_PATH], '/');
        }

        return $this->cache[static::ASSETS_PATH];
    }

    /**
     * Composer file
     *
     * @return string
     */
    public function composerFile(): string
    {
        if (!isset($this->cache[static::COMPOSER_FILE])) {
            $this->cache[static::COMPOSER_FILE] = $this->rootDir() . '/composer.json';
        }

        return $this->cache[static::COMPOSER_FILE];
    }

    /**
     * Bootstrap file
     *
     * @return string
     */
    public function bootstrapFile(): string
    {
        if (!isset($this->cache[static::BOOTSTRAP_FILE])) {
            if($this->settings[static::BOOTSTRAP_FILE][0] === '/') {
                $this->cache[static::BOOTSTRAP_FILE] = $this->settings[static::BOOTSTRAP_FILE];
            } else {
                $this->cache[static::BOOTSTRAP_FILE] = $this->vendorDir() . '/' . $this->settings[static::BOOTSTRAP_FILE];
            }
        }

        return $this->cache[static::BOOTSTRAP_FILE];
    }

    /**
     * Install mode
     *
     * @return  boolean
     */
    public function installMode(): bool
    {
        if (!isset($this->cache[static::INSTALL_MODE])) {
            $this->cache[static::INSTALL_MODE] = !file_exists($this->bootstrapFile());
        }

        return $this->cache[static::INSTALL_MODE];
    }

    /**
     * CLI mode
     *
     * @return  boolean
     */
    public function cliMode(): bool 
    {
        if (!isset($this->cache[static::CLI_MODE])) {
            $this->cache[static::CLI_MODE] = php_sapi_name() === 'cli';
        }

        return $this->cache[static::CLI_MODE];
    }

    /**
     * Get app settings
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->cache = [];
    }

}
