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

namespace Opis\Colibri\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    info
};

class BootstrapInit extends Command
{
    protected function configure()
    {
        $this
            ->setName('bootstrap-init')
            ->setDescription('Init bootstrap file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = info()->bootstrapFile();

        if (file_exists($file) && is_file($file)) {
            return;
        }

        $segments = explode(DIRECTORY_SEPARATOR, $file);
        $index = count($segments) - 1;
        $segments[$index] = 'dist.' . $segments[$index];
        $source = implode(DIRECTORY_SEPARATOR, $segments);

        if (!file_exists($source) || !is_file($source)) {
            return;
        }

        rename($source, $file);
    }
}