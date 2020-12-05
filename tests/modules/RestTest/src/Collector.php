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

namespace Test\RestTest;

use Opis\Colibri\Collector as BaseCollector;
use Opis\Colibri\Collectors\{JsonSchemaResolversCollector, RouteCollector};
use Opis\Colibri\Priority;
use Test\RestTest\API\RestResolver;

class Collector extends BaseCollector
{
    /**
     * @param JsonSchemaResolversCollector $resolver
     */
    public function jsonSchemaResolvers(JsonSchemaResolversCollector $resolver)
    {
        $resolver->addLoader('test.rest-test', __DIR__ . '/../schema');
    }

    #[Priority(100)]
    public function apiRoutes(RouteCollector $route)
    {
        $route
            ->group(static function (RouteCollector $route) {
                // Same instance returned, you can call multiple times
                $ctrl = RestResolver::controller();

                $route('/{controller}', $ctrl, ['GET', 'OPTIONS', 'POST'])
                    ->whereIn('controller', ['custom-controller'])
                    ->implicit('action', 'collection');

            }, RestResolver::PREFIX)
            // Add the rest resolver
            ->mixin(RestResolver::class, [
                // you can disable cors middleware
                // 'no-cors' => true
                // no-cors is the only option available
            ]);
    }
}