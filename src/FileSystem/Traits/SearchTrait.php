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

use Opis\Colibri\FileSystem\{FileInfo, Directory};

trait SearchTrait
{
    /**
     * @inheritdoc
     */
    public function search(
        string $path,
        string $text,
        ?callable $filter = null,
        ?array $options = null,
        ?int $depth = 0,
        ?int $limit = null
    ): iterable {
        $path = $this->dir(trim($path, ' /'));

        if ($path === null) {
            return [];
        }

        $text = trim($text);
        if ($text) {
            $text = str_replace('/', ' ', $text);
        }
        $text = strtolower($text);

        if (!$limit || $limit < 0) {
            $limit = PHP_INT_MAX;
        }

        if ($depth === null || $depth < 0) {
            $depth = PHP_INT_MAX;
        }

        yield from $this->doSearch($path, $text, $filter, $options, $depth, $limit);
    }

    /**
     * @param Directory $dir
     * @param string $text
     * @param callable|null $filter
     * @param array|null $options
     * @param int $depth
     * @param int $max
     * @return FileInfo[]
     */
    protected function doSearch(
        Directory $dir,
        string $text,
        ?callable $filter = null,
        ?array $options = null,
        int $depth = PHP_INT_MAX,
        int &$max = PHP_INT_MAX
    ): iterable {
        /** @var FileInfo[] $to_check */
        $to_check = [];

        while ($item = $dir->next()) {
            if ($depth > 0 && $item->stat()->isDir()) {
                $to_check[] = $item;
            }

            if ($filter && !$filter($item, $options)) {
                continue;
            }

            if ($text !== '' && stripos($item->name(), $text) === false) {
                continue;
            }

            yield $item;

            if (--$max <= 0) {
                return;
            }
        }

        while ($item = array_pop($to_check)) {
            $item = $this->dir($item->path());
            if ($item === null) {
                continue;
            }

            yield from $this->doSearch($item, $text, $filter, $options, $depth - 1, $max);

            if ($max <= 0) {
                return;
            }
        }
    }

    /**
     * @inheritdoc
     */
    abstract public function dir(string $path): ?Directory;
}