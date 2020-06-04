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

use Opis\Colibri\Core\Module;
use Throwable;

abstract class Installer
{
    /** @var Module */
    protected $module;

    /**
     * Installer constructor.
     * @param Module $module
     */
    final public function __construct(Module $module)
    {
        $this->module = $module;
    }

    /**
     * Install action
     */
    public function install()
    {

    }

    /**
     * Uninstall action
     */
    public function uninstall()
    {

    }

    /**
     * Enable action
     */
    public function enable()
    {

    }

    /**
     * Disable action
     */
    public function disable()
    {

    }

    /**
     * @param Throwable $e
     */
    public function installError(Throwable $e)
    {

    }

    /**
     * @param Throwable $e
     */
    public function enableError(Throwable $e)
    {

    }

    /**
     * @param Throwable $e
     */
    public function uninstallError(Throwable $e)
    {

    }

    /**
     * @param Throwable $e
     */
    public function disableError(Throwable $e)
    {

    }
}
