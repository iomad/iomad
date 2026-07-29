<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\DurationTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class DurationTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $duration
     */
    #[DataProvider('provideDuration')]
    public function testValidDuration(bool $shouldPass, string $duration): void
    {
        try {
            Assert::validDuration($duration);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideDuration(): array
    {
        return [
            [true, 'P2Y6M5DT12H35M30S'],
            [true, 'P1DT2H'],
            [true, 'P1W'],
            [true, 'P20M'],
            [true, 'PT20M'],
            [true, 'P0Y20M0D'],
            [true, 'P0Y'],
            [true, '-P60D'],
            [true, 'PT1M30.5S'],
            [true, 'P15.5Y'],
            [true, 'P15,5Y'],
            [false, 'P-20M'],
            [false, 'P20MT'],
            [false, 'P1YM5D'],
            [false, 'P1D2H'],
            [false, '1Y2M'],
            [false, 'P2M1Y'],
            [false, 'P'],
            [false, 'PT15.S'],
            // Trailing newlines are forbidden
            [false, "P20M\n"],
        ];
    }
}
