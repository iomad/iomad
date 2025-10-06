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
 * @covers \SimpleSAML\Assert\Assert::validDuration
 */
final class DurationTest extends TestCase
{
    /**
     * @dataProvider provideDuration
     * @param boolean $shouldPass
     * @param string $duration
     */
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
     * @return array
     */
    public function provideDuration(): array
    {
        return [
            [true, 'P2Y6M5DT12H35M30S'],
            [true, 'P1DT2H'],
            [true, 'P20M'],
            [true, 'PT20M'],
            [true, 'P0Y20M0D'],
            [true, 'P0Y'],
            [true, '-P60D'],
            [true, 'PT1M30.5S'],
            [false, 'P-20M'],
            [false, 'P20MT'],
            [false, 'P1YM5D'],
            [false, 'P15.5Y'],
            [false, 'P1D2H'],
            [false, '1Y2M'],
            [false, 'P2M1Y'],
            [false, 'P'],
            [false, 'PT15.S'],
        ];
    }
}
