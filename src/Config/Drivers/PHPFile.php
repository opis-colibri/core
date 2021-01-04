<?php
/* ============================================================================
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

namespace Opis\Colibri\Config\Drivers;

class PHPFile extends BaseFileDriver
{
    /**
     * @inheritDoc
     */
    public function __construct(string $path, string $prefix = '', string $extension = 'php')
    {
        parent::__construct($path, $prefix, $extension);
    }

    /**
     * @inheritDoc
     */
    protected function readData(string $file): string
    {
        return $file;
    }

    /**
     * @inheritDoc
     */
    protected function import(string $data): mixed
    {
        return include($data);
    }

    /**
     * @inheritDoc
     */
    protected function export(mixed $data): string
    {
        $data = var_export($data, true);
        $data = str_replace('stdClass::__set_state', '(object)', $data);
        return "<?php\nreturn {$data};";
    }
}
