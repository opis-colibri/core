<?php
/* ===========================================================================
 * Copyright 2013-2016 The Opis Project
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

namespace Opis\Colibri\Composer\Util;

use Composer\Util\Filesystem as BaseFilesystem;

/**
 * Provides basic file system operations.
 */
class Filesystem extends BaseFilesystem
{
    /**
     * Performs a recursive-enabled glob search with the given pattern.
     *
     * @param string $pattern
     *   The pattern passed to glob(). If the pattern contains "**", then it
     *   a recursive search will be used.
     * @param int $flags
     *   Flags to pass into glob().
     *
     * @return mixed
     *  An array of files that match the recursive pattern given.
     */
    public function recursiveGlob($pattern, $flags = 0)
    {
        // Perform the glob search.
        $files = glob($pattern, $flags);

        // Check if this is to be recursive.
        if (strpos($pattern, '**') !== FALSE) {
            $dirs = glob(dirname($pattern).DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR|GLOB_NOSORT);
            if ($dirs) {
                foreach ($dirs as $dir) {
                    $files = array_merge($files, $this->recursiveGlob($dir.DIRECTORY_SEPARATOR.basename($pattern), $flags));
                }
            }
        }

        return $files;
    }

    /**
     * Performs a recursive glob search for files with the given pattern.
     *
     * @param string $pattern
     *   The pattern passed to glob().
     * @param int $flags
     *   Flags to pass into glob().
     *
     * @return mixed
     *  An array of files that match the recursive pattern given.
     */
    public function recursiveGlobFiles($pattern, $flags = 0)
    {
        $files = $this->recursiveGlob($pattern, $flags);

        return array_filter($files, 'is_file');
    }
}