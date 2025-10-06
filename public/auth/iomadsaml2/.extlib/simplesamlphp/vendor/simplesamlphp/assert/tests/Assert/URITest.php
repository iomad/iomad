<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Assert;

use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Assert\AssertionFailedException;

/**
 * Class \SimpleSAML\Assert\Assert
 *
 * @package simplesamlphp/saml2
 * @covers \SimpleSAML\Assert\Assert::__callStatic
 * @covers \SimpleSAML\Assert\Assert::validURI
 * @covers \SimpleSAML\Assert\Assert::validURL
 * @covers \SimpleSAML\Assert\Assert::validURN
 */
final class URITest extends TestCase
{
    /**
     * @dataProvider provideURI
     * @param boolean $shouldPass
     * @param string $uri
     */
    public function testValidURI(bool $shouldPass, string $uri): void
    {
        try {
            Assert::validURI($uri);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @dataProvider provideURL
     * @param boolean $shouldPass
     * @param string $url
     */
    public function testValidURL(bool $shouldPass, string $url): void
    {
        try {
            Assert::validURL($url);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @dataProvider provideURN
     * @param boolean $shouldPass
     * @param string $urn
     */
    public function testValidURN(bool $shouldPass, string $urn): void
    {
        try {
            Assert::validURN($urn);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideURI(): array
    {
        return [
            'urn' => [true, 'urn:x-simplesamlphp:phpunit'],
            'same-doc' => [true, '#_53d830ab1be17291a546c95c7f1cdf8d3d23c959e6'],
            'url' => [true, 'https://www.simplesamlphp.org'],
            'bogus' => [false, 'stupid value'],
        ];
    }


    /**
     * @return array
     */
    public function provideURL(): array
    {
        return [
            'url' => [true, 'https://www.simplesamlphp.org'],
            'same-doc' => [false, '#_53d830ab1be17291a546c95c7f1cdf8d3d23c959e6'],
            'urn' => [false, 'urn:x-simplesamlphp:phpunit'],
            'bogus' => [false, 'stupid value'],
        ];
    }


    /**
     * @return array
     */
    public function provideURN(): array
    {
        return [
            'urn' => [true, 'urn:x-simplesamlphp:phpunit'],
            'url' => [false, 'https://www.simplesamlphp.org'],
            'same-doc' => [false, '#_53d830ab1be17291a546c95c7f1cdf8d3d23c959e6'],
            'bogus' => [false, 'stupid value'],
        ];
    }
}
