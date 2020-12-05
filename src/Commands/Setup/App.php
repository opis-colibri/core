<?php
/* ===========================================================================
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

namespace Opis\Colibri\Commands\Setup;

use Symfony\Component\Console\{Command\Command, Input\InputInterface, Input\InputOption, Output\OutputInterface};
use function Opis\Colibri\{info, app};

class App extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('app')
            ->setDescription('Setup web application')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force setup')
            ->addOption('bootstrap', null, InputOption::VALUE_NONE, 'Perform bootstrap');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = info()->writableDir() . '/setup';

        if ($input->getOption('force')) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        if (file_exists($file)) {
            $output->writeln('<info>The application was already setup</info>');
            return 0;
        }

        $_ENV['OPIS_COLIBRI_SKIP_BOOTSTRAP'] = !$input->getOption('bootstrap');

        $app = app()->bootstrap();
        $app->getApplicationInitializer()->setup($app);

        file_put_contents($file, time());

        $output->writeln('<info>The application was successfully setup</info>');

        return 0;
    }
}
