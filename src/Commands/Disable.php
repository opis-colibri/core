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

namespace Opis\Colibri\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    app, info, module
};

class Disable extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('disable')
            ->setDescription('Disable a module')
            ->addArgument('module', InputArgument::IS_ARRAY, 'A list of modules separated by space')
            ->addOption('uninstall', null, InputOption::VALUE_NONE, 'Uninstall modules')
            ->addOption('recursive', null, InputOption::VALUE_NONE, 'Uninstall & disable dependants');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (info()->installMode()) {
            $output->writeln('<error>The web application was not setup</error>');
            return 1;
        }

        $output->getFormatter()->setStyle('b-error', new OutputFormatterStyle('white', 'red', ['bold']));
        $output->getFormatter()->setStyle('warning', new OutputFormatterStyle('yellow'));
        $output->getFormatter()->setStyle('b-warning', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('b-info', new OutputFormatterStyle('green', null, ['bold']));

        $modules = $input->getArgument('module');
        $uninstall = $input->getOption('uninstall');
        $recursive = $input->getOption('recursive');

        foreach ($modules as $moduleName) {

            $module = module($moduleName);

            if (!$module->exists()) {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> doesn\'t exist.</error>');
                continue;
            }

            if (!$module->isEnabled()) {
                $output->writeln('<warning>Module <b-warning>' . $moduleName . '</b-warning> is already disabled.</warning>');
                continue;
            };

            if ($module->isApplicationInstaller()) {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> is hidden and can\'t be disabled.');
                continue;
            }

            if (app()->disable($module, true, $recursive)) {
                $output->writeln('<info>Module <b-info>' . $moduleName . '</b-info> was disabled.</info>');

                if ($uninstall) {
                    $command = $this->getApplication()->find('uninstall');
                    $args = [
                        'command' => 'uninstall',
                        'module' => [$moduleName],
                    ];
                    $command->run(new ArrayInput($args), $output);
                }
            } else {
                $output->writeln('<error>Module <b-error>' . $moduleName . '</b-error> could not be disabled.</error>');
            }
        }

        return 0;
    }
}
