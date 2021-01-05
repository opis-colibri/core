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

namespace Opis\Colibri\Http\Responses;

use RuntimeException;
use Opis\Colibri\Stream\ResourceStream;
use Opis\Colibri\Http\{MimeType, Response};

class FileStream extends Response
{
    /**
     * @param string $file
     * @param string|null $contentType
     * @param int $status
     * @param array $headers
     */
    public function __construct(string $file, ?string $contentType = null, int $status = 200, array $headers = [])
    {
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('File %s does not exist', $file));
        }

        $body = null;
        $size = $status !== 204 ? filesize($file) : 0;

        if ($size) {
            $headers['Content-Type'] = $contentType ??  MimeType::get($file);
            $headers['Content-Length'] = $size;
            $body = new ResourceStream($file);
        }

        parent::__construct($status, $headers, $body);
    }
}