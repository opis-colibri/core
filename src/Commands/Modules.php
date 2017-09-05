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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    app
};

class Modules extends Command
{

    protected function configure()
    {
        $this
            ->setName('modules')
            ->setDescription('List all available modules')
            ->addOption('hidden', null, InputOption::VALUE_NONE, 'List hidden modules');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->getFormatter()->setStyle('green', new OutputFormatterStyle('green', null, array('bold')));
        $output->getFormatter()->setStyle('blue', new OutputFormatterStyle('blue', null, array('bold')));
        $output->getFormatter()->setStyle('white', new OutputFormatterStyle('white', null, array('bold')));
        $output->getFormatter()->setStyle('yellow', new OutputFormatterStyle('yellow', null, array('bold')));

        $hidden = $input->getOption('hidden');

        $ws = str_repeat(' ', 24);

        foreach (app()->getModules() as $name => $module) {
            
            if (!$hidden && $module->isHidden()) {
                continue;
            }

            $text = $name . substr($ws, strlen($name)) . "<white>" . $module->title() . '</white>';

            if ($module->isEnabled()) {
                $output->writeln('<green>' . $text . '</green>');
            } elseif ($module->isInstalled()) {
                $output->writeln('<blue>' . $text . '</blue>');
            } else {
                $output->writeln('<yellow>' . $text . '</yellow>');
            }
        }
    }
}
