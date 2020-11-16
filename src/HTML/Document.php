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

namespace Opis\Colibri\HTML;

use Opis\Colibri\Core\View;

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

    /**
     * @param string $title
     * @return Document
     */
    public function title(string $title): self
    {
        return $this->set('title', $title);
    }

    /**
     * @param string $path
     * @return Document
     */
    public function base(string $path): self
    {
        return $this->set('base', $path);
    }

    /**
     * @param $content
     * @return Document
     */
    public function content($content): self
    {
        return $this->set('content', $content);
    }

    /**
     * @return LinkCollection
     */
    public function links(): LinkCollection
    {
        return $this->get('links');
    }

    /**
     * @return CSSCollection
     */
    public function css(): CSSCollection
    {
        return $this->get('styles');
    }

    /**
     * @return ScriptCollection
     */
    public function script(): ScriptCollection
    {
        return $this->get('scripts');
    }

    /**
     * @return MetaCollection
     */
    public function meta(): MetaCollection
    {
        return $this->get('meta');
    }

    /**
     * @param array $attributes
     * @return Document
     */
    public function htmlAttributes(array $attributes): self
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

    /**
     * @param array $classes
     * @return Document
     */
    public function bodyClasses(array $classes): self
    {
        $classes = array_flip(array_values($classes));
        $this->classes += $classes;
        return $this->bodyAttributes(array(
            'class' => implode(' ', array_keys($this->classes)),
        ));
    }

    /**
     * @param array $attributes
     * @return Document
     */
    public function bodyAttributes(array $attributes): self
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