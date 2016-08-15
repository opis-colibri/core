<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri;

use InvalidArgumentException;
use Opis\Events\Event as BaseEvent;
use Opis\Events\EventTarget;
use Opis\Events\RouteCollection;
use Opis\Routing\Route;

class CollectorTarget extends EventTarget
{

    /**
     * @param BaseEvent|CollectorEntry $event
     *
     * @return BaseEvent
     */
    public function dispatch(BaseEvent $event): BaseEvent
    {
        if (!$event instanceof CollectorEntry) {
            throw new InvalidArgumentException('Invalid event type. Expected ' . CollectorEntry::class);
        }

        $this->collection->sort();

        $collector = $event->getCollector();

        /** @var Route $handler */
        foreach ($this->router->match($event) as $handler){
            $callback = $handler->getAction();
            $callback($collector);
        }

        return $event;
    }
}