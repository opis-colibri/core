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

use Opis\Session\Handlers\Memory;
use Opis\Session\SessionHandler;
use Opis\Colibri\Testing\ApplicationInitializer;
use Opis\I18n\Locale;
use Opis\Colibri\IApplicationInitializer;
use Opis\Database\Connection;
use Opis\DataStore\{DataStore, Drivers\Memory as DefaultConfig};
use Psr\Log\{LoggerInterface, NullLogger as DefaultLogger};
use Opis\Cache\{CacheDriver, Drivers\Memory as DefaultCache};
use Opis\I18n\Translator\{Drivers\Memory as DefaultTranslator, Driver as TranslatorDriver};

class AppInitBuilder
{
    /** @var null|DataStore */
    protected $config = null;

    /** @var null|string */
    protected $timezone = null;

    /** @var null|string */
    protected $language = null;

    /** @var null|CacheDriver */
    protected $cache = null;

    /** @var null|SessionHandler */
    protected $sessionHandler = null;

    /** @var null|array */
    protected $sessionConfig = null;

    /** @var null|TranslatorDriver */
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
     * @return null|DataStore
     */
    public function getConfigDriver(): ?DataStore
    {
        if ($this->config === null) {
            $this->config = $this->defaultConfigDriver();
        }
        return $this->config;
    }

    /**
     * @param DataStore|null $driver
     * @return AppInitBuilder
     */
    public function setConfigDriver(?DataStore $driver): self
    {
        $this->config = $driver;
        return $this;
    }

    /**
     * @return null|CacheDriver
     */
    public function getCacheDriver(): ?CacheDriver
    {
        if ($this->cache === null) {
            $this->cache = $this->defaultCacheDriver();
        }
        return $this->cache;
    }

    /**
     * @param CacheDriver|null $cache
     * @return AppInitBuilder
     */
    public function setCacheDriver(?CacheDriver $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return SessionHandler
     */
    public function getSessionHandler(): ?SessionHandler
    {
        if ($this->sessionHandler === null) {
            $this->sessionHandler = $this->defaultSessionHandler();
        }

        return $this->sessionHandler;
    }

    /**
     * @return array
     */
    public function getSessionConfig(): array
    {
        if ($this->sessionConfig === null) {
            $this->sessionConfig = $this->defaultSessionConfig();
        }

        return $this->sessionConfig;
    }

    /**
     * @param SessionHandler|null $session
     * @return AppInitBuilder
     */
    public function setSessionHandler(?SessionHandler $session = null): self
    {
        $this->sessionHandler = $session;

        return $this;
    }

    /**
     * @param array|null $config
     * @return AppInitBuilder
     */
    public function setSessionConfig(?array $config = null): self
    {
        $this->sessionConfig = $config;

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
     * @return null|TranslatorDriver
     */
    public function getTranslator(): ?TranslatorDriver
    {
        if ($this->translator === null) {
            $this->translator = $this->defaultTranslator();
        }
        return $this->translator;
    }

    /**
     * @param TranslatorDriver|null $translator
     * @return AppInitBuilder
     */
    public function setTranslator(?TranslatorDriver $translator): self
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
     * @return DataStore
     */
    protected function defaultConfigDriver(): DataStore
    {
        return new DefaultConfig();
    }

    /**
     * @return CacheDriver
     */
    protected function defaultCacheDriver(): CacheDriver
    {
        return new DefaultCache();
    }

    /**
     * @return SessionHandler
     */
    protected function defaultSessionHandler(): SessionHandler
    {
        return new Memory();
    }

    /**
     * @return array
     */
    protected function defaultSessionConfig(): array
    {
        return [
            'flash_slot' => '__flash__',
            'gc_probability' => 0,
            'gc_divisor' => 100,
            'gc_maxlifetime' => 10,
            'cookie_name' => 'PHPSESSID',
            'cookie_lifetime' => 0,
            'cookie_path' => '/',
            'cookie_domain' => '',
            'cookie_secure' => false,
            'cookie_httponly' => false,
        ];
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