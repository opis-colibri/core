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

use Dotenv\Dotenv;
use Symfony\Component\Console\{Command\Command, Input\InputInterface, Input\InputOption, Output\OutputInterface};
use function Opis\Colibri\{info, app};

class Env extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('env')
            ->setDescription('Generate environment cache file')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete cache file')
            ->addOption('validate', null, InputOption::VALUE_NONE, 'Perform validation only');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = info()->envFile();

        if ($input->getOption('delete')) {
            if (is_file($file)) {
                unlink($file);
            }
            $output->writeln('<info>Environment cache file was deleted</info>');
            return 0;
        }

        $dotenv = Dotenv::createMutable(info()->rootDir());
        $content = '<?php return ' . var_export($dotenv->load(), true) . ';' . PHP_EOL;
        app()->getApplicationInitializer()->validateEnvironmentVariables($dotenv);

        if ($input->getOption('validate')) {
            unset($content);
            $output->writeln('<info>Environment settings are valid</info>');
            return 0;
        }

        if (false === file_put_contents($file, $content)) {
            $output->writeln('<error>Could not generate environment cache file</error>');
        } else {
            $output->writeln('<info>Environment cache file was generated</info>');
        }

        unset($content);

        return 0;
    }
}
