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
 * @covers \SimpleSAML\Assert\Assert::stringPlausibleBase64
 */
final class Base64Test extends TestCase
{
    /**
     * @dataProvider provideBase64
     * @param boolean $shouldPass
     * @param string $name
     */
    public function testStringPlausibleBase64(bool $shouldPass, string $name): void
    {
        try {
            Assert::StringPlausibleBase64($name);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideBase64(): array
    {
        return [
            'valid' => [true, 'U2ltcGxlU0FNTHBocA=='],
            'bogus' => [false, '&*$(#&^@!(^%$'],
            'length not dividable by 4' => [false, 'U2ltcGxlU0FTHBocA=='],
        ];
    }
}
