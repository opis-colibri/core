<?php
/* ===========================================================================
 * Copyright 2018 The Opis Project
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

namespace Opis\Colibri\SPA;

use Opis\Colibri\AppInfo;

class DataHandler
{
    /** @var AppInfo */
    protected $info;

    /**
     * DataHandler constructor.
     * @param AppInfo $info
     */
    public function __construct(AppInfo $info)
    {
        $this->info = $info;
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        $file = implode(DIRECTORY_SEPARATOR, [$this->info->writableDir(), 'spa', 'data.json']);
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return [
            'apps' => [],
            'modules' => [],
            'rebuild' => [],
        ];
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $dir = $this->info->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'data.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}