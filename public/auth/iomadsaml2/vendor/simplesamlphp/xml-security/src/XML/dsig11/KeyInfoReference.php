<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing a dsig11:KeyInfoReference element.
 *
 * @package simplesamlphp/xml-security
 */
final class KeyInfoReference extends AbstractDsig11Element implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a KeyInfoReference element.
     *
     * @param string $URI
     * @param string|null $Id
     */
    public function __construct(
        protected string $URI,
        protected ?string $Id = null,
    ) {
        Assert::validURI($URI, SchemaViolationException::class);
        Assert::nullOrValidNCName($Id);
    }


    /**
     * Collect the value of the URI-property
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->URI;
    }


    /**
     * Collect the value of the Id-property
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Convert XML into a KeyInfoReference
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'KeyInfoReference', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, KeyInfoReference::NS, InvalidDOMElementException::class);

        $URI = KeyInfoReference::getAttribute($xml, 'URI');
        $Id = KeyInfoReference::getOptionalAttribute($xml, 'Id', null);

        return new static($URI, $Id);
    }


    /**
     * Convert this KeyInfoReference element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this KeyInfoReference element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getURI());

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        return $e;
    }
}
