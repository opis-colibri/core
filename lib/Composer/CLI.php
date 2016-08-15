<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

use Composer\Composer;
use Composer\Console\Application as ComposerConsole;
use Composer\Factory;
use Composer\IO\NullIO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use function Opis\Colibri\Helpers\{info};

/**
 * Description of Composer
 *
 * @author mari
 */
class CLI
{
    protected $instance;

    public function __construct()
    {
        if(getenv('HOME') === false){
            putenv('HOME=' . posix_getpwuid(fileowner(info()->rootDir()))['dir']);
        }
    }

    public function getComposer(): Composer
    {
        $rootDir = info()->rootDir();
        $composerFile = info()->composerFile();
        return (new Factory())->createComposer(new NullIO(), $composerFile, false, $rootDir);
    }

    public function execute(array $command)
    {
        $cwd = getcwd();
        chdir(info()->rootDir());
        $this->instance()->run(new ArrayInput($command), new NullOutput());
        chdir($cwd);
    }

    public function dumpAutoload()
    {
        $this->execute(array(
            'command' => 'dump-autoload',
        ));
    }

    protected function instance()
    {
        $instance = new ComposerConsole();
        $instance->setAutoExit(false);
        return $instance;
    }
}
