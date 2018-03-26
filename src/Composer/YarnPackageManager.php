<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri\Composer;

class YarnPackageManager
{
    /** @var bool */
    protected $isCLI = false;

    /** @var bool */
    protected $needsEnvSetup = true;

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
     * @return bool
     */
    public function addPackage(string $name, string $root = null): bool
    {
        return $this->command('add', $name, $root) === 0;
    }

    /**
     * @param string $name
     * @param string|null $root
     * @return bool
     */
    public function removePackage(string $name, string $root = null): bool
    {
        return $this->command('remove', $name, $root) === 0;
    }

    /**
     * @param string|null $package
     * @param string|null $root
     * @return bool
     */
    public function install(string $package = null, string $root = null): bool
    {
        return $this->command('install', $package, $root) === 0;
    }

    /**
     * @param string|null $package
     * @param string|null $root
     * @return bool
     */
    public function update(string $package = null, string $root = null): bool
    {
        return $this->command('upgrade', $package, $root) === 0;
    }

    /**
     * @param string $script
     * @param string|null $root
     * @return bool
     */
    public function run(string $script, string $root = null): bool
    {
        return $this->command('run', $script, $root) === 0;
    }

    /**
     * @param string $command
     * @param null $args
     * @param string|null $root
     * @param string|null $redirect
     * @return int
     */
    public function command(string $command, $args = null, string $root = null, string $redirect = null): int
    {
        if (!$this->isCLI && $this->needsEnvSetup) {
            $this->setupEnv();
            $this->needsEnvSetup = false;
        }

        if ($redirect === null) {
            $redirect = $this->isCLI ? '/dev/tty' : '/dev/null';
        }

        $command = 'yarn ' . $command;

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

        if ($redirect !== '') {
            $command .= ' >> ' . $redirect;
        }

        $cwd = getcwd();
        chdir($root ?? getcwd());
        $code = 0;
        passthru($command, $code);
        chdir($cwd);
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
}