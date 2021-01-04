<?php
/* ===========================================================================
 * Copyright 2020-2021 Zindex Software
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

use Opis\Colibri\Session\{Session, CookieContainer};

class SessionCollection extends Collection
{
    /**
     * @param string $name
     * @param callable $callback Callback that creates a SessionHandler
     * @param array $config
     */
    public function register(string $name, callable $callback, array $config = []): void
    {
        $this->add($name, [
            'callback' => $callback,
            'config' => $config,
        ]);
    }

    public function getSession(string $name, CookieContainer $container): ?Session
    {
        if (null === $session = $this->get($name)) {
            return null;
        }

        return new Session($container, $session['callback']($name), $session['config']);
    }
}