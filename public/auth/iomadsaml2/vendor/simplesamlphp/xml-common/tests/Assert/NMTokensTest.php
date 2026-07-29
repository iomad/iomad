<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\NMTokensTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class NMTokensTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $nmtokens
     */
    #[DataProvider('provideNMTokens')]
    public function testValidTokens(bool $shouldPass, string $nmtokens): void
    {
        try {
            Assert::validNMTokens($nmtokens);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideNMTokens(): array
    {
        return [
            [true, 'Snoopy'],
            [true, 'CMS'],
            [true, 'fööbár'],
            [true, '1950-10-04'],
            [true, '0836217462 0836217463'],
            [true, 'foo bar'],
            // Quotes are forbidden
            [false, 'foo "bar" baz'],
            // Commas are forbidden
            [false, 'foo,bar'],
            // Trailing newlines are forbidden
            [false, "foobar\n"],
        ];
    }
}
