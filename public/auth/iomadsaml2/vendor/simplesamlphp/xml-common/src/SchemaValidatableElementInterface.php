<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMDocument;

/**
 * interface class to be implemented by all the classes that can be validated against a schema
 *
 * @package simplesamlphp/xml-common
 */
interface SchemaValidatableElementInterface extends ElementInterface
{
    /**
     * Validate the given DOMDocument against the schema set for this element
     *
     * @param \DOMDocument $document
     * @param string|null $schemaFile
     * @return \DOMDocument
     *
     * @throws \SimpleSAML\XML\Exception\IOException
     * @throws \SimpleSAML\XML\Exception\SchemaViolationException
     */
    public static function schemaValidate(DOMDocument $document, ?string $schemaFile = null): DOMDocument;
}
