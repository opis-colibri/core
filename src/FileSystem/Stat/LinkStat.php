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

namespace Opis\Colibri\FileSystem\Stat;

use Opis\Colibri\FileSystem\Stat;

final class LinkStat extends Stat
{
    /**
     * @inheritDoc
     */
    public function __construct(int $mode, int $size = 0, ?int $time = null, array $info = [])
    {
        $info['mode'] = $mode | 0xA000;
        $info['size'] = $size;
        $info['atime'] = $info['ctime'] = $info['mtime'] = $time;
        parent::__construct($info);
    }
}