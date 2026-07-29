<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Base64ElementTrait;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a dsig11:DEREncodedKeyValue element.
 *
 * @package simplesaml/xml-security
 */
final class DEREncodedKeyValue extends AbstractDsig11Element implements SchemaValidatableElementInterface
{
    use Base64ElementTrait;
    use SchemaValidatableElementTrait;


    /**
     * Initialize a DEREncodedKeyValue element.
     *
     * @param string $value
     * @param string|null $Id
     */
    public function __construct(
        string $value,
        protected ?string $Id = null,
    ) {
        Assert::validNCName($Id, SchemaViolationException::class);

        $this->setContent($value);
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
     * Convert XML into a DEREncodedKeyValue
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
        Assert::same($xml->namespaceURI, static::getNamespaceURI(), InvalidDOMElementException::class);

        return new static(
            $xml->textContent,
            self::getOptionalAttribute($xml, 'Id', null),
        );
    }


    /**
     * Convert this DEREncodedKeyValue element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this DEREncodedKeyValue element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->textContent = $this->getContent();

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        return $e;
    }
}
