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

namespace Opis\Colibri\Routing;

use Opis\Routing\Route;
use Opis\HttpRouting\Route as BaseHttpRoute;

class HttpGroupRoute extends HttpRoute
{
    /** @var bool */
    protected $inheriting = false;

    /**
     * If this is true, then no overwrite will occur
     * @param bool $inheriting
     * @return HttpGroupRoute
     */
    public function setIsInheriting(bool $inheriting): self
    {
        $this->inheriting = $inheriting;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, $value): Route
    {
        // Handles middleware, domain, method, secure, filter, guard, *
        if ($this->inheriting && $this->has($name)) {
            return $this;
        }
        return parent::set($name, $value);
    }

    /**
     * @inheritDoc
     *
     */
    public function callback(string $name, callable $callback): BaseHttpRoute
    {
        $list = $this->get('callbacks', []);

        if ($this->inheriting && isset($list[$name])) {
            return $this;
        }

        $list[$name] = $callback;
        parent::set('callbacks', $list);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function bind(string $name, callable $callback): Route
    {
        if ($this->inheriting && isset($this->bindings[$name])) {
            return $this;
        }
        return parent::bind($name, $callback);
    }

    /**
     * @inheritDoc
     */
    public function placeholder(string $name, string $value): Route
    {
        if ($this->inheriting && array_key_exists($name, $this->placeholders)) {
            return $this;
        }
        return parent::placeholder($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function implicit(string $name, $value): Route
    {
        if ($this->inheriting && array_key_exists($name, $this->defaults)) {
            return $this;
        }
        return parent::implicit($name, $value);
    }
}