<?php

declare(strict_types=1);

namespace SimpleSAML\XML\TestUtils;

use function class_exists;

/**
 * Test for arrayizable XML classes to perform default serialization tests.
 *
 * @package simplesamlphp\xml-common
 */
trait ArrayizableElementTestTrait
{
    /** @var class-string */
    protected static string $testedClass;

    /** @var array */
    protected static array $arrayRepresentation;


    /**
     * Test arrayization / de-arrayization
     */
    public function testArrayization(): void
    {
        if (!class_exists(self::$testedClass)) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testArrayization(). Please set ' . self::class
                . ':$element to a class-string representing the XML-class being tested',
            );
        } elseif (self::$arrayRepresentation === null) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testArrayization(). Please set ' . self::class
                . ':$arrayRepresentation to an array representing the XML-class being tested',
            );
        } else {
            $this->assertEquals(
                self::$arrayRepresentation,
                self::$testedClass::fromArray(self::$arrayRepresentation)->toArray(),
            );
        }
    }
}
