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

class MetaCollection extends Collection
{
    /**
     * @param string $entry
     * @param string $name
     * @param string $content
     * @param callable|null $callback
     * @return Meta
     */
    protected function createContentMeta(string $entry, string $name, string $content, ?callable $callback = null): Meta
    {
        $meta = new Meta();
        $meta->attributes(array(
            $entry => $name,
            'content' => $content,
        ));

        if ($callback !== null) {
            $callback($meta);
        }

        return $meta;
    }

    /**
     * @param string $entry
     * @param callable $callback
     * @return MetaCollection|Collection
     */
    public function custom(string $entry, callable $callback): self
    {
        $meta = new Meta();
        $callback($meta);
        return $this->add($meta, $entry);
    }

    /**
     * @param string $type
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function httpEquiv(string $type, string $value, ?callable $callback = null): self
    {
        $meta = new Meta();

        $meta->attributes(array(
            'http-equiv' => $type,
            'content' => $value,
        ));

        if ($callback !== null) {
            $callback($meta);
        }

        return $this->add($meta, 'http-equiv-' . strtolower($type));
    }

    /**
     * @param string $charset
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function charset(string $charset = 'utf-8', ?callable $callback = null): self
    {
        $meta = new Meta();
        $meta->attribute('charset', $charset);

        if ($callback !== null) {
            $callback($meta);
        }

        return $this->add($meta, 'charset');
    }

    /**
     * @param string $viewport
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function viewport(string $viewport = 'width=device-width, initial-scale=1, shrink-to-fit=no', ?callable $callback = null): self
    {
        $meta = new Meta();
        $meta->attributes(array(
            'name' => 'viewport',
            'content' => $viewport,
        ));

        if ($callback !== null) {
            $callback($meta);
        }

        return $this->add($meta, 'viewport');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function contentType(string $value, ?callable $callback = null): self
    {
        return $this->httpEquiv('content-type', $value, $callback);
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function defaultStyle(string $value, ?callable $callback = null): self
    {
        return $this->httpEquiv('default-style', $value, $callback);
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function refresh(string $value, ?callable $callback = null): self
    {
        return $this->httpEquiv('refresh', $value, $callback);
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function applicationName(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'application-name', $value, $callback), 'application-name');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function author(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'author', $value, $callback), 'author');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function copyright(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'copyright', $value, $callback), 'copyright');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function description(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'description', $value, $callback), 'description');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function generator(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'generator', $value, $callback), 'generator');
    }

    /**
     * @param string|string[] $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function keywords($value, ?callable $callback = null): self
    {
        if(is_array($value)){
            $value = implode(', ', $value);
        }
        return $this->add($this->createContentMeta('name', 'keywords', $value, $callback), 'keywords');
    }

    /**
     * @param string $value
     * @param callable|null $callback
     * @return MetaCollection|Collection
     */
    public function robots(string $value, ?callable $callback = null): self
    {
        return $this->add($this->createContentMeta('name', 'robots', $value, $callback), 'robots');
    }
}
