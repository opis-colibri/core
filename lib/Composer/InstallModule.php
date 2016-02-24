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
use Composer\Repository\PlatformRepository;
use Composer\Repository\CompositeRepository;

class InstallModule extends AbstractScript
{

    public function execute()
    {
        $io = $this->getIO();

        if (empty($this->arguments)) {
            $io->writeError('<error>No module was specified</error>');
            return 1;
        }

        $modules = array_map(function($value) {

            if (strpos($value, '/') === false) {
                $value = 'opis-colibri/' . $value;
            }

            if (strpos($value, ':') === false) {
                $value .= ':*';
            }

            return $value;
        }, $this->arguments);

        $file = Factory::getComposerFile();

        $json = new JsonFile($file);
        $composerDefinition = $json->read();
        $composerBackup = file_get_contents($json->getPath());


        $composer = $this->getComposer();
        $repos = $composer->getRepositoryManager()->getRepositories();

        $this->repos = new CompositeRepository(array_merge(
                array(new PlatformRepository), $repos
        ));


        $requirements = $this->determineRequirements($modules);

        $requireKey = !$this->devMode ? 'require-dev' : 'require';
        $removeKey = !$this->devMode ? 'require' : 'require-dev';
        $baseRequirements = array_key_exists($requireKey, $composerDefinition) ? $composerDefinition[$requireKey] : array();
        $requirements = $this->formatRequirements($requirements);

        $this->validateRequirements($requirements);

        $manager = $this->getComposer()->getRepositoryManager();

        $io->write('Searching for modules..');

        foreach ($requirements as $name => $version) {
            $pack = $manager->findPackage($name, $version);

            if ($pack == null) {
                $io->writeError('<error>Module ' . $name . ' doesn\'t exist</error>');
                return 1;
            }

            if ($pack->getType() !== 'opis-colibri-module') {
                $io->writeError('<error>' . $name . ' is not a valid module</error>');
                return 1;
            }

            $io->write('Found module ' . $pack->getName());
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
}
