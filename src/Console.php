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

namespace Opis\Colibri;

use Opis\Colibri\Application as WebApplication;
use Opis\Colibri\Commands\CommandLoader;
use Symfony\Component\Console\Application as ConsoleApplication;

class Console extends ConsoleApplication
{
    /**
     * Console constructor.
     * @param WebApplication $app
     */
    public function __construct(WebApplication $app)
    {
        parent::__construct("Opis Colibri");
        $this->setAutoExit(false);
        $this->setCommandLoader(new CommandLoader($app->getCollector()->getCommands()));
    }
}

// opis/view => Opis\View\DefaultView
// opis/routing => Opis\Routing\Router
// opis/colibri => Opis\Colibri\Application