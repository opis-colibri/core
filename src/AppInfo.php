<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

use Opis\Colibri\Plugin\Settings;

class AppInfo
{
    const ROOT_DIR = 'root-dir';
    const PUBLIC_DIR = 'public-dir';
    const WRITABLE_DIR = 'writable-dir';
    const TEMP_DIR = 'temp-dir';
    const CLI_MODE = 'cli-mode';
    const INSTALL_MODE = 'install-mode';
    const VENDOR_DIR = 'vendor-dir';
    const COMPOSER_FILE = 'composer-file';
    const ASSETS_PATH = 'assets-path';
    const INIT_FILE = 'init-file';
    const WEB_PATH = 'web-path';

    /** @var    array */
    protected $cache = [];

    /** @var  array */
    protected $settings = [];

    /** @var  string */
    protected $rootDir;

    /** @var Settings */
    protected $pluginSettings;

    /** @var null|array */
    private $extra;

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
            self::VENDOR_DIR => 'vendor',
            self::PUBLIC_DIR => 'public',
            self::WRITABLE_DIR => 'storage',
            self::TEMP_DIR => sys_get_temp_dir(),
            self::INIT_FILE => 'init.php',
            self::ASSETS_PATH => '/assets',
            self::WEB_PATH => '/',
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
        return $this->getPluginSettings()->assetsDir();
    }

    /**
     * Get public path
     *
     * @return  string
     */
    public function publicDir(): string
    {
        return $this->getFsPath(self::PUBLIC_DIR);
    }

    /**
     * Get the path to the app's writable directory
     *
     * @return  string
     */
    public function writableDir(): string
    {
        return $this->getFsPath(self::WRITABLE_DIR);
    }

    /**
     * Vendor dir
     *
     * @return  string
     */
    public function vendorDir(): string
    {
        return $this->getFsPath(self::VENDOR_DIR);
    }

    /**
     * Get the path to temporary files directory
     *
     * @return string
     */
    public function tempDir(): string
    {
        return $this->getFsPath(self::TEMP_DIR, true);
    }

    /**
     * @return string
     */
    public function assetsPath(): string
    {
        if (!isset($this->cache[self::ASSETS_PATH])) {
            $this->cache[self::ASSETS_PATH] = rtrim($this->settings[self::ASSETS_PATH], '/');
        }

        return $this->cache[self::ASSETS_PATH];
    }

    /**
     * @return string
     */
    public function webPath(): string
    {
        if (!isset($this->cache[self::WEB_PATH])) {
            $this->cache[self::WEB_PATH] = '/' . trim($this->settings[self::WEB_PATH], '/');
        }

        return $this->cache[self::WEB_PATH];
    }

    /**
     * Composer file
     *
     * @return string
     */
    public function composerFile(): string
    {
        if (!isset($this->cache[self::COMPOSER_FILE])) {
            $this->cache[self::COMPOSER_FILE] = $this->rootDir() . DIRECTORY_SEPARATOR . 'composer.json';
        }

        return $this->cache[self::COMPOSER_FILE];
    }

    /**
     * Init file
     *
     * @return string
     */
    public function initFile(): string
    {
        return $this->getFsPath(self::INIT_FILE, true);
    }

    /**
     * Install mode
     *
     * @return  boolean
     */
    public function installMode(): bool
    {
        if (!isset($this->cache[self::INSTALL_MODE])) {
            $this->cache[self::INSTALL_MODE] = !is_file($this->initFile());
        }

        return $this->cache[self::INSTALL_MODE];
    }

    /**
     * CLI mode
     *
     * @return  boolean
     */
    public function cliMode(): bool
    {
        if (!isset($this->cache[self::CLI_MODE])) {
            $this->cache[self::CLI_MODE] = php_sapi_name() === 'cli';
        }

        return $this->cache[self::CLI_MODE];
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
     * @return Settings
     */
    public function getPluginSettings(): Settings
    {
        if ($this->pluginSettings === null) {
            $settings = $this->getComposerExtra()['opis/colibri'] ?? [];
            if (!is_array($settings)) {
                $settings = [];
            }
            $this->pluginSettings = new Settings($this->rootDir, $settings);
        }

        return $this->pluginSettings;
    }

    /**
     * Clear cache
     */
    public function clearCache()
    {
        $this->cache = [];
    }

    /**
     * @param string $name
     * @param bool $writableAsBase
     * @return string
     */
    protected function getFsPath(string $name, bool $writableAsBase = false): string
    {
        if (!isset($this->cache[$name])) {
            if ($this->settings[$name][0] === '/') {
                $this->cache[$name] = $this->settings[$name];
            } else {
                $base = $writableAsBase ? $this->writableDir() : $this->rootDir();
                $this->cache[$name] = $base . DIRECTORY_SEPARATOR . $this->settings[$name];
            }
        }

        return $this->cache[$name];
    }

    /**
     * @return array
     */
    protected function getComposerExtra(): array
    {
        if ($this->extra === null) {
            $this->extra = json_decode(file_get_contents($this->composerFile()), true);
        }

        return $this->extra;
    }
}
