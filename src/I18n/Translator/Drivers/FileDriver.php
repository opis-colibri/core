<?php
/* ===========================================================================
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

namespace Opis\Colibri\I18n\Translator\Drivers;

use DirectoryIterator;
use Opis\Colibri\I18n\Translator\Driver;

abstract class FileDriver implements Driver
{

    protected int $dirMode = 0777;

    protected ?string $dir = null;

    protected ?array $languageDefaults = null;

    /**
     * AbstractFileStorage constructor.
     * @param string $dir
     * @param int $dir_mode
     * @param array|null $defaults
     */
    public function __construct(string $dir, int $dir_mode = 0775, ?array $defaults = null)
    {
        $this->dir = $dir;
        $this->dirMode = $dir_mode;
        $this->languageDefaults = $defaults;
    }

    /**
     * @inheritDoc
     */
    public function listLanguages(): array
    {
        return $this->dirList($this->getDir());
    }

    /**
     * @inheritDoc
     */
    public function loadLanguage(string $language): ?array
    {
        if ($this->languageDefaults === null) {
            $file = $this->getDir() . DIRECTORY_SEPARATOR . 'defaults.' . $this->getExtension();
            $data = null;
            if (file_exists($file)) {
                $data = $this->importFileContent($file);
            }
            $this->languageDefaults = $data ?? [];
            unset($data);
        }
        $file = $this->getDir() . DIRECTORY_SEPARATOR . $language . '.' . $this->getExtension();
        $data = null;
        if (file_exists($file)) {
            $data = $this->importFileContent($file);
        }
        if ($data) {
            if ($this->languageDefaults) {
                $data = $this->languageDefaults;
            }
        } elseif ($this->languageDefaults) {
            $this->mergeArrays($data, $this->languageDefaults);
        }

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function saveLanguage(string $language, array $settings = null): bool
    {
        $file = $this->getDir() . DIRECTORY_SEPARATOR . $language . '.' . $this->getExtension();
        if ($settings === null) {
            if (!file_exists($file)) {
                return true;
            }

            return unlink($file);
        }

        return (bool)file_put_contents($file, $this->exportFileContent($settings));
    }

    /**
     * @inheritDoc
     */
    public function listNS(string $language): array
    {
        return $this->fileList($this->getDir() . DIRECTORY_SEPARATOR . $language, $this->getExtension());
    }

    /**
     * @inheritDoc
     */
    public function loadNS(string $language, string $ns): ?array
    {
        $file = $this->getDir() . DIRECTORY_SEPARATOR . $language
            . DIRECTORY_SEPARATOR . $ns . '.' . $this->getExtension();

        return is_file($file) ? $this->importFileContent($file) : null;
    }

    /**
     * @inheritDoc
     */
    public function saveNS(string $language, string $ns, array $keys = null): bool
    {
        $dir = $this->getDir() . DIRECTORY_SEPARATOR . $language;
        if (!is_dir($dir)) {
            if ($keys === null) {
                return true;
            }
            if (!mkdir($dir, $this->getDirMode(), true)) {
                return false;
            }
        }

        $file = $dir . DIRECTORY_SEPARATOR . $ns . '.' . $this->getExtension();
        if ($keys === null) {
            if (is_file($keys)) {
                return unlink($file);
            }

            return true;
        }

        return (bool)file_put_contents($file, $this->exportFileContent($keys));
    }

    /**
     * @param string $dir
     * @return string[]
     */
    protected function dirList(string $dir): array
    {
        $dirs = [];
        if (!is_dir($dir)) {
            return $dirs;
        }
        foreach (new DirectoryIterator($dir) as $f) {
            if ($f->isDir() && !$f->isDot()) {
                $dirs[] = $f->getFilename();
            }
        }

        return $dirs;
    }

    /**
     * @param array $to
     * @param array $add
     */
    protected function mergeArrays(array &$to, array $add): void
    {
        foreach ($add as $key => $value) {
            if (!isset($to[$key])) {
                $to[$key] = $value;
                continue;
            }
            if (is_array($value)) {
                if (is_array($to[$key])) {
                    $this->mergeArrays($to[$key], $value);
                } else {
                    $to[$key] = $value;
                }
            }
        }
    }


    /**
     * @param string $dir
     * @param string $ext
     * @return string[]
     */
    protected function fileList(string $dir, string $ext): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return $files;
        }
        foreach (new DirectoryIterator($dir) as $f) {
            if (!$f->isFile() || $f->getExtension() !== $ext) {
                continue;
            }
            $files[] = $f->getBasename('.' . $ext);
        }

        return $files;
    }

    /**
     * @return int
     */
    protected function getDirMode(): int
    {
        return $this->dirMode;
    }

    /**
     * @return string
     */
    protected function getDir(): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $this->getExtension();
    }

    /**
     * @return string
     */
    abstract protected function getExtension(): string;

    /**
     * @param string $file
     * @return array|null
     */
    abstract protected function importFileContent(string $file): ?array;

    /**
     * @param array $data
     * @return string
     */
    abstract protected function exportFileContent(array $data): string;

}