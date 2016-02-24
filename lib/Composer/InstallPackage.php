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

namespace Opis\Colibri\Composer;

use Composer\Factory;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Repository\PlatformRepository;
use Composer\Repository\CompositeRepository;

class InstallPackage extends AbstractScript
{
    protected $extra;

    public function execute()
    {
        $io = $this->getIO();

        if (empty($this->arguments)) {
            $io->writeError('<error>No package was specified</error>');
            return 1;
        }

        if (!isset($this->arguments[1])) {
            $this->arguments[1] = '*';
        }

        $package = $this->arguments[0] . ':' . $this->arguments[1];

        if (strpos($package, '/') === false) {
            $package = 'opis-colibri/' . $package;
        }

        $file = Factory::getComposerFile();

        $json = new JsonFile($file);
        $composerDefinition = $json->read();
        $composerBackup = file_get_contents($json->getPath());


        $composer = $this->getComposer();
        $repos = $composer->getRepositoryManager()->getRepositories();

        $this->repos = new CompositeRepository(array_merge(
                array(new PlatformRepository), $repos
        ));


        $requirements = $this->determineRequirements(array($package));

        $requireKey = !$this->devMode ? 'require-dev' : 'require';
        $removeKey = !$this->devMode ? 'require' : 'require-dev';
        $baseRequirements = array_key_exists($requireKey, $composerDefinition) ? $composerDefinition[$requireKey] : array();
        $requirements = $this->formatRequirements($requirements);

        $this->validateRequirements($requirements);

        $manager = $this->getComposer()->getRepositoryManager();

        $io->write('Searching for package..');

        foreach ($requirements as $name => $version) {

            $pack = $manager->findPackage($name, $version);

            if ($pack == null) {
                $io->writeError('<error>The ' . $name . ' package was not found</error>');
                return 1;
            }

            $this->extra = $extra = $pack->getExtra();
            $dkey = 'opis-colibri-package';

            if ($pack->getType() !== 'metapackage' || !isset($extra[$dkey]) || !$extra[$dkey]) {
                $io->writeError('<error>' . $name . ' is not a valid package</error>');
                return 1;
            }

            $io->write('Package found');
        }

        $sortPackages = false;

        if (!$this->updateFileCleanly($json, $baseRequirements, $requirements, $requireKey, $removeKey, $sortPackages)) {
            foreach ($requirements as $package => $version) {
                $baseRequirements[$package] = $version;
                if (isset($composerDefinition[$removeKey][$package])) {
                    unset($composerDefinition[$removeKey][$package]);
                }
            }

            $composerDefinition[$requireKey] = $baseRequirements;
            $json->write($composerDefinition);
        }

        $this->resetComposer();
        $composer = $this->getComposer();

        $install = Installer::create($io, $composer);

        $status = $install
            ->setDevMode($this->devMode)
            ->setUpdate(true)
            ->setUpdateWhitelist(array_keys($requirements))
            ->run();

        if ($status !== 0) {
            $io->writeError('<error>Installation failed, reverting ' . $file . ' to its original content.</error>');
            file_put_contents($json->getPath(), $composerBackup);
        }

        return $status;
    }

    protected function updateFileCleanly(JsonFile $json, array $base, array $new, $requireKey, $removeKey, $sortPackages)
    {
        $extra = $this->extra;
        $contents = file_get_contents($json->getPath());
        $manipulator = new JsonManipulator($contents);

        foreach ($new as $package => $constraint) {
            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }

            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }

        if (isset($extra['installer-modules']) && is_array($extra['installer-modules'])) {
            $manipulator->addSubNode('extra', 'installer-modules', $extra['installer-modules']);
        }

        $paths = $this->getComposer()->getPackage()->getExtra();
        $paths = $paths['installer-paths'];

        if (isset($extra['installer-paths']) && is_array($extra['installer-paths'])) {
            foreach ($extra['installer-paths'] as $path => $list) {
                if (!is_array($list)) {
                    continue;
                }

                if (isset($paths[$path])) {
                    $result = array_merge($paths[$path], $list);
                    $result = array_keys(array_flip($result));
                    $paths[$path] = $result;
                } else {
                    $paths[$path] = $list;
                }
            }
        }

        if (isset($extra['system-modules']) && is_array($extra['system-modules'])) {
            $modules = $extra['system-modules'];

            foreach ($modules as &$module) {
                if (strpos($module, '/') === false) {
                    $module = 'opis-colibri/' . $module;
                }
            }

            $path = 'system/modules/{$name}/';

            if (isset($paths[$path])) {
                $result = array_merge($paths[$path], $modules);
                $result = array_keys(array_flip($result));
                $paths[$path] = $result;
            }
        }

        $manipulator->addSubNode('extra', 'installer-paths', $paths);
        file_put_contents($json->getPath(), $manipulator->getContents());
        return true;
    }
}
