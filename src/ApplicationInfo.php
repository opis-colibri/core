<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

class ApplicationInfo
{
    const PUBLIC_DIR = 'public-dir';
    const WRITABLE_DIR = 'writable-dir';
    const TEMP_DIR = 'temp-dir';
    const CLI_MODE = 'cli-mode';
    const VENDOR_DIR = 'vendor-dir';
    const COMPOSER_FILE = 'composer-file';
    const ASSETS_PATH = 'assets-path';
    const ASSETS_DIR = 'assets-dir';
    const INIT_FILE = 'init-file';
    const ENV_FILE = 'env-file';
    const WEB_PATH = 'web-path';

    protected array $cache = [];
    protected array $settings;
    protected string $rootDir;
    protected array $env;
    protected bool $envLoaded = false;

    /**
     * AppInfo constructor.
     * @param string $rootDir
     * @param array $settings
     * @param array $env
     */
    public function __construct(string $rootDir, array $settings, array $env = [])
    {
        $this->rootDir = $rootDir;
        $this->settings = $settings;
        $this->env = $env;

        $this->settings += [
            self::VENDOR_DIR => 'vendor',
            self::PUBLIC_DIR => 'public',
            self::WRITABLE_DIR => 'storage',
            self::ASSETS_DIR => 'assets',
            self::TEMP_DIR => sys_get_temp_dir(),
            self::INIT_FILE => 'init.php',
            self::ENV_FILE => 'env.php',
            self::ASSETS_PATH => '/assets',
            self::WEB_PATH => '/',
        ];
    }

    /**
     * Get env variable stored in envFile()
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getEnv(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, $this->env)) {
            // We already have it in cache
            return $this->env[$name];
        }

        if ($this->envLoaded) {
            // file was already loaded
            return $default;
        }

        $this->envLoaded = true;

        $file = $this->envFile();

        if (!is_file($file)) {
            return $default;
        }

        $env = require($file);

        if (!is_array($env)) {
            return $default;
        }

        $this->env += $env;

        return array_key_exists($name, $this->env) ? $this->env[$name] : $default;
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
     * Get assets directory
     *
     * @return  string
     */
    public function assetsDir(): string
    {
        return $this->getFsPath(self::ASSETS_DIR);
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
     * Get assets path
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
        return $this->getFsPath(self::INIT_FILE);
    }

    /**
     * Env file
     *
     * @return string
     */
    public function envFile(): string
    {
        return $this->getFsPath(self::ENV_FILE, true);
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
     * Clear cache
     */
    public function clearCache(): void
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
}
