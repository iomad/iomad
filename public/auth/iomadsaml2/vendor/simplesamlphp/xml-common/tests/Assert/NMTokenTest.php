<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\NMTokenTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class NMTokenTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $nmtoken
     */
    #[DataProvider('provideNMToken')]
    public function testValidToken(bool $shouldPass, string $nmtoken): void
    {
        try {
            Assert::validNMToken($nmtoken);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideNMToken(): array
    {
        return [
            [true, 'Snoopy'],
            [true, 'CMS'],
            [true, 'fööbár'],
            [true, '1950-10-04'],
            [true, '0836217462'],
            // Spaces are forbidden
            [false, 'foo bar'],
            // Commas are forbidden
            [false, 'foo,bar'],
            // Trailing newlines are forbidden
            [false, "foobar\n"],
        ];
    }
}
