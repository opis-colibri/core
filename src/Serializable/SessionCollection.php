<?php
/* ===========================================================================
 * Copyright 2020 Zindex Software
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

namespace Opis\Colibri\Serializable;

use Opis\Session\SessionHandler;
use Opis\Colibri\Core\Session;
use function Opis\Colibri\app;

class SessionCollection extends Collection
{
    /** @var Session[] */
    private array $sessions = [];

    /** @var SessionHandler[] */
    private array $handlers = [];

    /**
     * @param string $name
     * @param callable $callback
     * @param array $config
     */
    public function register(string $name, callable $callback, array $config)
    {
        $this->add($name, [
            'callback' => $callback,
            'config' => $config
        ]);
    }

    public function getSession(string $name): ?Session
    {
        if (isset($this->sessions[$name])) {
            return $this->sessions[$name];
        }

        if (null === $session = $this->get($name)) {
            return null;
        }

        return $this->sessions[$name] = new Session(
            $session['config'],
            $this->getHandler($name)
        );
    }

    public function getHandler(string $name): ?SessionHandler
    {
        if (isset($this->handlers[$name])) {
            return $this->handlers[$name];
        }

        if (null === $session = $this->get($name)) {
            return null;
        }

        return $this->handlers[$name] = $session['callback']($name);
    }
}