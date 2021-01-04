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

namespace Opis\Colibri\HTML;

use Opis\Colibri\Render\View;

class Collection extends View
{
    /**
     * Collection constructor.
     */
    public function __construct()
    {
        parent::__construct('html.collection', [
            'items' => [],
        ]);
    }

    /**
     * @param mixed $item
     * @param mixed $entry
     * @return Collection
     */
    public function add($item, $entry = null): self
    {
        if ($entry === null) {
            $this->vars['items'][] = $item;
        } else {
            $this->vars['items'][$entry] = $item;
        }
        return $this;
    }

    /**
     * @param Collection $collection
     * @return static
     */
    public function merge(Collection $collection): self
    {
        $array1 = $this->vars['items'];
        $array2 = $collection->vars['items'];
        $this->vars['items'] = array_merge($array1, $array2);
        return $this;
    }
}
