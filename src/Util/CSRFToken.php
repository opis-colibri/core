<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

namespace Opis\Colibri\Util;

use function Opis\Colibri\{session};

class CSRFToken
{

    /** @var    array */
    protected $values = array();

    /** @var    string */
    protected $sessionKey;

    /** @var    int */
    protected $maxNumber;

    /**
     * Constructor
     *
     * @param   string $key (optional)
     * @param   int $max (optional)
     */
    public function __construct(string $key = 'opis_colibri_csrf', int $max = 10)
    {
        $this->sessionKey = $key;
        $this->maxNumber = $max;
    }

    /**
     * Generates a new CSRF token
     *
     * @return  string
     */
    public function generate(): string
    {
        $tokens = session()->get($this->sessionKey, array());

        if (!empty($tokens)) {
            $tokens = array_slice($tokens, 0, $this->maxNumber - 1);
        }

        $token = $this->getRandomToken();

        array_unshift($tokens, $token);

        session()->set($this->sessionKey, $tokens);

        return $token;
    }

    /**
     * Generate random token
     *
     * @param   int $length (optional) Token's length
     *
     * @return  string
     */
    protected function getRandomToken(int $length = 32): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $token = '';

        for ($i = 0, $l = strlen($chars); $i < $length; $i++) {
            $token .= $chars[rand(0, $l - 1)];
        }

        return $token;
    }

    /**
     * Validate a CSRF token
     *
     * @param   string $value Token
     *
     * @return  boolean
     */
    public function validate(string $value): bool
    {
        $tokens = session()->get($this->sessionKey, array());

        $key = array_search($value, $tokens);

        if ($key !== false) {
            unset($tokens[$key]);
            session()->set($this->sessionKey, $tokens);
            return true;
        }

        return false;
    }
}
