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

namespace Opis\Colibri\Core;

use function Opis\Colibri\Functions\{
    random_str, session
};

class CSRFToken
{
    /** @var string */
    protected $sessionKey;

    /** @var int */
    protected $maxNumber;

    /**
     * @param string $key
     * @param int $max
     */
    public function __construct(string $key = 'opis_colibri_csrf', int $max = 10)
    {
        $this->sessionKey = $key;
        $this->maxNumber = $max;
    }

    /**
     * @return string
     */
    public function generate(): string
    {
        $tokens = session()->get($this->sessionKey, []);

        if (!empty($tokens)) {
            $tokens = array_slice($tokens, 0, $this->maxNumber - 1);
        }

        $token = random_str(32);

        array_unshift($tokens, $token);

        session()->set($this->sessionKey, $tokens);

        return $token;
    }

    /**
     * @param string $value
     * @param bool $remove
     * @return bool
     */
    public function validate(string $value, bool $remove = true): bool
    {
        $tokens = session()->get($this->sessionKey, []);

        $key = array_search($value, $tokens);

        if ($key !== false) {
            if (!$remove) {
                return true;
            }
            unset($tokens[$key]);
            session()->set($this->sessionKey, array_values($tokens));
            return true;
        }

        return false;
    }

    /**
     * @param string $value
     * @return bool
     */
    public function remove(string $value): bool
    {
        $tokens = session()->get($this->sessionKey, []);

        $key = array_search($value, $tokens);

        if ($key !== false) {
            unset($tokens[$key]);
            session()->set($this->sessionKey, array_values($tokens));
            return true;
        }

        return false;
    }
}