<?php

namespace SimpleSAML\Composer;

use SimpleSAML\Assert\Assert;
use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use InvalidArgumentException;

use function in_array;
use function is_string;
use function mb_strtolower;
use function preg_match;
use function sprintf;

class ModuleInstaller extends LibraryInstaller
{
    /** @var string */
    public const MIXED_CASE = 'ssp-mixedcase-module-name';

    /** @var array */
    public const SUPPORTED = ['simplesamlphp-assets', 'simplesamlphp-module'];


    /**
     * {@inheritDoc}
     */
    public function getInstallPath(PackageInterface $package)
    {
        return $this->getPackageBasePath($package);
    }


    /**
     * {@inheritDoc}
     */
    protected function getPackageBasePath(PackageInterface $package)
    {
        if($this->composer->getPackage()->getPrettyName() === 'simplesamlphp/simplesamlphp') {
            $ssp_path = ".";
        } else {
            $ssp_path = $this->composer->getConfig()->get('vendor-dir').'/simplesamlphp/simplesamlphp';
        }

        $matches = [];
        $name = $package->getPrettyName();
        if (!preg_match('@^.*/simplesamlphp-(module|assets)-(.+)$@', $name, $matches)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to install module %s, package name must be on the form "VENDOR/simplesamlphp-(module|assets)-MODULENAME".',
                $name,
            ));
        }

        Assert::count($matches, 3);
        $moduleType = $matches[1];
        $moduleDir = $matches[2];

        Assert::regex(
            $moduleDir,
            '@^[a-z0-9_.-]*$@',
            sprintf(
                'Unable to install module %s, module name must only contain characters from a-z, 0-9, "_", "." and "-".',
                $name
            ),
            InvalidArgumentException::class
        );

        Assert::notStartsWith(
            $moduleDir,
            '.',
            sprintf('Unable to install module %s, module name cannot start with ".".', $name),
            InvalidArgumentException::class
        );

        /**
         * Composer packages are supposed to only contain lowercase letters,
         *  but historically many modules have had names in mixed case.
         * We must provide a way to handle those. Here we allow the module directory
         *  to be overridden with a mixed case name.
         */
        $extraData = $package->getExtra();
        if (isset($extraData[self::MIXED_CASE])) {
            $mixedCaseModuleName = $extraData[self::MIXED_CASE];
            Assert::string(
                $mixedCaseModuleName,
                sprintf('Unable to install module %s, "%s" must be a string.', $name, self::MIXED_CASE),
                InvalidArgumentException::class
            );

            Assert::same(
                mb_strtolower($mixedCaseModuleName, 'utf-8'),
                $moduleDir,
                sprintf(
                    'Unable to install module %s, "%s" must match the package name except that it can contain uppercase letters.',
                    $name,
                    self::MIXED_CASE
                ),
                InvalidArgumentException::class
            );
            $moduleDir = $mixedCaseModuleName;
        }

        switch ($moduleType) {
            case 'assets':
                return $ssp_path . '/public/assets/' . $moduleDir;
            case 'module':
                return $ssp_path . '/modules/' . $moduleDir;
            default:
                throw new InvalidArgumentException(sprintf('Unsupported type: %s', $moduleType));
        }
    }


    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return in_array($packageType, self::SUPPORTED);
    }
}
