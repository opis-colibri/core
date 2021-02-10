<?php
/* ===========================================================================
 * Copyright 2018-2021 Zindex Software
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

namespace Opis\Colibri\HTML;

use Opis\Colibri\Render\View;

class Collection extends View
{
    public function __construct()
    {
        parent::__construct('html.collection', [
            'items' => [],
        ]);
    }

    public function add(mixed $item, int|string|null $entry = null): static
    {
        if ($entry === null) {
            $this->vars['items'][] = $item;
        } else {
            $this->vars['items'][$entry] = $item;
        }
        return $this;
    }

    public function merge(Collection $collection): static
    {
        $this->vars['items'] = array_merge(
            $this->vars['items'],
            $collection->vars['items'],
        );
        return $this;
    }
}
