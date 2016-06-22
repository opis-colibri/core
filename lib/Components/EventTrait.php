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

namespace Opis\Colibri\Components;

use Opis\Colibri\Event;

trait EventTrait
{
    use ApplicationTrait;

    /**
     * @param string $event
     * @param bool $cancelable
     * @return Event
     */
    protected function emit(string $event, bool $cancelable = false): Event
    {
        return $this->dispatch(new Event($this->getApp(), $event, $cancelable));
    }

    /**
     * @param Event $event
     * @return Event
     */
    protected function dispatch(Event $event): Event
    {
        return $this->getApp()->getEventTarget()->dispatch($event);
    }
}