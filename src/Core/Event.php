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

use Opis\Events\Event as BaseEvent;

class Event extends BaseEvent
{
    /** @var mixed */
    private $data;

    /**
     * @param string $name
     * @param null $data
     * @param bool $cancelable
     */
    public function __construct(string $name, $data = null, bool $cancelable = false)
    {
        $this->data = $data;
        parent::__construct($name, $cancelable);
    }

    /**
     * @return mixed
     */
    public function data()
    {
        return $this->data;
    }
}