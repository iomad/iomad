<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\StringElementTrait;

use function preg_split;

/**
 * Trait grouping common functionality for simple elements with QName textContent
 *
 * @package simplesamlphp/xml-common
 */
trait QNameElementTrait
{
    use StringElementTrait;

    /** @var string|null */
    protected ?string $contentNamespaceUri;


    /**
     * Validate the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(string $content): void
    {
        Assert::validQName($content, SchemaViolationException::class);
    }


    /**
     * Set the namespaceUri.
     *
     * @param string|null $namespaceUri
     */
    protected function setContentNamespaceUri(?string $namespaceUri): void
    {
        Assert::nullOrValidURI($namespaceUri, SchemaViolationException::class);
        $this->contentNamespaceUri = $namespaceUri;
    }


    /**
     * Get the namespace URI.
     *
     * @return string|null
     */
    public function getContentNamespaceUri(): ?string
    {
        return $this->contentNamespaceUri;
    }


    /**
     * Splits a QName into an array holding the prefix (or null if no prefix is available) and the localName
     *
     * @param string $qName  The qualified name
     * @return array
     */
    private static function parseQName(string $qName): array
    {
        Assert::validQName($qName);

        @list($prefix, $localName) = preg_split('/:/', $qName, 2);
        if ($localName === null) {
            $prefix = null;
            $localName = $qName;
        }

        Assert::nullOrValidNCName($prefix);
        Assert::validNCName($localName);

        return [$prefix, $localName];
    }


    /**
     * Convert XML into a class instance
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getLocalName(), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        list($prefix, $localName) = self::parseQName($xml->textContent);
        $namespace = $xml->lookupNamespaceUri($prefix);

        return new static($xml->textContent, $namespace);
    }


    /**
     * Convert this element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        list($prefix, $localName) = self::parseQName($this->getContent());
        $namespaceUri = $this->getContentNamespaceUri();
        if ($namespaceUri !== null && $prefix !== null) {
            if ($e->lookupNamespaceUri($prefix) === null && $e->lookupPrefix($namespaceUri) === null) {
                // The namespace is not yet available in the document - insert it
                $e->setAttribute('xmlns:' . $prefix, $namespaceUri);
            }
        }

        $e->textContent = ($prefix === null) ? $localName : ($prefix . ':' . $localName);

        return $e;
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
