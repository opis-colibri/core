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

namespace Opis\Colibri;

use Opis\Cache\CacheDriver;
use Opis\Database\Connection;
use Opis\DataStore\DataStore;
use Opis\I18n\Translator\Driver as TranslatorDriver;
use Opis\Session\SessionHandler;
use Psr\Log\LoggerInterface;

interface ApplicationContainer
{
    /**
     * @return ApplicationInfo
     */
    public function getAppInfo(): ApplicationInfo;

    /**
     * @param DataStore $driver
     * @return ApplicationContainer
     */
    public function setConfigDriver(DataStore $driver): self;

    /**
     * @param CacheDriver $driver
     * @return ApplicationContainer
     */
    public function setCacheDriver(CacheDriver $driver): self;

    /**
     * @param TranslatorDriver $driver
     * @return ApplicationContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): self;

    /**
     * @param string $language
     * @return ApplicationContainer
     */
    public function setDefaultLanguage(string $language): self;

    /**
     * @param Connection $connection
     * @return ApplicationContainer
     */
    public function setDatabaseConnection(Connection $connection): self;

    /**
     * @param SessionHandler $handler
     * @param array $config
     * @return ApplicationContainer
     */
    public function setSessionHandler(SessionHandler $handler, array $config = []): self;

    /**
     * @param LoggerInterface $logger
     * @return ApplicationContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): self;
}