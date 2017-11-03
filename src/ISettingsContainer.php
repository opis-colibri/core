<?php
/* ===========================================================================
 * Copyright 2014-2017 The Opis Project
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

namespace Opis\Colibri;

use Opis\Cache\CacheInterface;
use Opis\Config\ConfigInterface;
use Opis\Database\Connection;
use Opis\Intl\Translator\IDriver as TranslatorDriver;
use SessionHandlerInterface;
use Psr\Log\LoggerInterface;

interface ISettingsContainer
{
    /**
     * @param ConfigInterface $driver
     * @return ISettingsContainer
     */
    public function setConfigDriver(ConfigInterface $driver): self;

    /**
     * @param CacheInterface $driver
     * @return ISettingsContainer
     */
    public function setCacheDriver(CacheInterface $driver): self;

    /**
     * @param TranslatorDriver $driver
     * @return ISettingsContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): self;

    /**
     * @param string $language
     * @return ISettingsContainer
     */
    public function setDefaultLanguage(string $language): self;

    /**
     * @param Connection $connection
     * @return ISettingsContainer
     */
    public function setDatabaseConnection(Connection $connection): self;

    /**
     * @param SessionHandlerInterface $session
     * @return ISettingsContainer
     */
    public function setSessionHandler(SessionHandlerInterface $session): self;

    /**
     * @param LoggerInterface $logger
     * @return ISettingsContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): self;

    /**
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo;
}