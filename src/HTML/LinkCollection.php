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

class LinkCollection extends Collection
{
    /**
     * @param string $entry
     * @param callable $callback
     * @return LinkCollection
     */
    public function custom(string $entry, callable $callback): self
    {
        $link = new Link();
        $callback($link);
        return $this->add($link, $entry);
    }

    /**
     * @param string $rel
     * @param string $href
     * @param callable|null $callback
     * @param string|null $entry
     * @return LinkCollection
     */
    public function link(string $rel, string $href, ?callable $callback = null, ?string $entry = null): self
    {
        $link = new Link();

        $link->attributes([
            'rel' => $rel,
            'href' => $href
        ]);

        if ($callback !== null) {
            $callback($link);
        }

        return $this->add($link, $entry);
    }

    /**
     * @param string $href
     * @param callable|null $callback
     * @return LinkCollection
     */
    public function favicon(string $href, ?callable $callback = null): self
    {
        return $this->link('icon', $href, $callback);
    }

    /**
     * @param string $href
     * @param callable|null $callback
     * @return LinkCollection
     */
    public function canonical(string $href, ?callable $callback = null): self
    {
        return $this->link('canonical', $href, $callback, 'canonical');
    }
}