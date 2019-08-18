<?php
/* ============================================================================
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

namespace Opis\Colibri\Testing\Builders;

use Opis\Colibri\Session\MemoryHandler;
use Opis\Session\ISessionHandler;
use Opis\Colibri\Testing\ApplicationInitializer;
use Opis\Intl\Locale;
use SessionHandlerInterface;
use Opis\Colibri\Core\IApplicationInitializer;
use Opis\Database\Connection;
use Opis\Intl\Translator\IDriver;
use Opis\DataStore\{IDataStore, Drivers\Memory as DefaultConfig};
use Psr\Log\{LoggerInterface, NullLogger as DefaultLogger};
use Opis\Cache\{CacheInterface, Drivers\Memory as DefaultCache};
use Opis\Intl\Translator\{Drivers\Memory as DefaultTranslator, IDriver as TranslatorDriver};

class AppInitBuilder
{
    /** @var null|IDataStore */
    protected $config = null;

    /** @var null|string */
    protected $timezone = null;

    /** @var null|string */
    protected $language = null;

    /** @var null|CacheInterface */
    protected $cache = null;

    /** @var null|SessionHandlerInterface */
    protected $session = null;

    /** @var null|IDriver */
    protected $translator = null;

    /** @var null|Connection */
    protected $databaseConnection = null;

    /** @var null|LoggerInterface */
    protected $logger = null;

    /**
     * @return IApplicationInitializer
     */
    public function build(): IApplicationInitializer
    {
        return new ApplicationInitializer($this);
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        if ($this->timezone === null) {
            $this->timezone = $this->defaultTimeZone();
        }
        return $this->timezone;
    }

    /**
     * @param null|string $timezone
     * @return AppInitBuilder
     */
    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        if ($this->language === null) {
            $this->language = $this->defaultLanguage();
        }
        return $this->language;
    }

    /**
     * @param string|null $language
     * @return AppInitBuilder
     */
    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return null|IDataStore
     */
    public function getConfigDriver(): ?IDataStore
    {
        if ($this->config === null) {
            $this->config = $this->defaultConfigDriver();
        }
        return $this->config;
    }

    /**
     * @param IDataStore|null $driver
     * @return AppInitBuilder
     */
    public function setConfigDriver(?IDataStore $driver): self
    {
        $this->config = $driver;
        return $this;
    }

    /**
     * @return null|CacheInterface
     */
    public function getCacheDriver(): ?CacheInterface
    {
        if ($this->cache === null) {
            $this->cache = $this->defaultCacheDriver();
        }
        return $this->cache;
    }

    /**
     * @param CacheInterface|null $cache
     * @return AppInitBuilder
     */
    public function setCacheDriver(?CacheInterface $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return ISessionHandler
     */
    public function getSessionHandler(): ?ISessionHandler
    {
        if ($this->session === null) {
            $this->session = $this->defaultSessionHandler();
        }
        return $this->session;
    }

    /**
     * @param ISessionHandler|null $session
     * @return AppInitBuilder
     */
    public function setSessionHandler(?ISessionHandler $session = null): self
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return null|Connection
     */
    public function getDatabaseConnection(): ?Connection
    {
        if ($this->databaseConnection === null) {
            $this->databaseConnection = $this->defaultDatabaseConnection();
        }
        return $this->databaseConnection;
    }

    /**
     * @param Connection|null $database
     * @return AppInitBuilder
     */
    public function setDatabaseConnection(?Connection $database): self
    {
        $this->databaseConnection = $database;
        return $this;
    }

    /**
     * @return null|LoggerInterface
     */
    public function getLogger(): ?LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = $this->defaultLogger();
        }
        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     * @return AppInitBuilder
     */
    public function setLogger(?LoggerInterface $logger = null): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return null|IDriver
     */
    public function getTranslator(): ?IDriver
    {
        if ($this->translator === null) {
            $this->translator = $this->defaultTranslator();
        }
        return $this->translator;
    }

    /**
     * @param IDriver|null $translator
     * @return AppInitBuilder
     */
    public function setTranslator(?IDriver $translator): self
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * @return string
     */
    protected function defaultTimeZone(): string
    {
        return date_default_timezone_get() ?: 'UTC';
    }

    /**
     * @return string
     */
    protected function defaultLanguage(): string
    {
        return Locale::SYSTEM_LOCALE;
    }

    /**
     * @return IDataStore
     */
    protected function defaultConfigDriver(): IDataStore
    {
        return new DefaultConfig();
    }

    /**
     * @return CacheInterface
     */
    protected function defaultCacheDriver(): CacheInterface
    {
        return new DefaultCache();
    }

    /**
     * @return ISessionHandler
     */
    protected function defaultSessionHandler(): ISessionHandler
    {
        return new MemoryHandler();
    }

    /**
     * @return TranslatorDriver
     */
    protected function defaultTranslator(): TranslatorDriver
    {
        return new DefaultTranslator([], []);
    }

    /**
     * @return LoggerInterface
     */
    protected function defaultLogger(): LoggerInterface
    {
        return new DefaultLogger();
    }

    /**
     * @return Connection
     */
    protected function defaultDatabaseConnection(): Connection
    {
        return (new Connection('sqlite::memory:'))
            ->persistent(false)
            ->initCommand('PRAGMA foreign_keys = ON');
    }
}