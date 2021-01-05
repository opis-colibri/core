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

namespace Opis\Colibri\Cache\Drivers;

class File extends AbstractFile
{
    /**
     * @inheritDoc
     */
    public function __construct(string $path, string $prefix = '', string $extension = 'cache')
    {
        parent::__construct($path, $prefix, $extension);
    }

    /**
     * @inheritDoc
     */
    public function read(string $key): mixed
    {
        $key = $this->cacheFile($key);
        if (!is_file($key)) {
            return false;
        }

        // Cache exists
        $handle = fopen($key, 'r');
        $expire = (int)trim(fgets($handle));

        if ($expire === 0 || time() < $expire) {
            $cache = '';

            while (!feof($handle)) {
                $cache .= fgets($handle);
            }
            fclose($handle);

            return unserialize($cache);
        }

        // Expired
        fclose($handle);
        unlink($key);

        return false;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        $key = $this->cacheFile($key);
        if (!is_file($key)) {
            return false;
        }

        $handle = fopen($key, 'r');
        $expire = (int)trim(fgets($handle));
        fclose($handle);

        return $expire === 0 || time() < $expire;
    }

    /**
     * @inheritDoc
     */
    protected function serializeDate(mixed $data, int $ttl): string
    {
        return "{$ttl}\n" . serialize($data);
    }
}