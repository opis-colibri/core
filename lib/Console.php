<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

use Exception;
use Symfony\Component\Console\Application as ConsoleApplication;

class Console
{
    /** @var    \Opis\Colibri\Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Run a command
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
     * @return  array
     */
    public function commands()
    {
        $commands = array();

        foreach ($this->app->getCollector()->getCommands() as $name => $builder) {
            try {
                $command = call_user_func($builder);
                if ($command instanceof Command) {
                    $command->setApp($this->app);
                    $commands[$name] = $command;
                }
            } catch (Exception $e) {

            }
        }

        return $commands;
    }
}
