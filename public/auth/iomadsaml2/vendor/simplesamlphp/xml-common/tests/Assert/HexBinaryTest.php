<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\Assert\HexBinaryTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class HexBinaryTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $name
     */
    #[DataProvider('provideHexBinary')]
    public function testHexBinary(bool $shouldPass, string $name): void
    {
        try {
            Assert::validHexBinary($name);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function provideHexBinary(): array
    {
        return [
            'empty' => [false, ''],
            'base64' => [false, 'U2ltcGxlU0FNTHBocA=='],
            'valid' => [true, '3f3c6d78206c657673726f693d6e3122302e20226e656f636964676e223d54552d4622383e3f'],
            'invalid' => [false, '3f3r'],
            'bogus' => [false, '&*$(#&^@!(^%$'],
            'length not dividable by 4' => [false, '3f3'],
        ];
    }
}
