<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingAttributeException;

/**
 * Trait grouping common functionality for simple localized string elements
 *
 * @package simplesamlphp/xml-common
 */
trait LocalizedStringElementTrait
{
    use StringElementTrait;

    /**
     * The language this string is on.
     *
     * @var string
     */
    protected string $language;


    /**
     * Validate the content of the element.
     *
     * @param string $content  The value to go in the XML textContent
     * @throws \Exception on failure
     * @return void
     */
    protected function validateContent(string $content): void
    {
        Assert::notEmpty($content);
    }


    /**
     * Get the language this string is localized in.
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }


    /**
     * Set the language this string is localized in.
     *
     * @param string $language
     */
    protected function setLanguage(string $language): void
    {
        Assert::notEmpty($language, 'xml:lang cannot be empty.');
        $this->language = $language;
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
        Assert::true(
            $xml->hasAttributeNS(C::NS_XML, 'lang'),
            'Missing xml:lang from ' . static::getLocalName(),
            MissingAttributeException::class,
        );

        return new static($xml->getAttributeNS(C::NS_XML, 'lang'), $xml->textContent);
    }


    /**
     * @param \DOMElement|null $parent
     * @return \DOMElement
     */
    final public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttributeNS(C::NS_XML, 'xml:lang', $this->getLanguage());
        $e->textContent = $this->getContent();

        return $e;
    }
}
