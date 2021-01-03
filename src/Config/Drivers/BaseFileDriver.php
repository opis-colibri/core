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

namespace Opis\Colibri\Config\Drivers;

use RuntimeException;
use Opis\Colibri\Config\{
    ConfigStore, Traits\Path
};

abstract class BaseFileDriver implements ConfigStore
{
    use Path;

    protected string $path;
    protected string $prefix;
    protected string $extension;

    /** @var Memory[] */
    protected array $cache = [];

    /**
     * @param string $path
     * @param string $prefix
     * @param string $extension
     */
    public function __construct(string $path, string $prefix = '', string $extension = 'conf')
    {
        $this->path = rtrim($path, DIRECTORY_SEPARATOR);
        $this->prefix = trim($prefix, '.');
        $this->extension = trim($extension, '.');

        if ($this->prefix !== '') {
            $this->prefix .= '.';
        }

        if ($this->extension !== '') {
            $this->extension = '.' . $this->extension;
        }

        if (!is_dir($this->path) && !@mkdir($this->path, 0775, true)) {
            throw new RuntimeException(vsprintf("Directory ('%s') does not exist.", [$this->path]));
        }

        if (!is_writable($this->path) || !is_readable($this->path)) {
            throw new RuntimeException(vsprintf("Directory ('%s') is not writable or readable.", [$this->path]));
        }
    }

    /**
     * @inheritDoc
     */
    public function read($path, $default = null)
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return $default;
        }

        $key = reset($path);

        if (!isset($this->cache[$key])) {
            $file = $this->filePath($key);

            if (!is_file($file)) {
                return $default;
            }

            $this->cache[$key] = new Memory($this->import($this->readData($file)));
        }

        return $this->cache[$key]->read($path, $default);
    }

    /**
     * @inheritDoc
     */
    public function write($path, $value): bool
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return false;
        }

        $key = reset($path);

        $file = $this->filePath($key);

        if (!isset($this->cache[$key])) {
            if (is_file($file)) {
                $this->cache[$key] = new Memory($this->import($this->readData($file)));
            } else {
                $this->cache[$key] = new Memory();
            }
        }

        $store = $this->cache[$key];

        if (!$store->write($path, $value)) {
            return false;
        }

        return $this->writeData($file, $this->export($store->data()));
    }

    /**
     * @inheritDoc
     */
    public function delete($path): bool
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return false;
        }

        $key = reset($path);

        $file = $this->filePath($key);

        if (!isset($this->cache[$key])) {
            if (!is_file($file)) {
                return false;
            }
            if (count($path) === 1) {
                return (bool)@unlink($file);
            }
            $this->cache[$key] = new Memory($this->import($this->readData($file)));
        }

        $store = $this->cache[$key];

        if (!$store->delete($path)) {
            return false;
        }

        return $this->writeData($file, $this->export($store->data()));
    }

    /**
     * @inheritDoc
     */
    public function has($path): bool
    {
        return $this !== $this->read($path, $this);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function readData(string $file): string
    {
        return file_get_contents($file);
    }

    /**
     * @param string $file
     * @param string $data
     * @return bool
     */
    protected function writeData(string $file, string $data): bool
    {
        $chmod = !is_file($file);
        $fh = fopen($file, 'c');
        if ($fh === false) {
            return false;
        }

        if (!flock($fh, LOCK_EX)) {
            return false;
        }

        if ($chmod) {
            chmod($file, 0774);
        } elseif (!ftruncate($fh, 0)) {
            return false;
        }

        $ok = fwrite($fh, $data) !== false;
        flock($fh, LOCK_UN);
        fclose($fh);

        return $ok;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function filePath(string $key): string
    {
        return $this->path . '/' . $this->prefix . $key . $this->extension;
    }

    /**
     * @param string $data
     * @return mixed
     */
    abstract protected function import(string $data);

    /**
     * @param mixed $data
     * @return string
     */
    abstract protected function export($data): string;
}