<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

use Opis\Routing\CompiledRoute;
use SplQueue;

abstract class Middleware
{
    private $queue;
    private $compiledRoute;

    /**
     * Middleware constructor.
     * @param SplQueue $queue
     * @param CompiledRoute $compiledRoute
     */
    final function __construct(SplQueue $queue, CompiledRoute $compiledRoute)
    {
        $this->queue = $queue;
        $this->compiledRoute = $compiledRoute;
    }

    /**
     * @return mixed
     */
    final public function next()
    {
        do {
            if($this->queue->isEmpty()){
                return $this->compiledRoute->invokeAction();
            }
            /** @var Middleware $middleware */
            $middleware = $this->queue->dequeue();

        } while(!is_callable($middleware));

        $args = $this->compiledRoute->getArguments($middleware);
        return $middleware(...$args);
    }
}