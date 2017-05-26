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

namespace Opis\Colibri;

use Opis\Cache\CacheInterface;
use Opis\Config\ConfigInterface;
use SessionHandlerInterface;
use Opis\Database\Connection;
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
     * @param ConfigInterface $driver
     * @return ISettingsContainer
     */
    public function setTranslationsDriver(ConfigInterface $driver): self;

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