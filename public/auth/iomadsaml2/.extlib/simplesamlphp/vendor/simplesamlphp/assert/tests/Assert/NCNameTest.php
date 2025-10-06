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
 * @covers \SimpleSAML\Assert\Assert::validNCName
 */
final class NCNameTest extends TestCase
{
    /**
     * @dataProvider provideNCName
     * @param boolean $shouldPass
     * @param string $name
     */
    public function testValidNCName(bool $shouldPass, string $name): void
    {
        try {
            Assert::validNCName($name);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideNCName(): array
    {
        return [
            [true, 'Test'],
            [true, '_Test'],
            [false, 'Te*st'],
            [false, '1Test'],
            [false, 'Te:st'],
        ];
    }
}
