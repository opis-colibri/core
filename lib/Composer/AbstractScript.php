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
use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;
use Composer\Json\JsonManipulator;
use Composer\EventDispatcher\Event;
use Composer\Package\Version\VersionParser;

abstract class AbstractScript
{
    protected $io;
    protected $repos;
    protected $event;
    protected $devMode;
    protected $composer;
    protected $arguments;

    protected function __construct(Event $event, Composer $composer, IOInterface $io, array $arguments, $devmode)
    {
        $this->io = $io;
        $this->event = $event;
        $this->devMode = $devmode;
        $this->composer = $composer;
        $this->arguments = $arguments;
    }

    public static function instance(Event $event, Composer $composer, IOInterface $io, array $arguments, $devmode)
    {
        return new static($event, $composer, $io, $arguments, $devmode);
    }

    protected function resetComposer()
    {
        $this->composer = null;
    }

    public function getComposer()
    {
        if ($this->composer === null) {
            $this->composer = Factory::create($this->io, null, false);
        }

        return $this->composer;
    }

    public function getIO()
    {
        return $this->io;
    }

    protected function determineRequirements(array $requires)
    {
        $result = array();

        $requires = $this->normalizeRequirements($requires);

        foreach ($requires as $requirement) {
            $result[] = $requirement['name'] . ' ' . $requirement['version'];
        }

        return $result;
    }

    protected function normalizeRequirements(array $requirements)
    {
        $parser = new VersionParser();
        return $parser->parseNameVersionPairs($requirements);
    }

    protected function formatRequirements(array $requirements)
    {
        $requires = array();
        $requirements = $this->normalizeRequirements($requirements);
        foreach ($requirements as $requirement) {
            $requires[$requirement['name']] = $requirement['version'];
        }
        return $requires;
    }

    protected function validateRequirements(array $requirements)
    {
        // validate requirements format
        $versionParser = new VersionParser();
        foreach ($requirements as $constraint) {
            $versionParser->parseConstraints($constraint);
        }
    }
    
    protected function updateFileCleanly(JsonFile $json, array $base, array $new, $requireKey, $removeKey, $sortPackages)
    {
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

        file_put_contents($json->getPath(), $manipulator->getContents());
        return true;
    }

    public abstract function execute();
}
