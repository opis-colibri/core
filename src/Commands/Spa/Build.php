<?php
/* ===========================================================================
 * Copyright 2014-2018 The Opis Project
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

namespace Opis\Colibri\Commands\Spa;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use Opis\Colibri\Core\{
    Handlers\SpaHandler, PackageInstaller
};
use function Opis\Colibri\Functions\{
    info, module
};
use Symfony\Component\Console\{
    Command\Command, Helper\HelperSet, Input\InputArgument, Input\InputInterface, Output\OutputInterface
};

class Build extends Command
{
    protected function configure()
    {
        $this
            ->setName('spa:build')
            ->setDescription('Build SPA')
            ->addArgument('module', InputArgument::REQUIRED, "Module's name");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $module_name = $input->getArgument('module');
        $module = module($module_name);

        if (!$module->exists()) {
            $output->writeln("<error>Unknown module  $module_name</error>");
            return;
        }

        $console = new ConsoleIO($input, $output, new HelperSet());
        $appInfo = info();
        $rootDir = $appInfo->rootDir();
        $composerFile = $appInfo->composerFile();
        $composer = (new Factory())->createComposer($console, $composerFile, false, $rootDir);
        $installer = new PackageInstaller($appInfo, $console, $composer);

        $handler = null;

        foreach ($installer->getHandlers() as $handler) {
            if ($handler instanceof SpaHandler) {
                break;
            }
        }

        if (!isset($handler)) {
            return;
        }

        $handler->moduleStatusChanged($module->getPackage(), 'enabled', true);
    }
}