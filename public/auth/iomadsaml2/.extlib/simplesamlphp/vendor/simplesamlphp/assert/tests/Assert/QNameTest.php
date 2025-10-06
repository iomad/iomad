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
 * @covers \SimpleSAML\Assert\Assert::validQName
 */
final class QNameTest extends TestCase
{
    /**
     * @dataProvider provideQName
     * @param boolean $shouldPass
     * @param string $name
     */
    public function testValidQName(bool $shouldPass, string $name): void
    {
        try {
            Assert::validQName($name);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideQName(): array
    {
        return [
            [true, 'some:Test'],
            [true, 'some:_Test'],
            [true, '_some:_Test'],
            [true, 'Test'],
            [false, '1Test'],
            [false, 'Te*st'],
        ];
    }
}
