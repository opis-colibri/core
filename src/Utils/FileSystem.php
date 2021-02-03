<?php
/* ===========================================================================
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

namespace Opis\Colibri\Utils;

final class FileSystem
{
    /**
     * Constructor
     */
    private function __construct()
    {
        // empty constructor
    }

    public static function copy(string $source, string $dest): bool
    {
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        if (is_file($source)) {
            return copy($source, $dest);
        }

        if (!is_dir($dest)) {
            mkdir($dest);
        }

        $dir = dir($source);

        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            self::copy("$source/$entry", "$dest/$entry");
        }

        $dir->close();

        return true;
    }

    public static function remove(string $source): bool
    {
        if (!is_dir($source)) {
            return unlink($source);
        }

        $dir = dir($source);

        while (false !== $entry = $dir->read()) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $item = $source . '/' . $entry;
            self::remove($item);
        }

        return rmdir($source);
    }

    public static function normalize(string $path): string
    {
        $path = trim($path);
        $root = str_starts_with($path, '/') ? '/' : '';
        $result = [];
        foreach (explode('/', trim($path, '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($result);
            } else {
                $result[] = $segment;
            }
        }

        return $root . implode('/', $result);
    }

    public static function relativize(string $base, string $path): string
    {
        $base = self::normalize($base);
        if (!str_starts_with($path, '/')) {
            $path = self::normalize($base . '/' . $path);
        }
        $b = explode('/', $base);
        $p = explode('/', $path);

        $baseLength = count($b);
        $pathLength = count($p);
        $baseIsLonger = $baseLength > $pathLength;
        $min = $baseIsLonger ? $pathLength : $baseLength;
        $result = [];

        $check = true;
        for ($i = 0; $i < $min; $i++) {
            if ($check && $b[$i] === $p[$i]) {
                continue;
            }
            $check = false;
            $result[] = '..';
        }

        $start = $min - count($result);

        if ($baseIsLonger) {
            for ($i = $min; $i < $baseLength; $i++) {
                $result[] = '..';
            }
        }

        if (empty($result)) {
            $result[] = '.';
        }

        for ($i = $start; $i < $pathLength; $i++) {
            $result[] = $p[$i];
        }

        return implode('/', $result);
    }
}