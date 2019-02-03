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

namespace Opis\Colibri\Commands\Spa;

use Opis\Colibri\Application;
use Opis\Colibri\Core\{
    SPA\DataHandler
};
use function Opis\Colibri\Functions\{
    app, info
};
use Symfony\Component\Console\{Command\Command,
    Input\InputArgument,
    Input\InputInterface,
    Input\InputOption,
    Output\OutputInterface
};

class Build extends Command
{
    protected function configure()
    {
        $this
            ->setName('spa:build')
            ->setDescription('Rebuild SPAs')
            ->addArgument('apps', InputArgument::IS_ARRAY|InputArgument::OPTIONAL, " A list of SPAs")
            ->addOption('clean', null, InputOption::VALUE_NONE, 'Clean build');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $apps = $input->getArgument('apps');
        $clean = $input->getOption('clean');

        $data = new DataHandler(info());

        foreach ($apps as $app) {
            $data->rebuild($app, $clean);
        }

        $data->save();

        $cmd = new class extends Application {

            public function __construct()
            {
                // DO NOT INVOKE PARENT
            }

            public function appDumpAutoload(Application $app)
            {
                return $app->dumpAutoload();
            }
        };

        $cmd->appDumpAutoload(app());
    }
}