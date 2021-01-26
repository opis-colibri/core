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

namespace Opis\Colibri\Test\FileSystem;

trait FilesTrait
{
    /**
     * @param string $source
     * @param string $dest
     * @param string $prefix
     * @return string|null
     */
    protected static function copyFiles(string $source, string $dest, string $prefix = 'test_'): ?string
    {
        $source = rtrim($source, '/') . '/';
        $dest = rtrim($dest, '/') . '/' . uniqid($prefix) . '/';

        if (!mkdir($dest, 0777, true)) {
            return null;
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source,
            \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {

            $name = $item->getPathname();
            $name = $dest . substr($name, strlen($source));

            if ($item->isDir()) {
                mkdir($name, 0777, true);
            } else {
                copy($item, $name);
            }
        }

        return $dest;
    }

    /**
     * @param string $dir
     * @param bool $also_dir
     */
    protected static function deleteFiles(string $dir, bool $also_dir = true)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir,
            \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getRealPath());
            } else {
                unlink($item->getRealPath());
            }
        }

        if ($also_dir) {
            @rmdir($dir);
        }
    }
}