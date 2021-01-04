<?php
/* ===========================================================================
 * Copyright 2018-2020 Zindex Software
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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Module;

abstract class BaseCollector
{
    protected object $data;
    protected ?Module $crtModule = null;
    protected ?string $crtCollectorName = null;
    protected ?int $crtPriority = null;

    /**
     * BaseCollector constructor.
     * @param object $data
     */
    public function __construct(object $data)
    {
        $this->data = $data;
    }

    protected function data(): object
    {
        return $this->data;
    }

    public static function update(BaseCollector $instance, ?Module $module, ?string $collector, ?int $priority): void
    {
        $instance->crtModule = $module;
        $instance->crtCollectorName = $collector;
        $instance->crtPriority = $priority;
    }

    public static function getData(BaseCollector $instance): object
    {
        return $instance->data;
    }
}
