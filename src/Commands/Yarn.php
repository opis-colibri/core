<?php
/* ============================================================================
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

namespace Opis\Colibri\Commands;

use Opis\Colibri\Core\YarnPackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Filesystem\Filesystem;
use function Opis\Colibri\{info, module};

class Yarn extends Command
{
    private array $allowedCommands = ['install', 'upgrade', 'add', 'remove', 'why', 'link', 'unlink'];

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('yarn')
            ->setDescription('Executes yarn commands')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Yarn command')
            ->addArgument('packages', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Package names')
            ->addOption('module', null, InputOption::VALUE_NONE, 'Treat package names as modules')
            ->addOption('dev', null, InputOption::VALUE_NONE)
            ->addOption('prod', null, InputOption::VALUE_NONE);
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('err', new OutputFormatterStyle('white', 'red', ['bold']));

        $cmd = $input->getArgument('cmd');

        if (!in_array($cmd, $this->allowedCommands)) {
            $output->writeln(sprintf('<err>Unrecognized command %s</err>', $cmd));
            $output->writeln(sprintf('Available commands: %s', implode(', ', $this->allowedCommands)));
            return -1;
        }

        $args = $input->getArgument('packages');
        $isModule = $input->getOption('module');

        if ($cmd === 'add' && $isModule) {
            if (null === $args = $this->mapAddModules($args, $output)) {
                return -1;
            }
        } elseif ($cmd === 'remove' && $isModule) {
            if (null === $args = $this->mapRemoveModules($args, $output)) {
                return -1;
            }
        } else {
            if ($input->getOption('dev')) {
                $args[] = '--dev';
            } elseif ($input->getOption('prod')) {
                $args[] = '--prod';
            }
        }

        $args[] = '--no-bin-links';

        return (new YarnPackageManager())->command($cmd, $args, info()->rootDir(), info()->assetsDir());
    }

    private function mapAddModules(array $modules, OutputInterface $output): ?array
    {
        $result = [];
        $rootDir = info()->rootDir();
        $fs = new Filesystem();

        foreach ($modules as $moduleName) {

            $module = module($moduleName);

            if (!$module->exists()) {
                $output->writeln(sprintf('<err>Invalid module name %s</err>', $moduleName));
                return null;
            }

            if (null === $assets = $module->assets()) {
                $output->writeln(sprintf('<err>Module %s does not provide an assets folder</err>', $moduleName));
                return null;
            }

            $file = $assets . DIRECTORY_SEPARATOR . 'packages.json';

            if (!is_file($file)) {
                $output->writeln(sprintf('<err>Module %s does not provide a package.json file</err>', $module));
                return null;
            }

            $package = json_decode(file_get_contents($file));
            $packageName = str_replace('/', '.', $moduleName);

            if (!is_object($package) || !property_exists($package, 'name')) {
                $output->writeln(sprintf('<err>Module %s does not provide a valid package.json file</err>', $module));
                return null;
            }

            if ($package->name !== $packageName) {
                $output->writeln(sprintf('<err>Module %s must provide a package named %s</err>', $module, $packageName));
                return null;
            }

            $result[] = $fs->makePathRelative($rootDir, $assets);
        }

        return $result;
    }

    private function mapRemoveModules(array $modules, OutputInterface $output): ?array
    {
        $result = [];

        foreach ($modules as $moduleName) {

            $module = module($moduleName);

            if (!$module->exists()) {
                $output->writeln(sprintf('<err>Invalid module name %s</err>', $moduleName));
                return null;
            }

            $result[] = str_replace('/', '.', $moduleName);
        }

        return $result;
    }
}