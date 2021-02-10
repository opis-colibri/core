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

namespace Opis\Colibri\Http\Responses;

use RuntimeException;
use Opis\Colibri\Http\{
    MimeType, Response
};
use Opis\Colibri\Stream\ResourceStream;

class FileDownload extends Response
{
    public function __construct(string $file, array $options = [], int $status = 200, array $headers = [])
    {
        if (!is_file($file)) {
            throw new RuntimeException(sprintf('File %s does not exist', $file));
        }

        $body = null;
        $size = $status !== 204 ? filesize($file) : 0;

        if ($size) {
            $options += [
                'file_name' => basename($file),
                'disposition' => 'attachment',
                'content_type' => MimeType::get($file),
            ];
            $headers['Content-Type'] = $options['content_type'];
            $headers['Content-Length'] = $size;
            $headers['Content-Disposition'] = sprintf('%s; filename="%s"', $options['disposition'], $options['file_name']);
            $body = new ResourceStream($file);
        }

        parent::__construct($status, $headers, $body);
    }
}