<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;

/**
 * Trait grouping common functionality for simple elements with just some textContent
 *
 * @package simplesamlphp/xml-common
 */
trait StringElementTrait
{
    /** @var string */
    protected string $content;


    /**
     * Set the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     */
    protected function setContent(string $content): void
    {
        $this->validateContent($content);
        $this->content = $content;
    }


    /**
     * Get the content of the element.
     *
     * @return string
     */
    public function getContent(): string
    {
        return $this->sanitizeContent($this->getRawContent());
    }


    /**
     * Get the raw content of the element.
     *
     * @return string
     */
    public function getRawContent(): string
    {
        return $this->content;
    }


    /**
     * Sanitize the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return string
     */
    protected function sanitizeContent(string $content): string
    {
        /**
         * Perform no sanitation by default.
         * Override this method on the implementing class to perform content sanitation.
         */
        return $content;
    }


    /**
     * Validate the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(/** @scrutinizer ignore-unused */ string $content): void
    {
        /**
         * Perform no validation by default.
         * Override this method on the implementing class to perform content validation.
         */
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

        return new static($xml->textContent);
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
        $e->textContent = $this->getContent();

        return $e;
    }


    /**
     * @return string
     */
    abstract public static function getLocalName(): string;


    /**
     * Create a document structure for this element
     *
     * @param \DOMElement|null $parent The element we should append to.
     * @return \DOMElement
     */
    abstract public function instantiateParentElement(?DOMElement $parent = null): DOMElement;
}
