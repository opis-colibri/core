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

namespace Opis\Colibri\Composer;

use Opis\Cache\CacheInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\ISettingsContainer;
use Opis\Database\Connection;
use Opis\DataStore\IDataStore;
use Opis\Intl\Translator\IDriver as TranslatorDriver;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

class SurrogateContainer implements ISettingsContainer
{
    /** @var  IDataStore */
    protected $config;

    /** @var  AppInfo */
    protected $appInfo;


    /**
     * DefaultCollector constructor.
     * @param AppInfo $appInfo
     */
    public function __construct(AppInfo $appInfo)
    {
        $this->appInfo = $appInfo;
    }

    /**
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->appInfo;
    }

    /**
     * @return IDataStore
     */
    public function getConfigDriver(): IDataStore
    {
        return $this->config;
    }

    /**
     * @param IDataStore $driver
     * @return ISettingsContainer
     */
    public function setConfigDriver(IDataStore $driver): ISettingsContainer
    {
        $this->config = $driver;
        return $this;
    }

    /**
     * @param CacheInterface $driver
     * @return ISettingsContainer
     */
    public function setCacheDriver(CacheInterface $driver): ISettingsContainer
    {
        return $this;
    }

    /**
     * @param TranslatorDriver $driver
     * @return ISettingsContainer
     */
    public function setTranslatorDriver(TranslatorDriver $driver): ISettingsContainer
    {
        return $this;
    }

    /**
     * @param string $language
     * @return ISettingsContainer
     */
    public function setDefaultLanguage(string $language): ISettingsContainer
    {
        return $this;
    }


    /**
     * @param Connection $connection
     * @return ISettingsContainer
     */
    public function setDatabaseConnection(Connection $connection): ISettingsContainer
    {
        return $this;
    }

    /**
     * @param SessionHandlerInterface $session
     * @return ISettingsContainer
     */
    public function setSessionHandler(SessionHandlerInterface $session): ISettingsContainer
    {
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return ISettingsContainer
     */
    public function setDefaultLogger(LoggerInterface $logger): ISettingsContainer
    {
        return $this;
    }

}