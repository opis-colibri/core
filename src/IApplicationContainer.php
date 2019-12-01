<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
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
use Opis\Database\Connection;
use Opis\DataStore\IDataStore;
use Opis\Intl\Translator\IDriver as TranslatorDriver;
use Opis\Session\ISessionHandler;
use Psr\Log\LoggerInterface;

interface IApplicationContainer
{
    /**
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo;

    /**
     * @param IDataStore $driver
     * @return IApplicationContainer
     */
    public function setConfigDriver(IDataStore $driver): self;

    /**
     * @param CacheInterface $driver
     * @return IApplicationContainer
     */
    public function setCacheDriver(CacheInterface $driver): self;

    /**
     * @param TranslatorDriver $driver
     * @return IApplicationContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): self;

    /**
     * @param string $language
     * @return IApplicationContainer
     */
    public function setDefaultLanguage(string $language): self;

    /**
     * @param Connection $connection
     * @return IApplicationContainer
     */
    public function setDatabaseConnection(Connection $connection): self;

    /**
     * @param ISessionHandler $handler
     * @param array $config
     * @return IApplicationContainer
     */
    public function setSessionHandler(ISessionHandler $handler, array $config = []): self;

    /**
     * @param LoggerInterface $logger
     * @return IApplicationContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): self;
}