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

final class PHPDataStream extends ResourceStream
{
    /**
     * DataStream constructor.
     * This is a readonly stream
     * @param string $data
     * @param string $mode
     * @param string $content_type
     */
    public function __construct(string $data, string $mode = 'rb', string $content_type = 'text/plain')
    {
        parent::__construct('data://' . $content_type . ';base64,' . base64_encode($data), $mode);
    }
}