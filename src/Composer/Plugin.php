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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Opis\Colibri\SPA\SpaHandler;
use Opis\Colibri\SPA\SpaInfo;
use Symfony\Component\Filesystem\Filesystem;


class Plugin implements PluginInterface, EventSubscriberInterface
{
    /** @var  IOInterface */
    protected $io;

    /** @var  Composer */
    protected $composer;

    /** @var  AppInfo */
    protected $appInfo;

    /** @var  bool */
    protected $isProject;

    /**
     * Apply plugin modifications to Composer
     *
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->io = $io;
        $this->composer = $composer;
        $rootDir = realpath($this->composer->getConfig()->get('vendor-dir') . '/../');
        $settings = $this->composer->getPackage()->getExtra()['application'] ?? [];
        $this->appInfo = new AppInfo($rootDir, $settings);
        $this->isProject = $composer->getPackage()->getType() === 'project';
        if ($this->isProject) {
            $this->composer
                ->getInstallationManager()
                ->addInstaller(new ModuleInstaller($this->appInfo, $io, $composer));
        }
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     * * The method name to call (priority defaults to 0)
     * * An array composed of the method name to call and the priority
     * * An array of arrays composed of the method names to call and respective
     *   priorities, or 0 if unset
     *
     * For instance:
     *
     * * array('eventName' => 'methodName')
     * * array('eventName' => array('methodName', $priority))
     * * array('eventName' => array(array('methodName1', $priority), array('methodName2'))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            'pre-autoload-dump' => 'handleDumpAutoload',
        ];
    }

    /**
     * @param Event $event
     */
    public function handleDumpAutoload(
        /** @noinspection PhpUnusedParameterInspection */
        Event $event
    ) {
        $installMode = true;
        $installed = $enabled = [];

        if (!$this->isProject) {
            return;
        }

        if (!$this->appInfo->installMode()) {
            $installMode = false;
            if (null !== $app = Application::getInstance()) {
                $config = $app->getConfig();
            } else {
                $container = new SurrogateContainer($this->appInfo);
                /** @var \Opis\Colibri\IBootstrap $bootstrap */
                /** @noinspection PhpIncludeInspection */
                $bootstrap = require $this->appInfo->bootstrapFile();
                $bootstrap->bootstrap($container);
                $config = $container->getConfigDriver();
            }
            $installed = $config->read('modules.installed', []);
            $enabled = $config->read('modules.enabled', []);
        }

        $this->preparePacks($installMode, $enabled, $installed);

        if (!$installMode) {
            $this->buildSinglePageApps($enabled);
        }
    }

    /**
     * @param bool $installMode
     * @param array $enabled
     * @param array $installed
     * @return CompletePackage[]
     */
    public function preparePacks(bool $installMode, array $enabled, array $installed): array
    {
        /** @var CompletePackage[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();

        foreach ($packages as $package) {
            if ($package->getType() !== Application::COMPOSER_TYPE) {
                continue;
            }

            $module = $package->getName();

            if ($installMode) {
                $package->setAutoload([]);
                continue;
            }

            if (!in_array($module, $installed)) {
                $package->setAutoload([]);
                continue;
            }

            if (in_array($module, $enabled)) {
                continue;
            }

            $classmap = [];
            $extra = $package->getExtra()['module'] ?? [];

            foreach (['collector', 'installer'] as $key) {
                if (!isset($extra[$key]) || !is_array($extra[$key])) {
                    continue;
                }
                $item = $extra[$key];
                if (isset($item['file']) && isset($item['class'])) {
                    $classmap[] = $item['file'];;
                }
            }

            $package->setAutoload(empty($classmap) ? [] : ['classmap' => $classmap]);
        }

        return $packages;
    }

    /**
     * @param array $enabled
     */
    public function buildSinglePageApps(array $enabled)
    {
        $base_dir = $this->appInfo->writableDir() . DIRECTORY_SEPARATOR . 'spa';
        $data_file = $base_dir . DIRECTORY_SEPARATOR . 'data.json';

        if (!file_exists($data_file)) {
            return;
        }

        $data = json_decode(file_get_contents($data_file), true);
        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $fs = new Filesystem();
        $yarn = new YarnPackageManager();

        foreach ($rebuild as $app_name) {
            if (!isset($apps[$app_name])) {
                continue;
            }

            $app = $apps[$app_name];
            $dir = $this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . $app_name;

            if (!in_array($app['owner'], $enabled)) {
                if (is_dir($dir)) {
                    $fs->remove($dir);
                }
                continue;
            }
            /** @var SpaHandler $handler */
            $handler = new $app['handler'](new SpaInfo($app['name'], $app['owner'], $app['dir'], $app['dist'],
                $app['modules']));

            foreach ($app['modules'] as $app_module_name) {
                if (in_array($app_module_name, $enabled)) {
                    $conf_file = $modules[$app_module_name][$app_name] . DIRECTORY_SEPARATOR . 'spa.conf.json';
                    $conf = null;
                    if (file_exists($conf_file)) {
                        $conf = json_decode(file_get_contents($conf_file), true);
                    }
                    $app_module_name = str_replace('/', '.', $app_module_name);
                    $handler->importPackage($app_module_name, $conf);
                }
            }

            $handler->prepare();

            $yarn->install(null, $app['dir']);
            $yarn->update(null, $app['dir']);
            $yarn->run('build', $app['dir']);

            $handler->finalize();

            if (is_dir($dir)) {
                $fs->remove($dir);
            }
            $fs->mirror($app['dist'], $dir);
        }

        $rebuild = [];
        file_put_contents($data_file, json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}