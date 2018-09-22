<?php
/* ============================================================================
 * Copyright © 2016-2018 ZINDEX™ CONCEPT SRL
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

namespace Opis\Colibri\Testing;

use Opis\Colibri\Core\IBootstrap;
use Opis\Colibri\Application as BaseApplication;
use Opis\Colibri\Core\AppInfo;
use Opis\Colibri\Rendering\TemplateStream;
use Opis\Session\Session;
use Composer\Package\CompletePackageInterface;
use Composer\Repository\InstalledFilesystemRepository;

class Application extends BaseApplication
{
    /** @var string */
    protected $installedJson;

    /** @var null|callable */
    protected $autoloader = null;

    /** @var IBootstrap */
    protected $bootstrap;

    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * @param IBootstrap $bootstrap
     * @param AppInfo $info
     * @param string $installed
     * @param callable|null $autoloader
     */
    public function __construct(IBootstrap $bootstrap, AppInfo $info, string $installed, ?callable $autoloader = null) {
        $this->bootstrap = $bootstrap;
        $this->info = $info;
        $this->installedJson = $installed;

        if ($autoloader !== null) {
            spl_autoload_register($autoloader);
            $this->autoloader = $autoloader;
        }

        TemplateStream::register();

        static::$instance = $this;
    }

    /**
     * @param bool $hard
     */
    public function destroy(bool $hard = true)
    {
        if ($hard) {
            $this->installedJson = null;
            $this->bootstrap = null;

            if ($this->autoloader) {
                spl_autoload_unregister($this->autoloader);
                $this->autoloader = null;
            }

            if ($this->cache) {
                foreach ($this->cache as $cache) {
                    $cache->clear();
                }
            }
            if ($this->connection) {
                foreach ($this->connection as $connection) {
                    $connection->disconnect();
                }
            }
            if ($this->database) {
                foreach ($this->database as $database) {
                    $database->getConnection()->disconnect();
                }
            }
            if ($this->entityManager) {
                foreach ($this->entityManager as $entityManager) {
                    $entityManager->getConnection()->disconnect();
                }
            }
            if ($this->session) {
                $this->session->destroy();
            }
        }

        $this->console = null;
        $this->info = null;
        $this->packages = null;
        $this->modules = null;
        $this->collector = null;
        $this->containerInstance = null;
        $this->translatorDriver = null;
        $this->translatorInstance = null;
        $this->defaultLanguage = null;
        $this->csrfTokenInstance = null;
        $this->placeholderInstance = null;
        $this->cache = null;
        $this->connection = null;
        $this->database = null;
        $this->entityManager = null;
        $this->session = null;
        $this->httpRequest = null;
        $this->httpRouter = null;
        $this->viewRenderer = null;
        $this->loggers = null;
        $this->eventDispatcher = null;
        $this->validator = null;
        $this->implicit = [];
        $this->collectorList = null;
        $this->alerts = null;
        $this->config = null;

        TemplateStream::unregister();
        static::$instance = null;
    }

    /**
     * @inheritDoc
     */
    public function getSession(): Session
    {
        if ($this->session === null) {
            $this->session = $this->useMemorySession() ? new MemorySession() : parent::getSession();
        }
        return $this->session;
    }

    /**
     * @return string
     */
    protected function installedJsonFile(): string
    {
        return $this->installedJson;
    }

    /**
     * @inheritDoc
     */
    protected function getBootstrapInstance(): IBootstrap
    {
        return $this->bootstrap;
    }

    /**
     * @return bool
     */
    protected function useMemorySession(): bool
    {
        return true;
    }
}