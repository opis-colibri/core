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

namespace Opis\Colibri\Stream\Wrapper;

use Opis\Colibri\Stream\{DataStream, Content, Stream, StreamWrapper};

abstract class ContentStreamWrapper implements StreamWrapper
{
    /** @var bool[] */
    private static array $registered = [];

    /** @var Content[] */
    protected static array $cached = [];

    /** @var resource|null */
    public $context;

    protected ?Stream $stream = null;

    /**
     * @inheritDoc
     */
    public function stream_close(): void
    {
        if ($this->stream) {
            $this->stream->close();
        }
    }

    /**
     * @inheritDoc
     */
    public function stream_eof(): bool
    {
        return $this->stream ? $this->stream->isEOF() : true;
    }

    /**
     * @inheritDoc
     */
    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path = null): bool
    {
        $this->stream = $this->stream($path, $mode);
        return $this->stream !== null;
    }

    /**
     * @inheritDoc
     */
    public function stream_read(int $count): ?string
    {
        return $this->stream ? $this->stream->read($count) : null;
    }

    /**
     * @inheritDoc
     */
    public function stream_stat(): ?array
    {
        return $this->stream ? $this->stream->stat() : null;
    }

    /**
     * @inheritDoc
     */
    public function stream_tell(): ?int
    {
        return $this->stream ? $this->stream->tell() : null;
    }

    /**
     * @inheritDoc
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        return $this->stream ? $this->stream->seek($offset, $whence) : false;
    }

    /**
     * @inheritDoc
     */
    public function stream_flush(): bool
    {
        return $this->stream ? $this->stream->flush() : false;
    }

    /**
     * @inheritDoc
     */
    public function stream_lock(int $operation): bool
    {
        return $this->stream ? $this->stream->lock($operation) : false;
    }

    /**
     * @inheritDoc
     */
    public function stream_truncate(int $size): bool
    {
        return $this->stream ? $this->stream->truncate($size) : false;
    }

    /**
     * @inheritDoc
     */
    public function stream_write(string $data): ?int
    {
        return $this->stream ? $this->stream->write($data) : null;
    }

    /**
     * @inheritDoc
     */
    public function stream_cast(int $opt)
    {
        return $this->stream ? $this->stream->resource() : null;
    }

    /**
     * @param string $path
     * @param int $flags
     * @return array|null
     */
    public function url_stat(
        string $path,
        /** @noinspection PhpUnusedParameterInspection */
        int $flags
    ): ?array {
        if (($stream = $this->stream($path, 'rb')) === null) {
            return null;
        }
        return $stream->stat();
    }

    /**
     * @inheritDoc
     */
    public function stream_set_option(int $option, int $arg1, ?int $arg2 = null): bool
    {
        return false;
    }

    /**
     * @param string $path
     * @param string $mode
     * @return null|Stream
     */
    protected function stream(string $path, string $mode): ?Stream
    {
        $key = $this->cacheKey($path);
        if (!array_key_exists($key, static::$cached)) {
            static::$cached[$key] = $this->content($path);
        }

        $content = static::$cached[$key];
        if ($content === null) {
            return null;
        }

        return $this->contentToStream($content, $path, $mode, $this->contextOptions($this->context));
    }

    /**
     * @param string $path
     * @return string
     */
    protected function cacheKey(string $path): string
    {
        return md5($path);
    }

    /**
     * @param Content $content
     * @param string $path
     * @param string $mode
     * @param array|null $options
     * @return Stream
     */
    protected function contentToStream(Content $content, string $path, string $mode, ?array $options = null): ?Stream
    {
        $data = $content->data($options);
        if ($data === null) {
            return null;
        }

        $meta = $this->streamMeta($content, $path, $mode, $options);

        return new DataStream($data, $mode, $content->created(), $content->updated(), $meta);
    }

    /**
     * @param resource|null $context
     * @return array|null
     */
    protected function contextOptions($context): ?array
    {
        if (!$context || !($context = stream_context_get_options($context))) {
            return null;
        }

        return $context[static::protocol()] ?? null;
    }

    /**
     * @param Content $content
     * @param string $path
     * @param string $mode
     * @param array|null $options
     * @return array
     */
    protected function streamMeta(
        Content $content,
        string $path,
        string $mode,
        /** @noinspection PhpUnusedParameterInspection */
        ?array $options = null
    ): array {
        return [
            'wrapper_type' => static::protocol(),
            'mediatype' => $content->type(),
            'mode' => $mode,
            'uri' => $path,
        ];
    }

    /**
     * @param string $path
     * @return null|Content
     */
    abstract protected function content(string $path): ?Content;

    /**
     * @return bool
     */
    final public static function register(): bool
    {
        $protocol = static::protocol();
        if (isset(self::$registered[$protocol])) {
            return true;
        }

        if (!stream_wrapper_register($protocol, static::class, static::protocolFlags())) {
            return false;
        }

        self::$registered[$protocol] = true;

        return true;
    }

    /**
     * @return bool
     */
    final public static function unregister(): bool
    {
        $protocol = static::protocol();

        if (!isset(self::$registered[$protocol]) || !stream_wrapper_unregister($protocol)) {
            return false;
        }

        unset(self::$registered[$protocol]);

        return true;
    }

    /**
     * @return bool
     */
    final public static function isRegistered(): bool
    {
        return isset(self::$registered[static::protocol()]);
    }

    /**
     * @param array $options
     * @param array|null $params
     * @return resource
     */
    public static function createContext(array $options, ?array $params = null)
    {
        return stream_context_create([
            static::protocol() => $options,
        ], $params);
    }

    /**
     * @return int
     */
    protected static function protocolFlags(): int
    {
        return 0;
    }

    /**
     * @return string
     */
    abstract public static function protocol(): string;
}