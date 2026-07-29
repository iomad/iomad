<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMDocument;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\{IOException, RuntimeException};
use SimpleSAML\XML\Exception\SchemaViolationException;

use function array_unique;
use function defined;
use function file_exists;
use function implode;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function trim;

/**
 * trait class to be used by all the classes that implement the SchemaValidatableElementInterface
 *
 * @package simplesamlphp/xml-common
 */
trait SchemaValidatableElementTrait
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
    public static function schemaValidate(DOMDocument $document, ?string $schemaFile = null): DOMDocument
    {
        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        if ($schemaFile === null) {
            $schemaFile = self::getSchemaFile();
        }

        // Must suppress the warnings here in order to throw them as an error below.
        $result = @$document->schemaValidate($schemaFile);

        if ($result === false) {
            $msgs = [];
            foreach (libxml_get_errors() as $err) {
                $msgs[] = trim($err->message) . ' on line ' . $err->line;
            }

            throw new SchemaViolationException(sprintf(
                "XML schema validation errors:\n - %s",
                implode("\n - ", array_unique($msgs)),
            ));
        }

        libxml_use_internal_errors($internalErrors);
        libxml_clear_errors();

        return $document;
    }


    /**
     * Get the schema file that can validate this element.
     * The path must be relative to the project's base directory.
     *
     * @return string
     */
    public static function getSchemaFile(): string
    {
        if (!defined('static::SCHEMA')) {
            throw new RuntimeException('A SCHEMA-constant was not set on this class.');
        }
        $schemaFile = static::SCHEMA;

        Assert::true(file_exists($schemaFile), sprintf("File not found: %s", $schemaFile), IOException::class);
        return $schemaFile;
    }
}
