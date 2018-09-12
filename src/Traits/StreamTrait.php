<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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

namespace Opis\Colibri\Traits;

trait StreamTrait
{
    /** @var bool */
    protected static $isRegistered = false;

    /** @var  string */
    protected $content;

    /** @var  int */
    protected $length;

    /** @var int */
    protected $pointer = 0;

    /**
     * @param string $path
     * @return string Content
     */
    abstract public function getContent(string $path): string;

    /**
     * @param $path
     * @param $mode
     * @param $options
     * @param $opened_path
     * @return bool
     */
    function stream_open(
        /** @noinspection PhpUnusedParameterInspection */
        $path,
        $mode,
        $options,
        &$opened_path
    ) {
        $this->content = $this->getContent(substr($path, strlen(static::getProtocol() . '://')));
        $this->length = strlen($this->content);
        return true;
    }

    /**
     * @param $count
     * @return string
     */
    public function stream_read($count)
    {
        $value = substr($this->content, $this->pointer, $count);
        $this->pointer += $count;
        return $value;
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return $this->pointer >= $this->length;
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        $stat = stat(__FILE__);
        $stat[7] = $stat['size'] = $this->length;
        return $stat;
    }

    /**
     * @param $path
     * @param $flags
     * @return array
     */
    public function url_stat(
        /** @noinspection PhpUnusedParameterInspection */
        $path,
        $flags
    ) {
        $stat = stat(__FILE__);
        $stat[7] = $stat['size'] = $this->length;
        return $stat;
    }

    /**
     * @param $offset
     * @param $whence
     * @return bool
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        $crt = $this->pointer;

        switch ($whence) {
            case SEEK_SET:
                $this->pointer = $offset;
                break;
            case SEEK_CUR:
                $this->pointer += $offset;
                break;
            case SEEK_END:
                $this->pointer = $this->length + $offset;
                break;
        }

        if ($this->pointer < 0 || $this->pointer >= $this->length) {
            $this->pointer = $crt;
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function stream_tell()
    {
        return $this->pointer;
    }

    /**
     * @param string $path
     * @return string
     */
    public static function getPath(string $path): string
    {
        return static::getProtocol() . '://' . $path;
    }

    /**
     * Register the stream
     */
    public static function register()
    {
        if (!static::$isRegistered) {
            static::$isRegistered = stream_wrapper_register(static::getProtocol(), __CLASS__);
        }
    }

    /**
     * Unregister the stream
     */
    public static function unregister()
    {
        if (static::$isRegistered) {
            stream_wrapper_unregister(static::getProtocol());
            static::$isRegistered = false;
        }
    }

    /**
     * @return bool
     */
    public static function isRegistered(): bool
    {
        return static::$isRegistered;
    }

    /**
     * Get stream's protocol
     *
     * @return string
     */
    abstract public static function getProtocol(): string;
}