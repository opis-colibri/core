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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    module
};

class About extends Command
{

    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Displays information about a module')
            ->addArgument('module', InputArgument::REQUIRED, 'Module name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('p', new OutputFormatterStyle('yellow', null, ['bold']));
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('green', null, ['bold']));
        $output->getFormatter()->setStyle('e', new OutputFormatterStyle('white', null, ['bold']));
        $output->getFormatter()->setStyle('r', new OutputFormatterStyle('red', null, ['bold']));
        $output->getFormatter()->setStyle('b', new OutputFormatterStyle('blue', null, ['bold']));
        $output->getFormatter()->setStyle('err', new OutputFormatterStyle('white', 'red', ['bold']));

        $moduleName = $input->getArgument('module');
        $module = module($moduleName);

        if (!$module->exists()) {
            $output->writeln('<error>Module <err>' . $moduleName . '</err> doesn\'t exist.</error>');
            exit;
        }

        $output->writeln('<p>Name:</p> <i>' . $module->name() . '</i>');
        $output->writeln('<p>Title:</p> <i>' . $module->description() . '</i>');
        $output->writeln('<p>Version:</p> <i>' . $module->version() . '</i>');

        if ($module->description() !== '') {
            $output->writeln('<p>Description:</p> <i>' . $module->description() . '</i>');
        } else {
            $output->writeln('<p>Description:</p> <e>No description provided</e>');
        }

        $dependencies = [];

        foreach ($module->dependencies() as $name => $dependency) {
            if ($dependency->exists()) {
                if ($dependency->isInstalled()) {
                    if ($dependency->isEnabled()) {
                        $dependencies[] = $name . '(<b>enabled</b>)';
                    } else {
                        $dependencies[] = $name . '(<b>disabled</b>)';
                    }
                } else {
                    $dependencies[] = $name . '(<e>uninstalled</e>)';
                }
            } else {
                $dependencies[] = $name . '(<r>missing</r>)';
            }
        }

        if (!empty($dependencies)) {
            $output->writeln('<p>Dependencies:</p> <i>' . implode(', ', $dependencies) . '</i>');
        } else {
            $output->writeln('<p>Dependencies:</p> <e>No dependencies</e>');
        }


        $output->writeln('<p>Application installer</p>: <i>' . ($module->isApplicationInstaller() ? 'TRUE' : 'FALSE') . '</i>');
        $output->writeln('<p>Directory</p>: <i>' . $module->directory() . '</i>');

        if ($module->collector()) {
            $output->writeln('<p>Collector:</p> <i>' . $module->collector() . '</i>');
        } else {
            $output->writeln('<p>Installer:</p> <e>No collector</e>');
        }

        if ($module->installer()) {
            $output->writeln('<p>Installer:</p> <i>' . $module->installer() . '</i>');
        } else {
            $output->writeln('<p>Installer:</p> <e>No installer</e>');
        }

        if ($module->assets()) {
            $output->writeln('<p>Assets:</p> <i>' . $module->assets() . '</i>');
        } else {
            $output->writeln('<p>Assets:</p> <e>No assets folder</e>');
        }
    }
}
