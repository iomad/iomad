<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200106;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SOAP\Exception\ProtocolViolationException;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\ExtendableAttributesTrait;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;

use function array_diff;
use function array_filter;
use function array_pop;
use function array_values;

/**
 * Class representing a env:Body element.
 *
 * @package simplesaml/xml-soap
 */
final class Body extends AbstractSoapElement implements SchemaValidatableElementInterface
{
    use ExtendableAttributesTrait;
    use ExtendableElementTrait;
    use SchemaValidatableElementTrait;

    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::ANY;

    /** The namespace-attribute for the xs:anyAttribute element */
    public const XS_ANY_ATTR_NAMESPACE = NS::ANY;

    /**
     * @var \SimpleSAML\SOAP\XML\env_200106\Fault|null
     */
    protected ?Fault $fault;


    /**
     * Initialize a soap:Body
     *
     * @param list<\SimpleSAML\XML\SerializableElementInterface> $children
     * @param list<\SimpleSAML\XML\Attribute> $namespacedAttributes
     */
    public function __construct(array $children = [], array $namespacedAttributes = [])
    {
        /**
         * 4.4: If present, the SOAP Fault element MUST appear as a body entry and MUST NOT
         * appear more than once within a Body element.
         */
        $fault =  array_values(array_filter($children, function ($elt) {
            return $elt instanceof Fault;
        }));
        Assert::maxCount($fault, 1, ProtocolViolationException::class);

        $this->setFault(array_pop($fault));
        $this->setElements(array_diff($children, $fault));
        $this->setAttributesNS($namespacedAttributes);
    }


    /**
     * @param \SimpleSAML\SOAP\XML\env_200106\Fault|null $fault
     */
    public function setFault(?Fault $fault): void
    {
        $this->fault = $fault;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200106\Fault|null
     */
    public function getFault(): ?Fault
    {
        return $this->fault;
    }


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool
    {
        return empty($this->fault) && empty($this->elements) && empty($this->namespacedAttributes);
    }


    /*
     * Convert XML into an Body element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Body', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Body::NS, InvalidDOMElementException::class);

        return new static(
            self::getChildElementsFromXML($xml),
            self::getAttributesNSFromXML($xml),
        );
    }


    /**
     * Convert this Body to XML.
     *
     * @param \DOMElement|null $parent The element we should add this Body to.
     * @return \DOMElement This Body-element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getAttributesNS() as $attr) {
            $attr->toXML($e);
        }

        $this->getFault()?->toXML($e);

        /** @psalm-var \SimpleSAML\XML\SerializableElementInterface $child */
        foreach ($this->getElements() as $child) {
            if (!$child->isEmptyElement()) {
                $child->toXML($e);
            }
        }

        return $e;
    }
}
