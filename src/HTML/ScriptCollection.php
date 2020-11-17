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

class ScriptCollection extends Collection
{
    protected ?Collection $headScripts = null;
    protected ?Collection $bodyScripts = null;

    /**
     * @param string $href
     * @param callable|null $callback
     * @param bool $inBody
     * @return ScriptCollection|Collection
     */
    public function url(string $href, ?callable $callback = null, bool $inBody = false): self
    {
        $script = new Script();

        if ($callback !== null) {
            $callback($script);
        }
        $this->headScripts = $this->bodyScripts = null;
        return $this->add($script->inBody($inBody)->src($href), $href);
    }

    /**
     * @param string $content
     * @param callable|null $callback
     * @param bool $inBody
     * @return ScriptCollection|Collection
     */
    public function inline(string $content, ?callable $callback = null, bool $inBody = false): self
    {
        $script = new Script();
        if ($callback !== null) {
            $callback($script);
        }
        $this->headScripts = $this->bodyScripts = null;
        return $this->add($script->inBody($inBody)->content($content), md5($content));
    }

    /**
     * @return Collection
     */
    public function headScripts(): Collection
    {
        if ($this->headScripts === null) {
            $this->headScripts = $this->getScriptCollection(false);
        }
        return $this->headScripts;
    }

    /**
     * @return Collection
     */
    public function bodyScripts(): Collection
    {
        if ($this->bodyScripts === null) {
            $this->bodyScripts = $this->getScriptCollection(true);
        }
        return $this->bodyScripts;
    }

    /**
     * @param bool $inBody
     * @return Collection
     */
    protected function getScriptCollection(bool $inBody): Collection
    {
        $scripts = array();
        /** @var Script $script */
        foreach ($this->vars['items'] as $script){
            if ($script->isInBody() === $inBody) {
                $scripts[] = $script;
            }
        }
        $collection = new Collection();
        return $collection->set('items', $scripts);
    }
}
