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
        $this->componentInstaller = new ComponentInstaller($io, $composer, $this->appInfo);
        $this->composer->getInstallationManager()->addInstaller($this->componentInstaller);
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
        $this->copyAssets($installMode, $enabled, $installed);
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
            $extra = $package->getExtra();

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
     * @param bool $installMode
     * @param array $enabled
     * @param array $installed
     * @return CompletePackage[]
     */
    public function copyAssets(bool $installMode, array $enabled, array $installed)
    {
        $fs = new Filesystem();
        /** @var CompletePackage[] $packages */
        $packages = $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
        $modulesDir = $this->appInfo->assetsDir() . DIRECTORY_SEPARATOR . 'module';
        $manager = $this->composer->getInstallationManager();

        $doCopy = function (Filesystem $fs, array $component, string $destination, string $packageDir){
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
                        // Construct the final file destination.
                        $destination .= DIRECTORY_SEPARATOR . $withoutPackageDir;
                        // Ensure the directory is available.
                        $fs->ensureDirectoryExists(dirname($destination));
                        // Copy the file to its destination.
                        copy($filesource, $destination);
                    }
                }
            }
        };

        foreach ($packages as $package) {
            $packageType = $package->getType();

            if(!in_array($packageType, [Application::COMPOSER_TYPE, 'component'])){
                continue;
            }

            $extra = $package->getExtra();

            if(!isset($extra['component']) || !is_array($extra['component'])){
                continue;
            }

            $packageName = $package->getName();
            $isModule = $packageType === Application::COMPOSER_TYPE;

            if($isModule && !in_array($packageName, $enabled)){
                $targetDir = $modulesDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, explode('/', $packageName));

                if(file_exists($targetDir) && is_dir($targetDir)){
                    $fs->removeDirectory($targetDir);
                    $targetDir = dirname($targetDir);
                    // If parent dir is empty, delete it
                    if(count(scandir($targetDir)) == 2){
                        $fs->removeDirectory($targetDir);
                    }
                }
                continue;
            }

            $types = ['scripts', 'styles', 'files'];

            $packageDir = $manager->getInstallPath($package);

            foreach ($types as $type){
                if(!isset($extra['component'][$type]) || !is_array($extra['component'][$type])){
                    continue;
                }
                foreach ($extra['component'][$type] as $file){
                    $source = $packageDir . DIRECTORY_SEPARATOR . $file;
                    foreach ($fs->recursiveGlobFiles($source) as $filesource){
                        // Find the final destination without the package directory.
                        $withoutPackageDir = str_replace($packageDir . DIRECTORY_SEPARATOR, '', $filesource);
                        // Construct the final file destination.
                        if($isModule){
                            $destination = $modulesDir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, explode('/', $packageName));
                        } else{
                            $destination = $this->componentInstaller->getComponentPath($package);
                        }
                        $destination .= DIRECTORY_SEPARATOR . $withoutPackageDir;
                        // Ensure the directory is available.
                        $fs->ensureDirectoryExists(dirname($destination));
                        // Copy the file to its destination.
                        copy($filesource, $destination);
                    }
                }
            }

        }
    }

}