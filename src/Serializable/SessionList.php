<?php
/* ===========================================================================
 * Copyright 2019 Zindex Software
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

use Opis\Closure\SerializableClosure;
use Opis\Colibri\Session;
use Opis\Session\ISessionHandler;

class SessionList implements \Serializable
{
    /** @var array */
    private $sessions = [];

    /** @var ISessionHandler[] */
    private $handlers = [];

    /** @var Session[] */
    private $instances = [];

    /**
     * @param string $name
     * @param callable $callback
     * @param array $config
     */
    public function register(string $name, callable $callback, array $config)
    {
        $this->sessions[$name] = [
            'callback' => $callback,
            'config' => $config,
        ];
    }

    /**
     * @param string $name
     * @return Session|null
     */
    public function get(string $name): ?Session
    {
        if (!isset($this->sessions[$name])) {
            return null;
        }

        if (!isset($this->instances[$name])) {
            $this->instances[$name] = new Session($this->handler($name), $this->sessions[$name]['config']);
        }

        return $this->instances[$name];
    }

    /**
     * @param string $name
     * @return ISessionHandler|null
     */
    public function handler(string $name): ?ISessionHandler
    {
        if (!isset($this->sessions[$name])) {
            return null;
        }

        if (!isset($this->handlers[$name])) {
            return $this->handlers[$name] = ($this->sessions[$name]['callback'])($name);
        }

        return $this->handlers[$name];
    }

    /**
     * @inheritDoc
     */
    public function serialize()
    {
        SerializableClosure::enterContext();

        $result = [];

        foreach ($this->sessions as $name => $data) {
            if ($data['callback'] instanceof \Closure) {
                $data['callback'] = SerializableClosure::from($data['callback']);
            }
            $result[$name] = $data;
        }

        $result = serialize($result);

        SerializableClosure::exitContext();

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function unserialize($serialized)
    {
        $sessions = unserialize($serialized);
        $result = &$this->sessions;

        foreach ($sessions as $name => $data) {
            if ($data['callback'] instanceof SerializableClosure) {
                $data['callback'] = $data['callback']->getClosure();
            }
            $result[$name] = $data;
        }
    }
}