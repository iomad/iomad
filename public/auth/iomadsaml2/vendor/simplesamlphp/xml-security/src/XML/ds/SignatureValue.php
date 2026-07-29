<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Base64ElementTrait;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing a ds:SignatureValue element.
 *
 * @package simplesaml/xml-security
 */
final class SignatureValue extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use Base64ElementTrait;
    use SchemaValidatableElementTrait;


    /**
     * @param string $content
     * @param string|null $Id
     */
    public function __construct(
        string $content,
        protected ?string $Id = null,
    ) {
        Assert::nullOrValidNCName($Id);

        $this->setContent($content);
    }


    /**
     * Get the Id used for this signature value.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Convert XML into a SignatureValue element
     *
     * @param \DOMElement $xml
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'SignatureValue', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, SignatureValue::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);

        return new static($xml->textContent, $Id);
    }


    /**
     * Convert this SignatureValue element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this SignatureValue element to.
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
