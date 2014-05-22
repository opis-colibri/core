<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2013 Marius Sarca
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

namespace Opis\Colibri\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;

use Opis\Colibri\App;
use Opis\Colibri\Module;


class RefreshCommand extends Command
{
    
    protected function configure()
    {
        $this
            ->setName('refresh')
            ->setDescription('Clear system cache');
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if(App::systemCache()->clear())
        {
            $output->writeln('<info>System cache cleared.</info>');
        }
        else
        {
            $output->writeln('<error>System cache was not cleared.</error>');
        }
    }
    
}
