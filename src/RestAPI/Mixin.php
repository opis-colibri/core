<?php
/* ============================================================================
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

namespace Opis\Colibri\RestAPI;

use RuntimeException;
use Opis\Routing\{Route, Mixin as BaseMixin};

class Mixin extends BaseMixin
{
    /**
     * @param Route $route
     * @param array|null $config
     */
    public function __invoke(Route $route, ?array $config)
    {
        if (!isset($config['resolver']) || !is_string($config['resolver'])) {
            throw new RuntimeException("Resolver class was not provided");
        }

        $class = $config['resolver'];

        if (!class_exists($class)) {
            throw new RuntimeException("Resolver class {$class} does not exists");
        }

        if (!is_subclass_of($class, ControllerResolver::class)) {
            throw new RuntimeException("Resolver class {$class} must extend " . ControllerResolver::class);
        }

        $middleware = $route->getProperties()['middleware'] ?? [];

        if ($config['default-middleware'] ?? true !== false) {
            $middleware = array_unique(array_merge([CORSMiddleware::class], $middleware));
        }

        $route
            ->bind('controller', $class . '::bindController')
            ->bind('action', $class . '::bindAction')
            ->bind('data', $class . '::bindData')
            ->middleware(...$middleware);
    }
}