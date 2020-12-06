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

use Opis\Colibri\Serializable\Collection;

/**
 * @method Collection data()
 */
class ViewEngineCollector extends BaseCollector
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct(new Collection());
    }

    /**
     * Defines a new view engine
     *
     * @param callable $factory
     *
     * @return ViewEngineCollector
     */
    public function register(callable $factory): self
    {
        $key = $this->data()->length() + 1;
        $this->data()->add($key, [$factory, $this->crtPriority]);
        return $this;
    }
}
