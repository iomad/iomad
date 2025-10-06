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
 * @covers \SimpleSAML\Assert\Assert::notInArray
 */
final class NotInArrayTest extends TestCase
{
    /**
     * @dataProvider provideNotInArray
     * @param boolean $shouldPass
     * @param mixed $item
     * @param array $arr
     */
    public function testnotInArray(bool $shouldPass, $item, array $arr): void
    {
        try {
            Assert::notInArray($item, $arr);
            $this->assertTrue($shouldPass);
        } catch (AssertionFailedException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array
     */
    public function provideNotInArray(): array
    {
        return [
            [true, 0, [1]],
            [false, 1, [1]],
        ];
    }
}
