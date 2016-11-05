<?php
/*
 * This file is part of Component Installer.
 *
 * (c) Rob Loach (http://robloach.net)
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Opis\Colibri\Composer;

use Composer\Composer;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Opis\Colibri\AppInfo;

/**
 * Component Installer for Composer.
 */
class ComponentInstaller extends LibraryInstaller
{
    /* @var AppInfo */
    protected $appInfo;

    /**
     * Installer constructor.
     * 
     * @param IOInterface $io
     * @param Composer $composer
     * @param AppInfo $appInfo
     */
    public function __construct(IOInterface $io, Composer $composer, AppInfo $appInfo)
    {
        $this->appInfo = $appInfo;
        parent::__construct($io, $composer);
    }

    /**
     * {@inheritDoc}
     *
     * Components are supported by all packages. This checks wheteher or not the
     * entire package is a "component", as well as injects the script to act
     * on components embedded in packages that are not just "component" types.
     */
    public function supports($packageType)
    {
        return $packageType === 'component';
    }

    /**
     * Gets the destination Component directory.
     *
     * @param PackageInterface $package
     * @return string
     *   The path to where the final Component should be installed.
     */
    public function getComponentPath(PackageInterface $package)
    {
        // Parse the pretty name for the vendor and package name.
        $name = $prettyName = $package->getPrettyName();

        if (strpos($prettyName, '/') !== false) {
            list($vendor, $name) = explode('/', $prettyName);
            unset($vendor);
        }

        // First look for an override in root package's extra, then try the package's extra
        $rootPackage = $this->composer->getPackage();
        $rootExtras = $rootPackage ? $rootPackage->getExtra() : array();
        $customComponents = isset($rootExtras['component']) ? $rootExtras['component'] : array();

        if (isset($customComponents[$prettyName]) && is_array($customComponents[$prettyName])) {
            $component = $customComponents[$prettyName];
        }
        else {
            $extra = $package->getExtra();
            $component = isset($extra['component']) ? $extra['component'] : array();
        }

        // Allow the component to define its own name.
        if (isset($component['name'])) {
            $name = $component['name'];
        }
        // Find where the package should be located.
        return $this->getComponentDir() . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Retrieves the Installer's provided component directory.
     */
    public function getComponentDir()
    {
        return $this->appInfo->assetsDir();
    }

    /**
     * Initialize the Component directory, as well as the vendor directory.
     */
    protected function initializeVendorDir()
    {
        $this->filesystem->ensureDirectoryExists($this->getComponentDir());
        parent::initializeVendorDir();
    }

    /**
     * Remove both the installed code and files from the Component directory.
     *
     * @param PackageInterface $package
     */
    public function removeCode(PackageInterface $package)
    {
        $this->removeComponent($package);
        parent::removeCode($package);
    }

    /**
     * Remove a Component's files from the Component directory.
     *
     * @param PackageInterface $package
     * @return bool
     */
    public function removeComponent(PackageInterface $package)
    {
        $path = $this->getComponentPath($package);
        return $this->filesystem->remove($path);
    }

    /**
     * Before installing the Component, be sure its destination is clear first.
     *
     * @param PackageInterface $package
     */
    public function installCode(PackageInterface $package)
    {
        $this->removeComponent($package);
        parent::installCode($package);
    }
    
}