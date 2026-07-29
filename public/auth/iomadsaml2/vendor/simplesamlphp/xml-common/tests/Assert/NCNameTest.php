<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\NCNameTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class NCNameTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $name
     */
    #[DataProvider('provideNCName')]
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
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideNCName(): array
    {
        return [
            [true, 'Test'],
            [true, '_Test'],
            // Prefixed v4 UUID
            [true, '_5425e58e-e799-4884-92cc-ca64ecede32f'],
            // An empty value is not valid, unless xsi:nil is used
            [false, ''],
            [false, 'Te*st'],
            [false, '1Test'],
            [false, 'Te:st'],
            // Trailing newlines are forbidden
            [false, "Test\n"],
        ];
    }
}
