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

namespace Opis\Colibri;

use Opis\Colibri\Commands\{
    Collect as CollectCommand, Disable as DisableCommand, Enable as EnableCommand,
    About as AboutCommand, Install as InstallCommand, Modules as ModulesCommand, Uninstall as UninstallCommand,
    Assets\Build as BuildAssetsCommand,
    Spa\Build as BuildSpaCommand
};
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use function Opis\Colibri\Functions\app;


class Console
{
    /**
     * Run a command
     * @throws \Exception
     */
    public function run()
    {
        $application = new ConsoleApplication();

        foreach ($this->commands() as $command) {
            $application->add($command);
        }

        $application->run();
    }

    /**
     *  Get a list of commands
     *
     * @return  Command[]
     */
    public function commands(): array
    {
        $commands = [
            'about' => new AboutCommand(),
            'assets:build' => new BuildAssetsCommand(),
            'spa:build' => new BuildSpaCommand(),
            'collect' => new CollectCommand(),
            'disable' => new DisableCommand(),
            'enable' => new EnableCommand(),
            'install' => new InstallCommand(),
            'modules' => new ModulesCommand(),
            'uninstall' => new UninstallCommand(),
        ];

        foreach (app()->getCollector()->getCommands() as $name => $builder) {
            $commands[$name] = $builder();
        }

        return $commands;
    }
}
