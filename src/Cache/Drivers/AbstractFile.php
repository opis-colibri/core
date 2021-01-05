<?php
/* ============================================================================
 * Copyright 2021 Zindex Software
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

use RuntimeException;
use Opis\Colibri\Cache\CacheDriver;

abstract class AbstractFile implements CacheDriver
{
    use CacheLoadTrait;

    protected string $path;
    protected string $prefix;
    protected string $extension;

    /**
     * @param string $path Path
     * @param string $prefix (optional) Cache key prefix
     * @param string $extension (optional) File extension
     */
    public function __construct(string $path, string $prefix, string $extension)
    {
        $this->path = rtrim($path, '/');
        $this->prefix = trim($prefix, '.');
        $this->extension = trim($extension, '.');

        if ($this->prefix !== '') {
            $this->prefix .= '.';
        }

        if ($this->extension !== '') {
            $this->extension = '.' . $this->extension;
        }

        if (!is_dir($this->path) && !@mkdir($this->path, 0775, true)) {
            throw new RuntimeException(vsprintf("Cache directory ('%s') does not exist.", [$this->path]));
        }

        if (!is_writable($this->path) || !is_readable($this->path)) {
            throw new RuntimeException(vsprintf("Cache directory ('%s') is not writable or readable.", [$this->path]));
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $key, mixed $data, int $ttl = 0): bool
    {
        $ttl = ((int)$ttl <= 0) ? 0 : ((int)$ttl + time());
        $file = $this->cacheFile($key);
        $data = $this->serializeDate($data, $ttl);

        return $this->fileWrite($file, $data);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        $file = $this->cacheFile($key);

        if (is_file($file)) {
            return unlink($file);
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $pattern = $this->path . '/' . $this->prefix . '*' . $this->extension;

        foreach (glob($pattern) as $file) {
            if (!is_dir($file)) {
                if (unlink($file) === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Returns the path to the cache file.
     *
     * @param string $key Cache key
     *
     * @return  string
     */
    protected function cacheFile(string $key): string
    {
        return $this->path . '/' . $this->prefix . $key . $this->extension;
    }

    /**
     * Write on file
     *
     * @param string $file File path
     * @param string $data Content
     *
     * @return  bool
     */
    protected function fileWrite(string $file, string $data): bool
    {
        $fh = fopen($file, 'c');
        flock($fh, LOCK_EX);
        chmod($file, 0774);
        ftruncate($fh, 0);
        fwrite($fh, $data);
        flock($fh, LOCK_UN);
        fclose($fh);
        return true;
    }

    /**
     * @param mixed $data
     * @param int $ttl
     * @return string
     */
    abstract protected function serializeDate(mixed $data, int $ttl): string;
}