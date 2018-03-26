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

use Composer\{
    Composer,
    Factory,
    IO\BaseIO,
    IO\NullIO,
    Console\Application as ComposerConsole
};
use Symfony\Component\Console\{
    Input\ArrayInput,
    Output\NullOutput,
    Output\OutputInterface
};
use function Opis\Colibri\Functions\{
    info
};

/**
 * Description of Composer
 *
 * @author mari
 */
class CLI
{
    /** @var ComposerConsole */
    protected $instance;

    /**
     * CLI constructor.
     */
    public function __construct()
    {
        if (getenv('HOME') === false) {
            putenv('HOME=' . posix_getpwuid(fileowner(info()->rootDir()))['dir']);
        }
    }

    /**
     * @param BaseIO|null $io
     * @return Composer
     */
    public function getComposer(BaseIO $io = null): Composer
    {
        $rootDir = info()->rootDir();
        $composerFile = info()->composerFile();
        return (new Factory())->createComposer($io ?? new NullIO(), $composerFile, false, $rootDir);
    }

    /**
     * @param array $command
     * @param OutputInterface|null $output
     * @return int
     */
    public function execute(array $command, OutputInterface $output = null)
    {
        $cwd = getcwd();
        chdir(info()->rootDir());
        $code = $this->instance()->run(new ArrayInput($command), $output ?? new NullOutput());
        chdir($cwd);
        return $code;
    }

    /**
     * @param OutputInterface|null $output
     * @return int
     */
    public function dumpAutoload(OutputInterface $output = null)
    {
        return $this->execute([
            'command' => 'dump-autoload',
        ], $output);
    }

    /**
     * @return ComposerConsole
     */
    protected function instance()
    {
        $instance = new ComposerConsole();
        $instance->setAutoExit(false);
        return $instance;
    }
}
