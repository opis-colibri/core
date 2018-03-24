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


class SpaInfo
{
    /** @var string */
    private $name;
    /** @var string */
    private $dir;
    /** @var string[] */
    private $modules;
    /** @var string */
    private $owner;
    /** @var string */
    private $dist;

    /**
     * SpaInfo constructor.
     * @param string $name
     * @param string $dir
     * @param string $dist
     * @param string $owner
     * @param array $modules
     */
    public function __construct(string $name, string $owner, string $dir, string $dist, array $modules)
    {
        $this->name = $name;
        $this->dir = $dir;
        $this->dist = $dist;
        $this->owner = $owner;
        $this->modules = $modules;
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
    public function dir(): string
    {
        return $this->dir;
    }

    /**
     * @return string
     */
    public function distDir(): string
    {
        return $this->dist;
    }

    /**
     * @return string
     */
    public function owner(): string
    {
        return $this->owner;
    }

    /**
     * @return string[]
     */
    public function modules(): array
    {
        return $this->modules;
    }
}