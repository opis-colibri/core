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

namespace Opis\Colibri\SPA;

use Opis\Colibri\Module;

class Extension
{
    /** @var string */
    private $name;
    /** @var string */
    private $module;
    /** @var array */
    private $conf;
    /** @var string */
    private $source;

    /**
     * Extension constructor.
     * @param string $name
     * @param string $module
     * @param string $source
     * @param array $conf
     */
    public function __construct(string $name, string $module, string $source, array $conf)
    {
        $this->name = $name;
        $this->module = $module;
        $this->source = $source;
        $this->conf = $conf;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function source(): string
    {
        return $this->source;
    }

    /**
     * @return Module
     */
    public function module(): Module
    {
        return \Opis\Colibri\Functions\module($this->module);
    }

    /**
     * @return array
     */
    public function config(): array
    {
        return $this->conf;
    }
}