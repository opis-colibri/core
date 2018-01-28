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

namespace Opis\Colibri\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    module, info
};

class Uninstall extends Command
{

    protected function configure()
    {
        $this
            ->setName('uninstall')
            ->setDescription('Uninstall a module')
            ->addArgument('module', InputArgument::IS_ARRAY, 'A list of modules separated by space');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (info()->installMode()) {
            die('Opis Colibri is not installed' . PHP_EOL);
        }

        $output->getFormatter()->setStyle('b-error', new OutputFormatterStyle('white', 'red', array('bold')));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('b-warning', new OutputFormatterStyle('yellow', null, array('bold')));
        $output->getFormatter()->setStyle('b-info', new OutputFormatterStyle('green', null, array('bold')));

        $modules = $input->getArgument('module');

        foreach ($modules as $moduleName) {

            $module = module($moduleName);

            if (!$module->exists()) {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> doesn\'t exist.</error>');
                continue;
            }

            if (!$module->isInstalled()) {
                $output->writeln('<warning>Module <b-warning>' . $moduleName . '</b-warning> is already uninstalled.</warning>');
                continue;
            }

            if ($module->isApplicationInstaller()) {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> is hidden and can\'t be uninstalled.');
                continue;
            }

            if ($module->uninstall()) {
                $output->writeln('<info>Module <b-info>' . $moduleName . '</b-info> was uninstalled.</info>');
            } else {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> could not be uninstalled.</error>');
            }
        }
    }
}
