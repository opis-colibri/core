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

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\CompletePackage;
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Opis\Colibri\AppInfo;
use Opis\Colibri\Application;
use Opis\Colibri\Composer\Installers\AssetsInstaller;
use Opis\Colibri\Composer\Installers\ComponentInstaller;
use Opis\Colibri\Composer\Util\Filesystem;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /** @var  IOInterface */
    protected $io;

    /** @var  Composer */
    protected $composer;

    /** @var  AppInfo */
    protected $appInfo;

    /** @var  ComponentInstaller */
    protected $componentInstaller;

    /** @var  AssetsInstaller */
    protected $assetsInstaller;

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
        $this->componentInstaller = new ComponentInstaller($this->appInfo, $io, $composer);
        $this->assetsInstaller = new AssetsInstaller($this->appInfo, $io, $composer);
        $manager = $this->composer->getInstallationManager();
        $manager->addInstaller($this->componentInstaller);
        $manager->addInstaller($this->assetsInstaller);
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
            'pre-autoload-dump' => 'handleDumpAutoload'
        ];
    }

    /**
     * @param Event $event
     */
    public function handleDumpAutoload(Event $event)
    {
        $installMode = true;
        $installed = $enabled = [];

        if (!$this->appInfo->installMode()) {
            $installMode = false;
            $collector = new DefaultCollector($this->appInfo);
            /** @var \Opis\Colibri\BootstrapInterface $bootstrap */
            $bootstrap = require $this->appInfo->bootstrapFile();
            $bootstrap->bootstrap($collector);
            $installed = $collector->getInstalledModules();
            $enabled = $collector->getEnabledModules();
        }

        $this->preparePacks($installMode, $enabled, $installed);
        $this->copyAssets($enabled);
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
    protected function copyAssets(array $enabled)
    {
        $fs = new Filesystem();
        /** @var PackageInterface[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $manager = $this->composer->getInstallationManager();

        // Copy function
        $doCopy = function (Filesystem $fs, array $component, string $packageDir, string $destination) use(&$i){
            $types = ['scripts', 'styles', 'files'];
            foreach ($types as $type){
                if(!isset($component[$type]) || !is_array($component[$type])){
                    continue;
                }
                foreach ($component[$type] as $file){
                    $source = $packageDir . DIRECTORY_SEPARATOR . $file;
                    foreach ($fs->recursiveGlobFiles($source) as $filesource){
                        // Find the final destination without the package directory.
                        $withoutPackageDir = str_replace($packageDir . DIRECTORY_SEPARATOR, '', $filesource);
                        $fileDest = $destination . DIRECTORY_SEPARATOR . $withoutPackageDir;
                        // Ensure the directory is available.
                        $fs->ensureDirectoryExists(dirname($fileDest));
                        // Copy the file to its destination.
                        copy($filesource, $fileDest);
                    }
                }
            }
        };

        // Remove module function
        $removeDir = function(Filesystem $fs, string $dir){
            $fs->removeDirectory($dir);
            $dir = dirname($dir);
            if(count(scandir($dir)) == 2){
                $fs->removeDirectory($dir);
            }
        };

        foreach ($packages as $package) {
            $packageType = $package->getType();

            if(!in_array($packageType, [Application::COMPOSER_TYPE, 'component'])){
                continue;
            }

            $extra = $package->getExtra();
            $packageDir = $manager->getInstallPath($package);

            if($packageType === 'component'){
                if(!isset($extra['component']) || !is_array($extra['component'])){
                    continue;
                }
                $moduleDest = $this->componentInstaller->getAssetsPath($package);
                if(!file_exists($moduleDest)){
                    $doCopy($fs, $extra['component'], $packageDir, $moduleDest);
                }
                continue;
            }

            $moduleDest = $this->assetsInstaller->getAssetsPath($package);

            if(!in_array($package->getName(), $enabled)){
                if(file_exists($moduleDest)){
                    $removeDir($fs, $moduleDest);
                }
                continue;
            }

            $extra = $extra['module'] ?? [];

            if(!isset($extra['assets']) || !is_string($extra['assets'])){
                continue;
            }

            $packageDir .= DIRECTORY_SEPARATOR . $extra['assets'];

            if(!file_exists($moduleDest)){
                $doCopy($fs, ['files' => ['**']], $packageDir, $moduleDest);
            }
        }
    }

}