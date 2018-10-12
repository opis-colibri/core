<?php
/* ============================================================================
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

namespace Opis\Colibri\Rendering;

use Opis\Stream\IContent;

final class CallbackContent implements IContent
{
    /** @var callable */
    private $func;

    /** @var string */
    private $extension;

    /**
     * CallbackContent constructor.
     * @param callable $func
     * @param string $extension
     */
    public function __construct(callable $func, string $extension)
    {
        $this->func = $func;
        $this->extension = $extension;
    }

    /**
     * @inheritDoc
     */
    public function data(?array $options = null): ?string
    {
        $data = ($this->func)($this->extension, $options);

        if (is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))) {
            return (string)$data;
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function created(): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function updated(): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function type(): ?string
    {
        return null;
    }
}