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

use Opis\Colibri\Config\{
    ConfigDriver, Traits\Path
};

class Memory implements ConfigDriver
{
    use Path;

    protected mixed $data;

    /**
     * @param array|object $data
     */
    public function __construct(array|object $data = [])
    {
        if (!is_object($data) && !is_array($data)) {
            $data = [];
        }
        $this->data = $data;
    }


    public function data(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function read(string|array $path, mixed $default = null): mixed
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return $default;
        }

        $data = $this->data;

        foreach ($path as $key) {
            if (is_object($data)) {
                if (!property_exists($data, $key)) {
                    return $default;
                }
                $data = $data->{$key};
            } elseif (is_array($data)) {
                if (!array_key_exists($key, $data)) {
                    return $default;
                }
                $data = $data[$key];
            } else {
                return $default;
            }
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function write(string|array $path, mixed $value): bool
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return false;
        }

        $last = array_pop($path);

        $data = &$this->data;

        foreach ($path as $key) {
            if (is_object($data)) {
                if (!property_exists($data, $key)) {
                    $data->{$key} = [];
                }
                $data = &$data->{$key};
                continue;
            } elseif (is_array($data)) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = [];
                }
                $data = &$data[$key];
                continue;
            }

            return false;
        }

        if (is_object($data)) {
            $data->{$last} = $value;
            return true;
        } elseif (is_array($data)) {
            $data[$last] = $value;
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete(string|array $path): bool
    {
        $path = $this->normalizePath($path);
        if (empty($path)) {
            return false;
        }

        $last = array_pop($path);

        $data = &$this->data;

        foreach ($path as $key) {
            if (is_object($data)) {
                if (!property_exists($data, $key)) {
                    return false;
                }
                $data = &$data->{$key};
                continue;
            } elseif (is_array($data)) {
                if (!array_key_exists($key, $data)) {
                    return false;
                }
                $data = &$data[$key];
                continue;
            }

            return false;
        }

        if (is_object($data)) {
            if (!property_exists($data, $last)) {
                return false;
            }
            unset($data->{$last});
            return true;
        } elseif (is_array($data)) {
            if (!array_key_exists($last, $data)) {
                return false;
            }
            unset($data[$last]);
            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function has(string|array $path): bool
    {
        return $this !== $this->read($path, $this);
    }
}