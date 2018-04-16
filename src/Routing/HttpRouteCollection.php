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

use Opis\HttpRouting\RouteCollection;

class HttpRouteCollection extends RouteCollection
{
    /** @var callable[] */
    protected $mixins = [];

    /**
     * HttpRouteCollection constructor.
     */
    public function __construct()
    {
        parent::__construct(static::class . '::factory');
    }

    /**
     * @param string $name
     * @param callable $callback
     * @return static|HttpRouteCollection
     */
    public function mixin(string $name, callable $callback): self
    {
        $this->mixins[$name] = $callback;
        return $this;
    }

    /**
     * @return callable[]
     */
    public function getMixins(): array
    {
        return $this->mixins;
    }

    /**
     * @param HttpRouteCollection $collection
     * @param string $id
     * @param string $pattern
     * @param callable $action
     * @param string|null $name
     * @return HttpRoute
     */
    protected static function factory(
        HttpRouteCollection $collection,
        string $id,
        string $pattern,
        callable $action,
        string $name = null
    ): HttpRoute {
        return new HttpRoute($collection, $id, $pattern, $action, $name);
    }
}