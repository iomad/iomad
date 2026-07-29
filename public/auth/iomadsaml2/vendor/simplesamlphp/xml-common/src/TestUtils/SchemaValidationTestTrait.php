<?php

declare(strict_types=1);

namespace SimpleSAML\XML\TestUtils;

use DOMDocument;
use PHPUnit\Framework\Attributes\Depends;

use function class_exists;

/**
 * Test for AbstractElement classes to perform schema validation tests.
 *
 * @package simplesamlphp\xml-common
 */
trait SchemaValidationTestTrait
{
    /** @var string|null */
    protected static ?string $schemaFile = null;

    /** @var class-string */
    protected static string $testedClass;

    /** @var \DOMDocument */
    protected static DOMDocument $xmlRepresentation;


    /**
     * Test schema validation.
     */
    #[Depends('testSerialization')]
    public function testSchemaValidation(): void
    {
        if (!class_exists(self::$testedClass)) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testSchemaValidation(). Please set ' . self::class
                . ':$testedClass to a class-string representing the XML-class being tested',
            );
        } elseif (empty(self::$xmlRepresentation)) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testSchemaValidation(). Please set ' . self::class
                . ':$xmlRepresentation to a DOMDocument representing the XML-class being tested',
            );
        } else {
            // Validate before serialization
            self::$testedClass::schemaValidate(self::$xmlRepresentation, self::$schemaFile);

            // Perform serialization
            $class = self::$testedClass::fromXML(self::$xmlRepresentation->documentElement);
            $serializedClass = $class->toXML();

            // Validate after serialization
            self::$testedClass::schemaValidate($serializedClass->ownerDocument, self::$schemaFile);

            // If we got this far and no exceptions were thrown, consider this test passed!
            $this->addToAssertionCount(1);
        }
    }

    abstract public function testSerialization(): void;
}
