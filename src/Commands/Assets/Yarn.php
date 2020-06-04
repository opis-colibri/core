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

namespace Opis\Colibri\Commands\Assets;

use Opis\Colibri\Plugin\YarnPackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use function Opis\Colibri\info;

class Yarn extends Command
{
    protected array $allowedCommands = ['install', 'upgrade', 'add', 'remove', 'why', 'link', 'unlink'];

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('assets:yarn')
            ->setDescription('Executes yarn commands')
            ->addArgument('cmd', InputArgument::REQUIRED, 'Yarn command')
            ->addArgument('packages', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Package names')
            ->addOption('dev', null, InputOption::VALUE_OPTIONAL)
            ->addOption('prod', null, InputOption::VALUE_OPTIONAL);
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

        if ($input->getOption('dev')) {
            $args[] = '--dev';
        }

        if ($input->getOption('prod')) {
            $args[] = '--prod';
        }

        $args[] = '--no-bin-links';

        return (new YarnPackageManager())->command($cmd, $args, info()->rootDir(), info()->assetsDir());
    }
}