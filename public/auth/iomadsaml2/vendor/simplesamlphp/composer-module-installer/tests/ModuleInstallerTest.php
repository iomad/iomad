<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Composer;

use Composer\IO\NullIO;
use Composer\Package\PackageInterface;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Config;
use Composer\PartialComposer;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Composer\Util\HttpDownloader;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Composer\ModuleInstaller;

/**
 * @covers \SimpleSAML\Composer\ModuleInstaller
 */
class ModuleInstallerTest extends TestCase
{
    /** @var \SimpleSAML\Composer\ModuleInstaller */
    private ModuleInstaller $moduleInstaller;


    /**
     */
    public function setUp(): void
    {
        $partialComposer = new PartialComposer();
        $partialComposer->setConfig(new Config());
        $partialComposer->setRepositoryManager(
            new RepositoryManager(new NullIO(), new Config(), new HttpDownloader(new NullIO(), new Config()))
        );
        $partialComposer->getRepositoryManager()->setLocalRepository(new InstalledArrayRepository());
        $partialComposer->setPackage(new RootPackage('simplesamlphp/simplesamlphp', '0.0.1', 'v0.0.1'));

        $this->moduleInstaller = new ModuleInstaller(new NullIO(), $partialComposer);
    }


    #[DataProvider('packageProvider')]
    public function testGetInstallPath(bool $shouldPass, string $package): void
    {
        $package = new Package($package, '0.0.1', 'v0.0.1');
        try {
            $this->moduleInstaller->getInstallPath($package);
            $this->assertTrue($shouldPass);
        } catch (InvalidArgumentException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    #[DataProvider('packageTypeProvider')]
    public function testSupports(bool $shouldPass, string $packageType): void
    {
        $this->assertEquals(
            $this->moduleInstaller->supports($packageType),
            $shouldPass
        );
    }


    /**
     * @return array
     */
    public static function packageTypeProvider(): array
    {
        return [
            'simplesamlphp-module' => [true, 'simplesamlphp-module'],
            'simplesamlphp-assets' => [true, 'simplesamlphp-assets'],
            'simplesamlphp-anyother' => [false, 'simplesamlphp-anyother'],
        ];
    }


    /**
     * @return array
     */
    public static function packageProvider(): array
    {
        return [
            'base' => [false, 'simplesamlphp/simplesamlphp-assets'],
            'bogus-nonmodule' => [false, 'simplesamlphp/simplesamlphp-bogus-nonmodule'],
            'consent-assets' => [true, 'simplesamlphp/simplesamlphp-assets-consent'],
            'ldap' => [true, 'simplesamlphp/simplesamlphp-module-ldap'],
            'module' => [false, 'simplesamlphp/simplesamlphp-module'],
        ];
    }
}
