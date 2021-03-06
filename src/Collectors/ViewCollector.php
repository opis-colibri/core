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

namespace Opis\Colibri\Collectors;

use Opis\Colibri\Render\{PHPEngine, Renderer, ViewHandlerSettings};
use function Opis\Colibri\collect;

/**
 * @method Renderer data()
 */
class ViewCollector extends BaseCollector
{
    public function __construct()
    {
        $renderer = new Renderer(new PHPEngine());
        $resolver = $renderer->getEngineResolver();

        foreach (collect(RenderEngineCollector::class)->getEntries() as [$factory, $priority]) {
            $resolver->register($factory, $priority);
        }
        $resolver->sort();

        parent::__construct($renderer);
    }

    /**
     * Defines a new view route
     *
     * @param   string $pattern View's pattern
     * @param   callable $resolver A callback that will resolve a view route into a path
     *
     * @return  ViewHandlerSettings
     */
    public function handle(string $pattern, callable $resolver): ViewHandlerSettings
    {
        return $this->data()->handle($pattern, $resolver, $this->crtPriority);
    }
}
