<?php
/* ===========================================================================
 * Copyright 2020-2021 Zindex Software
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

namespace Opis\Colibri\Events;

class Event
{
    private string $name;
    private bool $cancelable;
    private mixed $data;
    private bool $isCanceled = false;

    public function __construct(string $name, bool $cancelable = false, mixed $data = null)
    {
        $this->name = $name;
        $this->cancelable = $cancelable;
        $this->data = $data;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isCancelable(): bool
    {
        return $this->cancelable;
    }

    public function isCanceled(): bool
    {
        return $this->isCanceled;
    }

    public function cancel(): bool
    {
        if (!$this->cancelable) {
            return false;
        }
        return $this->isCanceled = true;
    }

    public function data(): mixed
    {
        return $this->data;
    }
}