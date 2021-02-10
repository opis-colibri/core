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

class Document extends View
{
    protected array $classes = [];

    public function __construct()
    {
        parent::__construct('html.document', [
            'title' => null,
            'content' => null,
            'base' => null,
            'links' => new LinkCollection(),
            'styles' => new CSSCollection(),
            'scripts' => new ScriptCollection(),
            'meta' => new MetaCollection(),
            'htmlAttributes' => null,
            'bodyAttributes' => null,
        ]);
    }

    public function title(string $title): static
    {
        return $this->set('title', $title);
    }

    public function base(string $path): static
    {
        return $this->set('base', $path);
    }

    public function content(mixed $content): static
    {
        return $this->set('content', $content);
    }

    public function links(): LinkCollection
    {
        return $this->get('links');
    }

    public function css(): CSSCollection
    {
        return $this->get('styles');
    }

    public function script(): ScriptCollection
    {
        return $this->get('scripts');
    }

    public function meta(): MetaCollection
    {
        return $this->get('meta');
    }

    public function htmlAttributes(array $attributes): static
    {
        if ($this->has('htmlAttributes')) {
            $attr = $this->get('htmlAttributes');
        } else {
            $attr = new Attributes();
        }

        foreach ($attributes as $name => $value) {
            if (is_numeric($name)) {
                $name = $value;
                $value = null;
            }

            $attr->add($name, $value);
        }

        return $this->set('htmlAttributes', $attr);
    }

    public function bodyClasses(array $classes): static
    {
        $classes = array_flip(array_values($classes));
        $this->classes += $classes;
        return $this->bodyAttributes([
            'class' => implode(' ', array_keys($this->classes)),
        ]);
    }

    public function bodyAttributes(array $attributes): static
    {
        if ($this->has('bodyAttributes')) {
            $attr = $this->get('bodyAttributes');
        } else {
            $attr = new Attributes();
        }

        foreach ($attributes as $name => $value) {
            if (is_numeric($name)) {
                $name = $value;
                $value = null;
            }

            $attr->add($name, $value);
        }

        return $this->set('bodyAttributes', $attr);
    }
}