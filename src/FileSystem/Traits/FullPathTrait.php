<?php
/* ============================================================================
 * Copyright 2019-2021 Zindex Software
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

namespace Opis\Colibri\FileSystem\Traits;

trait FullPathTrait
{
    protected ?string $protocol = null;

    public function protocol(): ?string
    {
        return $this->protocol;
    }

    public function setProtocol(?string $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return string
     */
    public function fullPath(): string
    {
        return $this->protocol === null ? $this->path() : ($this->protocol . '://' . $this->path());
    }

    /**
     * @inheritdoc
     */
    abstract public function path(): string;
}