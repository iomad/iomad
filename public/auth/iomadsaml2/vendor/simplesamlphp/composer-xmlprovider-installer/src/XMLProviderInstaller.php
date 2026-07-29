<?php

declare(strict_types=1);

namespace SimpleSAML\Composer\XMLProvider;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;

use function in_array;
use function file_exists;
use function link;
use function sha1;
use function unlink;

class XMLProviderInstaller extends LibraryInstaller
{
    /** @var array */
    public const SUPPORTED = ['simplesamlphp-xmlprovider'];


    /**
     * @inheritDoc
     */
    public function ensureBinariesPresence(PackageInterface $package)
    {
        $result = parent::ensureBinariesPresence($package);

        $downloadPath = $this->getInstallPath($package);
        $registry = $downloadPath . '/classes/element.registry.php';

        if (file_exists($registry) === true) {
            $classesDir = $this->vendorDir . '/simplesamlphp/composer-xmlprovider-installer/classes/';
            $target = $classesDir . 'element.registry.' . sha1($registry) . '.php';
            if (file_exists($target) === false) {
                link($registry, $target);
            }
        }

        return $result;
    }


    /**
     * @inheritDoc
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        $result = parent::uninstall($repo, $package);

        $downloadPath = $this->getInstallPath($package);
        $registry = $downloadPath . '/classes/element.registry.php';
        if (file_exists($registry) === true) {
            $classesDir = $this->vendorDir . '/simplesamlphp/composer-xmlprovider-installer/classes/';
            $target = $classesDir . 'element.registry.' . sha1($registry) . '.php';
            @unlink($target);
        }

        return $result;
    }


    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, self::SUPPORTED);
    }
}
