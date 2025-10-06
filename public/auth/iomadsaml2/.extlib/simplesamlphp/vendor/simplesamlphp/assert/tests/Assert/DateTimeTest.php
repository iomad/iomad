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
 * @covers \SimpleSAML\Assert\Assert::validDateTime
 * @covers \SimpleSAML\Assert\Assert::validDateTimeZulu
 */
final class DateTimeTest extends TestCase
{
    /**
     * @dataProvider provideDateTime
     * @param boolean $shouldPass
     * @param string $timestamp
     */
    public function testValidDateTime(bool $shouldPass, string $timestamp): void
    {
        try {
            Assert::validDateTime($timestamp);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @dataProvider provideDateTimeZulu
     * @param boolean $shouldPass
     * @param string $timestamp
     */
    public function testValidDateTimeZulu(bool $shouldPass, string $timestamp): void
    {
        try {
            Assert::validDateTimeZulu($timestamp);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideDateTime(): array
    {
        return [
            'sub-second offset' => [true, '2016-07-27T19:30:00.123+05:00'],
            'sub-second zulu' => [true, '2016-07-27T19:30:00.123Z'],
            'offset' => [true, '2016-07-27T19:30:00+05:00'],
            'zulu' => [true, '2016-07-27T19:30:00Z'],
            'bogus' => [false, '&*$(#&^@!(^%$'],
            'whitespace' => [false, ' '],
        ];
    }


    /**
     * @return array
     */
    public function provideDateTimeZulu(): array
    {
        return [
            'sub-second zulu' => [true, '2016-07-27T19:30:00.123Z'],
            'zulu' => [true, '2016-07-27T19:30:00Z'],
            'sub-second offset' => [false, '2016-07-27T19:30:00.123+05:00'],
            'offset' => [false, '2016-07-27T19:30:00+05:00'],
            'bogus' => [false, '&*$(#&^@!(^%$'],
            'whitespace' => [false, ' '],
        ];
    }
}
