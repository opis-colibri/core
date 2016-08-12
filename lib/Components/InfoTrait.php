<?php
/* ===========================================================================
 * Opis Project
 * http://opis.io
 * ===========================================================================
 * Copyright 2014-2016 Marius Sarca
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

namespace Opis\Colibri\Components;


trait InfoTrait
{
    use ApplicationTrait;

    /**
     * @return string
     */
    private function assetsDir(): string
    {
        return $this->getApp()->getAppInfo()->assetsDir();
    }

    /**
     * @return string
     */
    private function assetsPath(): string
    {
        return $this->getApp()->getAppInfo()->assetsPath();
    }

    /**
     * @return string
     */
    private function publicDir(): string
    {
        return $this->getApp()->getAppInfo()->publicDir();
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function rootDir(): string
    {
        return $this->getApp()->getAppInfo()->rootDir();
    }

    /**
     * @return string
     */
    private function writableDir(): string
    {
        return $this->getApp()->getAppInfo()->writableDir();
    }

    /**
     * @return string
     */
    private function vendorDir(): string
    {
        return $this->getApp()->getAppInfo()->vendorDir();
    }

    /**
     * @return string
     */
    private function composerFile(): string
    {
        return $this->getApp()->getAppInfo()->composerFile();
    }

    /**
     * @return string
     */
    private function bootstrapFile(): string
    {
        return $this->getApp()->getAppInfo()->bootstrapFile();
    }

    /**
     * @return bool
     */
    private function installMode(): bool
    {
        return $this->getApp()->getAppInfo()->installMode();
    }

    /**
     * @return bool
     */
    private function cliMode(): bool
    {
        return $this->getApp()->getAppInfo()->cliMode();
    }
}