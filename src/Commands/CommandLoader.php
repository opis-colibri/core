<?php
/* ============================================================================
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

namespace Opis\Colibri\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\CommandLoader\CommandLoaderInterface;

class CommandLoader implements CommandLoaderInterface
{
    /** @var string[] */
    protected $classes;

    /** @var callable[] */
    protected $builders;

    /**
     * CommandLoader constructor.
     * @param callable[] $builders
     * @param string[] $classes
     */
    public function __construct(array $builders = [], array $classes = [])
    {
        $classes = array_filter($classes, function ($class) {
            return is_string($class) && is_subclass_of($class, Command::class, true);
        });

        $this->classes = [
                'about' => About::class,
                'collect' => Collect::class,
                'setup' => Setup::class,
                'create-module' => CreateModule::class,

                'modules' => Modules::class,
                'install' => Install::class,
                'enable' => Enable::class,
                'disable' => Disable::class,
                'uninstall' => Uninstall::class,

                'assets:build' => Assets\Build::class,
                'assets:yarn' => Assets\Yarn::class,
                'spa:build' => Spa\Build::class,
            ] + $classes;

        $this->builders = array_filter($builders, function ($callable) {
            return is_callable($callable);
        });
    }

    /**
     * @inheritdoc
     */
    public function get($name)
    {
        if (isset($this->classes[$name])) {
            $cls = $this->classes[$name];
            return new $cls($name);
        }

        if (isset($this->builders[$name])) {
            $func = $this->builders[$name];
            return $func($name);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function has($name)
    {
        return isset($this->classes[$name]) || isset($this->builders[$name]);
    }

    /**
     * @inheritdoc
     */
    public function getNames()
    {
        return array_unique(array_merge(array_keys($this->classes), array_keys($this->builders)));
    }
}