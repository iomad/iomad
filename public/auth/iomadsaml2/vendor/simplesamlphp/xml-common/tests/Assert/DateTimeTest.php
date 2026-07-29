<?php

declare(strict_types=1);

namespace SimpleSAML\Test\XML\Assert;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\Assert\Assert;

/**
 * Class \SimpleSAML\Test\XML\Assert\DateTimeTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(Assert::class)]
final class DateTimeTest extends TestCase
{
    /**
     * @param boolean $shouldPass
     * @param string $dateTime
     */
    #[DataProvider('provideDateTime')]
    public function testValidDateTime(bool $shouldPass, string $dateTime): void
    {
        try {
            Assert::validDateTime($dateTime);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<int, array{0: bool, 1: string}>
     */
    public static function provideDateTime(): array
    {
        return [
            [true, '2001-10-26T21:32:52'],
            [true, '2001-10-26T21:32:52+02:00'],
            [true, '2001-10-26T19:32:52Z'],
            [true, '2001-10-26T19:32:52+00:00'],
            [true, '-2001-10-26T21:32:52'],
            [true, '2001-10-26T21:32:52.12679'],
            [false, '2001-10-26'],
            [false, '2001-10-26T21:32'],
            [false, '2001-10-26T25:32:52+02:00'],
            [false, '01-10-26T21:32'],
        ];
    }
}
