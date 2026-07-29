<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;

/**
 * Trait grouping common functionality for simple elements with hexbinary textContent
 *
 * @package simplesamlphp/xml-common
 */
trait HexBinaryElementTrait
{
    use StringElementTrait;


    /**
     * Sanitize the content of the element.
     *
     * Note:  There are no processing rules for xs:hexBinary regarding whitespace. General consensus is to strip them
     *
     * @param string $content  The unsanitized textContent
     * @throws \Exception on failure
     * @return string
     */
    protected function sanitizeContent(string $content): string
    {
        return str_replace(["\f", "\r", "\n", "\t", "\v", ' '], '', $content);
    }


    /**
     * Validate the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(string $content): void
    {
        // Note: content must already be sanitized before validating
        Assert::regex(
            $this->sanitizeContent($content),
            '/([0-9A-F]{2})*/i',
            SchemaViolationException::class,
        );
    }


    /** @return string */
    abstract public static function getLocalName(): string;


    /**
     * Create a document structure for this element
     *
     * @param \DOMElement|null $parent The element we should append to.
     * @return \DOMElement
     */
    abstract public function instantiateParentElement(?DOMElement $parent = null): DOMElement;
}
