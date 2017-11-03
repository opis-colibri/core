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
        if($this->isProject){
            $manager = $this->composer->getInstallationManager();
            $manager->addInstaller(new AssetsInstaller($this->appInfo, $io, $composer));
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
    public function handleDumpAutoload(/** @noinspection PhpUnusedParameterInspection */Event $event)
    {
        $installMode = true;
        $installed = $enabled = [];

        if(!$this->isProject){
            return;
        }

        if (!$this->appInfo->installMode()) {
            $installMode = false;
            $container = new SurrogateContainer($this->appInfo);
            /** @var \Opis\Colibri\IBootstrap $bootstrap */
            /** @noinspection PhpIncludeInspection */
            $bootstrap = require $this->appInfo->bootstrapFile();
            $bootstrap->bootstrap($container);
            $installed = $container->getInstalledModules();
            $enabled = $container->getEnabledModules();
        }

        $this->preparePacks($installMode, $enabled, $installed);

        if(!$installMode){
            $this->buildSinglePageApps();
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
     * Build SPAs
     */
    public function buildSinglePageApps()
    {
        $base_dir = $this->appInfo->writableDir() . '/spa';

        if(!file_exists($base_dir . '/data.json')){
            return;
        }

        $data = json_decode(file_get_contents($base_dir . '/data.json'), true);
        $apps = &$data['apps'];
        $modules = &$data['modules'];
        $rebuild = &$data['rebuild'];

        $rebuild_apps = [];

        foreach ($rebuild as $module){
            if(!isset($modules[$module])){
                continue;
            }
            foreach (array_keys($modules[$module]) as $app){
                if(!in_array($app, $rebuild_apps)){
                    $rebuild_apps[] = $app;
                }
            }
        }

        $rebuild = [];
        file_put_contents($base_dir . '/data.json', json_encode($data));

        $cwd = getcwd();
        $fs = new Filesystem();
        $source = '';

        $assets_dir = $this->appInfo->assetsDir();

        if(!is_dir($assets_dir . '/spa')){
            $fs->mkdir($assets_dir . '/spa');
        }

        foreach ($rebuild_apps as $app) {
            if (!isset($apps[$app])) {
                continue;
            }

            $app = $apps[$app];
            $dir = $assets_dir . '/spa/' . $app['name'];
            if(!is_dir($dir)){
                $fs->mkdir($dir);
            }

            $fs->remove($dir);

            if(!empty($app['entries'])){
                $webpack_config = str_replace('{{entries}}', json_encode($app['entries']), $source);
                file_put_contents($app['dir'] . '/webpack.config.js', $webpack_config);

                chdir($app['dir']);
                passthru('yarn install >> /dev/tty');
                passthru('./node_modules/.bin/webpack >> /dev/tty');
                chdir($cwd);

                $fs->mirror($app['dir'], $dir);
            }

            if(empty($app['modules'])){
                $fs->remove($app['dir']);
            }
        }
    }
}