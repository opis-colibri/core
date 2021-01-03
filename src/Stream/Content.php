<?php
/* ============================================================================
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

namespace Opis\Colibri\Stream;

class Content
{

    /** @var string|callable */
    protected $data;
    protected ?int $created = null;
    protected ?int $updated = null;
    protected ?string $type = null;
    protected bool $callable = false;

    /**
     * Content constructor.
     * @param string|callable $data
     * @param int|null $created
     * @param int|null $updated
     * @param null|string $type
     */
    public function __construct($data, ?int $created = null, ?int $updated = null, ?string $type = null)
    {
        $this->callable = is_callable($data);
        $this->data = $data;
        $this->created = $created;
        $this->updated = $updated;
        $this->type = $type;
    }

    /**
     * @param array|null $options
     * @return string|null
     */
    public function data(?array $options = null): ?string
    {
        $data = $this->callable ? ($options ? ($this->data)($options) : ($this->data)()) : $this->data;

        if (is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))) {
            return (string)$data;
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function created(): ?int
    {
        return $this->created;
    }

    /**
     * @return int|null
     */
    public function updated(): ?int
    {
        return $this->updated;
    }

    /**
     * @return string|null
     */
    public function type(): ?string
    {
        return $this->type;
    }
}