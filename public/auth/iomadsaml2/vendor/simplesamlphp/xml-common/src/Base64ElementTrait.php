<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;

use function str_replace;

/**
 * Trait grouping common functionality for simple elements with base64 textContent
 *
 * @package simplesamlphp/xml-common
 */
trait Base64ElementTrait
{
    use StringElementTrait;


    /**
     * Sanitize the content of the element.
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
        Assert::validBase64(
            $this->sanitizeContent($content),
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
