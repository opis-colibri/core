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

namespace Opis\Colibri\Core;

class YarnPackageManager
{
    protected bool $isCLI = false;
    protected bool $needsEnvSetup = true;
    protected ?string $path = null;

    /**
     * NodePackageManager constructor.
     */
    public function __construct()
    {
        $this->isCLI = PHP_SAPI === 'cli';
    }

    /**
     * @param string $name
     * @param string|null $root
     * @param string|null $modulesFolder
     * @return bool
     */
    public function addPackage(string $name, string $root = null, string $modulesFolder = null): bool
    {
        return $this->command('add', $name, $root, $modulesFolder) === 0;
    }

    /**
     * @param string $name
     * @param string|null $root
     * @param string|null $modulesFolder
     * @return bool
     */
    public function removePackage(string $name, string $root = null, string $modulesFolder = null): bool
    {
        return $this->command('remove', $name, $root, $modulesFolder) === 0;
    }

    /**
     * @param string|null $package
     * @param string|null $root
     * @param string|null $modulesFolder
     * @return bool
     */
    public function install(string $package = null, string $root = null, string $modulesFolder = null): bool
    {
        return $this->command('install', $package, $root, $modulesFolder) === 0;
    }

    /**
     * @param string|null $package
     * @param string|null $root
     * @param string|null $modulesFolder
     * @return bool
     */
    public function update(string $package = null, string $root = null, string $modulesFolder = null): bool
    {
        return $this->command('upgrade', $package, $root, $modulesFolder) === 0;
    }

    /**
     * @param string $script
     * @param string|null $root
     * @param string|null $modulesFolder
     * @return bool
     */
    public function run(string $script, string $root = null, string $modulesFolder = null): bool
    {
        return $this->command('run', $script, $root, $modulesFolder) === 0;
    }

    /**
     * @param string $command
     * @param string|array|null $args
     * @param string|null $root
     * @param string|null $modulesFolder
     * @param string|null $redirect
     * @return int
     */
    public function command(string $command, $args = null, string $root = null, string $modulesFolder = null, string $redirect = null): int
    {
        $command = 'node ' . $this->getYarnPath() . ' ' . $command;

        if (is_string($args)) {
            $command .= ' ' . $args;
        } elseif (is_array($args)) {
            foreach ($args as $name => $arg) {
                if ($arg === false) {
                    continue;
                }
                if (is_int($name)) {
                    $name = '';
                }
                $command .= ' ' . $name;
                if ($arg === null) {
                    continue;
                }
                if (is_array($arg)) {
                    $arg = implode(' ', array_map('escapeshellarg', $arg));
                } elseif (is_scalar($arg)) {
                    $arg = escapeshellarg($arg);
                }
                $command .= ' ' . $arg;
            }
        }

        if ($modulesFolder !== null) {
            $command .= ' --modules-folder ' . escapeshellarg($modulesFolder);
        }

        if ($redirect !== '') {
            $command .= ' >> ' . ($redirect ?? $this->isCLI ? '/dev/tty' : '/dev/null');
        }

        if (!$this->isCLI && $this->needsEnvSetup) {
            $this->setupEnv();
            $this->needsEnvSetup = false;
        }

        $code = 0;

        if ($root === null) {
            passthru($command, $code);
        } else {
            $cwd = getcwd();
            chdir($root);
            passthru($command, $code);
            chdir($cwd);
        }

        return $code;
    }

    /**
     * Sets up env variables
     */
    protected function setupEnv()
    {
        if (getenv('PATH') === false) {
            putenv('PATH=' . implode(':', [
                    '/usr/local/bin',
                    '/usr/bin',
                    '/bin',
                ])
            );
        }
    }

    protected function getYarnPath(): string
    {
        if ($this->path === null) {
            $this->path = realpath(__DIR__ . '../../yarn/bin/yarn.js');
        }

        return $this->path;
    }
}