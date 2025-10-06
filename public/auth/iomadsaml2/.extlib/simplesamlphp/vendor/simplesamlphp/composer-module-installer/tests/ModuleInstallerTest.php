<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Composer;

use Composer\IO\NullIO;
use Composer\Package\BasePackage;
use Composer\Package\PackageInterface;
use Composer\Config;
use Composer\PartialComposer;
use Composer\Repository\InstalledArrayRepository;
use Composer\Repository\InstalledRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Composer\Util\HttpDownloader;
use InvalidArgumentException;
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
        $this->moduleInstaller = new ModuleInstaller(new NullIO(), $partialComposer);
    }


    /**
     * @dataProvider packageProvider
     */
    public function testGetInstallPath(bool $shouldPass, PackageInterface $package): void
    {
        try {
            $this->moduleInstaller->getInstallPath($package);
            $this->assertTrue($shouldPass);
        } catch (InvalidArgumentException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @dataProvider packageTypeProvider
     */
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
    public function packageTypeProvider(): array
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
    public function packageProvider(): array
    {
        return [
            'base' => [
                true,
                $this->getMockForAbstractClass(BasePackage::class, ['simplesamlphp/simplesamlphp-assets']),
            ],
            'bogus-nonmodule' => [
                false,
                $this->getMockForAbstractClass(BasePackage::class, ['simplesamlphp/simplesamlphp-bogus-nonmodule']),
            ],
            'consent-assets' => [
                true,
                $this->getMockForAbstractClass(BasePackage::class, ['simplesamlphp/simplesamlphp-assets-consent']),
            ],
            'ldap' => [
                true,
                $this->getMockForAbstractClass(BasePackage::class, ['simplesamlphp/simplesamlphp-module-ldap']),
            ],
            'module' => [
                false,
                $this->getMockForAbstractClass(BasePackage::class, ['simplesamlphp/simplesamlphp-module']),
            ],
        ];
    }
}
