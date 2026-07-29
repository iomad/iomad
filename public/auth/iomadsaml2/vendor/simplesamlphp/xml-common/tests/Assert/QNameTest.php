<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\QNameTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class QNameTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $name
     */
    #[DataProvider('provideQName')]
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
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideQName(): array
    {
        return [
            [true, 'some:Test'],
            [true, 'some:_Test'],
            [true, '_some:_Test'],
            [true, 'Test'],
            // Cannot start with a colon
            [false, ':test'],
            // Cannot contain multiple colons
            [false, 'test:test:test'],
            // Cannot start with a number
            [false, '1Test'],
            // Cannot contain a wildcard character
            [false, 'Te*st'],
            // Prefixed newlines are forbidden
            [false, "\nsome:Test"],
            // Trailing newlines are forbidden
            [false, "some:Test\n"],
        ];
    }
}
