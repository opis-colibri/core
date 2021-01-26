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

namespace Opis\Colibri\FileSystem\Traits;

use Opis\Colibri\FileSystem\Context;

trait StreamContextTrait
{
    /** @var resource|null */
    public $context = null;
    protected ?Context $contextOptions = null;

    /**
     * @inheritdoc
     */
    public function stream_set_option(int $option, int $arg1, ?int $arg2 = null): bool
    {
        switch ($option) {
            case STREAM_OPTION_BLOCKING:
                $this->context()->setIsBlocking((bool)$arg1);
                return true;
            case STREAM_OPTION_READ_TIMEOUT:
                $this->context()->setReadTimeout($arg1 * 1000 + intval(($arg2 ?? 0) / 1000));
                return true;
            case STREAM_OPTION_READ_BUFFER:
                $this->context()->setReadMode($arg1);
                if ($arg2) {
                    $this->context()->setReadBufferSize($arg2);
                }
                return true;
            case STREAM_OPTION_WRITE_BUFFER:
                $this->context()->setWriteMode($arg1);
                if ($arg2) {
                    $this->context()->setWriteBufferSize($arg2);
                }
                return true;
        }

        return false;
    }

    /**
     * @return Context
     */
    protected function context(): Context
    {
        if ($this->contextOptions === null) {
            $this->contextOptions = new Context($this->getCurrentProtocol(), $this->context);
        }

        return $this->contextOptions;
    }

    /**
     * @return string
     */
    abstract protected function getCurrentProtocol(): string;
}