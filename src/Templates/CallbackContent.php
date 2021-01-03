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

namespace Opis\Colibri\Templates;

use Opis\Colibri\Stream\Content;

final class CallbackContent extends Content
{

    private string $extension;

    /**
     * CallbackContent constructor.
     * @param callable $func
     * @param string $extension
     */
    public function __construct(callable $func, string $extension)
    {
        parent::__construct($func);
        $this->extension = $extension;
    }

    /**
     * @inheritDoc
     */
    public function data(?array $options = null): ?string
    {
        $data = ($this->data)($this->extension, $options);

        if (is_scalar($data) || (is_object($data) && method_exists($data, '__toString'))) {
            return (string)$data;
        }

        return null;
    }
}