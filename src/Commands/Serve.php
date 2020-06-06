<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Serve extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('serve')
            ->setDescription('Start the built-in PHP server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'HTTP host', 'localhost')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'HTTP port', 8080)
            ->addOption('use-ini', null, InputOption::VALUE_NONE, 'Use local php.ini file');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $host = $input->getOption('host');
        $port = $input->getOption('port');
        $ini = $input->getOption('use-ini');

        $command = 'php -S ' . $host . ':' . $port . ($ini ? ' -c php.ini' : '') . ' -t public/ router.php';

        $status = 0;
        passthru($command, $status);
        return $status;
    }
}