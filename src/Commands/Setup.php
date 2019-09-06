<?php
/* ===========================================================================
 * Copyright 2019 Zindex Software
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

use Opis\Colibri\Core\ModuleManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Opis\Colibri\Functions\{
    app, info
};

class Setup extends Command
{
    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            ->setName('setup')
            ->setDescription('Setup web application');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $info = info();

        if (!$info->installMode()) {
            $output->writeln('<error>The web application is already setup</error>');
            return 1;
        }

        $app = app();

        $moduleManager = new ModuleManager($info->vendorDir(), function () use($app){
            return $app->getConfig();
        });

        if ($this->hasInstaller($moduleManager)) {
            $output->writeln('<error>The web application provides an installer module</error>');
            return 1;
        }

        $source = __DIR__ . '/../../init.php.stub';
        $dest = $info->initFile();

        if (!copy($source, $dest)) {
            $output->writeln('<error>Failed to setup web application</error>');
            return 1;
        }

        $output->writeln('<info>The web application was successfully setup</info>');
        return 0;
    }

    /**
     * @param ModuleManager $manager
     * @return bool
     */
    private function hasInstaller(ModuleManager $manager): bool
    {
        foreach ($manager->modules() as $module) {
            if ($module->isApplicationInstaller()) {
                return true;
            }
        }

        return false;
    }
}