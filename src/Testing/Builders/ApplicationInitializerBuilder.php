<?php
/* ============================================================================
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

namespace Opis\Colibri\Testing\Builders;

use Opis\Colibri\Session\Handlers\Memory;
use Opis\Colibri\Session\SessionHandler;
use Opis\Colibri\Testing\CustomApplicationInitializer;
use Opis\Colibri\I18n\Locale;
use Opis\Database\Connection;
use Opis\Colibri\Config\{ConfigDriver, Drivers\Memory as DefaultConfig};
use Psr\Log\{LoggerInterface, NullLogger as DefaultLogger};
use Opis\Colibri\Cache\{CacheDriver, Drivers\Memory as DefaultCache};
use Opis\Colibri\I18n\Translator\{Drivers\Memory as DefaultTranslator, Driver as TranslatorDriver};

class ApplicationInitializerBuilder
{

    protected ?ConfigDriver $config = null;

    protected ?string $timezone = null;

    protected ?string $language = null;

    protected ?CacheDriver $cache = null;

    protected ?SessionHandler $sessionHandler = null;

    protected ?array $sessionConfig = null;

    protected ?TranslatorDriver $translator = null;

    protected ?Connection $databaseConnection = null;

    protected ?LoggerInterface $logger = null;

    /** @var null|callable */
    protected $environmentValidator = null;

    /** @var null|callable */
    protected $setupHandler = null;

    /**
     * @return CustomApplicationInitializer
     */
    public function build(): CustomApplicationInitializer
    {
        return new CustomApplicationInitializer($this);
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
     * @return ApplicationInitializerBuilder
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
     * @return ApplicationInitializerBuilder
     */
    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return null|ConfigDriver
     */
    public function getConfigDriver(): ?ConfigDriver
    {
        if ($this->config === null) {
            $this->config = $this->defaultConfigDriver();
        }
        return $this->config;
    }

    /**
     * @param ConfigDriver|null $driver
     * @return ApplicationInitializerBuilder
     */
    public function setConfigDriver(?ConfigDriver $driver): self
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
     * @return ApplicationInitializerBuilder
     */
    public function setCacheDriver(?CacheDriver $cache): self
    {
        $this->cache = $cache;
        return $this;
    }

    /**
     * @return SessionHandler|null
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
     * @return ApplicationInitializerBuilder
     */
    public function setSessionHandler(?SessionHandler $session = null): self
    {
        $this->sessionHandler = $session;

        return $this;
    }

    /**
     * @param array|null $config
     * @return ApplicationInitializerBuilder
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
     * @return ApplicationInitializerBuilder
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
     * @return ApplicationInitializerBuilder
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
     * @return ApplicationInitializerBuilder
     */
    public function setTranslator(?TranslatorDriver $translator): self
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getEnvironmentValidator(): ?callable
    {
        return $this->environmentValidator;
    }

    /**
     * @param callable|null $validator
     * @return self
     */
    public function setEnvironmentValidator(?callable $validator): self
    {
        $this->environmentValidator = $validator;
        return $this;
    }

    /**
     * @return callable|null
     */
    public function getSetupHandler(): ?callable
    {
        return $this->setupHandler;
    }

    /**
     * @param callable|null $setup
     * @return self
     */
    public function setSetupHandler(?callable $setup): self
    {
        $this->setupHandler = $setup;
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
     * @return ConfigDriver
     */
    protected function defaultConfigDriver(): ConfigDriver
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
            'cookie_httponly' => true,
            'cookie_samesite' => null,
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