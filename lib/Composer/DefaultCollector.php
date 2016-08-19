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

namespace Opis\Colibri\Composer;


use Opis\Cache\StorageInterface as CacheStorageInterface;
use Opis\Colibri\AppInfo;
use Opis\Colibri\DefaultCollectorInterface;
use Opis\Config\ConfigInterface;
use Opis\Database\Connection;
use Psr\Log\LoggerInterface;
use SessionHandlerInterface;

class DefaultCollector implements DefaultCollectorInterface
{
    /** @var  ConfigInterface */
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
     * @return array
     */
    public function getInstalledModules(): array
    {
        return $this->config->read('modules.installed', []);
    }

    /**
     * @return array
     */
    public function getEnabledModules(): array
    {
        return $this->config->read('modules.enabled', []);
    }

    /**
     * @return AppInfo
     */
    public function getAppInfo(): AppInfo
    {
        return $this->appInfo;
    }


    /**
     * @param ConfigInterface $driver
     * @return DefaultCollectorInterface
     */
    public function setConfigDriver(ConfigInterface $driver): DefaultCollectorInterface
    {
        $this->config = $driver;
        return $this;
    }

    /**
     * @param CacheStorageInterface $storage
     * @return DefaultCollectorInterface
     */
    public function setCacheStorage(CacheStorageInterface $storage): DefaultCollectorInterface
    {
        return $this;
    }

    /**
     * @param ConfigInterface $storage
     * @return DefaultCollectorInterface
     */
    public function setTranslationsStorage(ConfigInterface $storage): DefaultCollectorInterface
    {
        return $this;
    }

    /**
     * @param Connection $connection
     * @return DefaultCollectorInterface
     */
    public function setDatabaseConnection(Connection $connection): DefaultCollectorInterface
    {
        return $this;
    }

    /**
     * @param SessionHandlerInterface $session
     * @return DefaultCollectorInterface
     */
    public function setSessionStorage(SessionHandlerInterface $session): DefaultCollectorInterface
    {
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return DefaultCollectorInterface
     */
    public function setDefaultLogger(LoggerInterface $logger): DefaultCollectorInterface
    {
        return $this;
    }

}