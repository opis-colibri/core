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

trait StreamMetaTrait
{
    public function stream_metadata(string $path, int $option, mixed $value = null): bool
    {
        switch ($option) {
            case STREAM_META_TOUCH:
                $value = $value ?? [];
                if (!isset($value[0])) {
                    $value[0] = time();
                }
                if (!isset($value[1])) {
                    $value[1] = $value[0];
                }
                return $this->touch($path, $value[0], $value[1]);
            /** @noinspection PhpMissingBreakStatementInspection */
            case STREAM_META_OWNER:
                if (!is_int($value)) {
                    return false;
                }
                $value = $this->ownerName($path, $value);
            // fall
            case STREAM_META_OWNER_NAME:
                if (!is_string($value)) {
                    return false;
                }
                return $this->chown($path, $value);
            /** @noinspection PhpMissingBreakStatementInspection */
            case STREAM_META_GROUP:
                if (!is_int($value)) {
                    return false;
                }
                $value = $this->groupName($path, $value);
            // fall
            case STREAM_META_GROUP_NAME:
                if (!is_string($value)) {
                    return false;
                }
                return $this->chgrp($path, $value);
            case STREAM_META_ACCESS:
                if (!is_int($value)) {
                    return false;
                }
                return $this->chmod($path, $value);
        }

        return false;
    }

    protected function ownerName(/** @noinspection PhpUnusedParameterInspection */
        string $path, int $id): ?string
    {
        if (!function_exists('posix_getpwuid')) {
            return null;
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        return posix_getpwuid($id)['name'] ?? null;
    }

    protected function groupName(/** @noinspection PhpUnusedParameterInspection */
        string $path, int $id): ?string
    {
        if (!function_exists('posix_getgrgid')) {
            return null;
        }

        /** @noinspection PhpComposerExtensionStubsInspection */
        return posix_getgrgid($id)['name'] ?? null;
    }

    abstract protected function touch(string $path, int $time, int $atime): bool;
    abstract protected function chown(string $path, string $owner): bool;
    abstract protected function chgrp(string $path, string $group): bool;
    abstract protected function chmod(string $path, int $mode): bool;
}