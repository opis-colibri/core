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

namespace Opis\Colibri\Commands\Assets;

use Composer\Factory;
use Composer\IO\ConsoleIO;
use Opis\Colibri\{
    Handlers\AssetHandler, PackageInstaller, Module
};
use Symfony\Component\Console\{
    Command\Command,
    Formatter\OutputFormatterStyle,
    Helper\HelperSet,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};
use Symfony\Component\Filesystem\Filesystem;
use function Opis\Colibri\Functions\{
    info, app, module
};

class Build extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('assets:build')
            ->setDescription("Build modules' assets")
            ->setAliases(['build-assets'])
            ->addArgument('module', InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'A list of modules separated by space')
            ->addOption('dependencies', null, InputOption::VALUE_NONE, 'Install/Uninstall asset dependencies');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output
            ->getFormatter()
            ->setStyle('b-info', new OutputFormatterStyle('yellow', null, ['bold']));

        $fs = new Filesystem();
        $console = new ConsoleIO($input, $output, new HelperSet());
        $appInfo = info();
        $rootDir = $appInfo->rootDir();
        $composerFile = $appInfo->composerFile();
        $composer = (new Factory())->createComposer($console, $composerFile, false, $rootDir);
        $installer = new PackageInstaller($appInfo, $console, $composer);

        $handler = null;

        foreach ($installer->getHandlers() as $handler) {
            if ($handler instanceof AssetHandler) {
                break;
            }
        }

        if (!isset($handler)) {
            return;
        }

        $modules = $input->getArgument('module');
        $dependencies = $input->getOption('dependencies');

        if (empty($modules)) {
            $modules = app()->getModules();
        } else {
            $modules = array_map(function ($value) {
                return module($value);
            }, $modules);
        }

        /** @var Module $module */
        foreach ($modules as $module) {

            if (!$module->exists()) {
                continue;
            }

            if (!$module->assets()) {
                continue;
            }

            $output->writeln('<info>Building assets for <b-info>' . $module->name() . '</b-info> module...</info>');

            $name = str_replace('/', '.', $module->name());

            if ($dependencies) {
                $handler->uninstall($module->package());
            } else {
                $fs->remove(info()->assetsDir() . DIRECTORY_SEPARATOR . $name);
            }

            if ($dependencies) {
                $handler->install($module->package());
            } else {
                $fs->mirror($module->assets(), $appInfo->assetsDir() . DIRECTORY_SEPARATOR . $name);
            }
        }
    }
}