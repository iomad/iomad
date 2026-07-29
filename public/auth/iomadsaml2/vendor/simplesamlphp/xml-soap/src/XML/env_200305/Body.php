<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SOAP\Exception\ProtocolViolationException;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\ExtendableAttributesTrait;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;

use function array_pop;

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
    public const XS_ANY_ATTR_NAMESPACE = NS::OTHER;


    /**
     * Initialize a soap:Body
     *
     * @param \SimpleSAML\SOAP\XML\env_200305\Fault|null $fault
     * @param list<\SimpleSAML\XML\SerializableElementInterface> $children
     * @param list<\SimpleSAML\XML\Attribute> $namespacedAttributes
     */
    public function __construct(
        protected ?Fault $fault = null,
        array $children = [],
        array $namespacedAttributes = [],
    ) {
        if ($fault !== null) {
            /**
             * 5.4: When generating a fault, SOAP senders MUST NOT include additional element
             *      information items in the SOAP Body .
             */
            Assert::isEmpty(
                $children,
                "When generating a fault, SOAP senders MUST NOT include additional elements in the SOAP Body.",
                ProtocolViolationException::class,
            );
        }
        Assert::allNotInstanceOf($children, Fault::class, ProtocolViolationException::class);

        $this->setElements($children);
        $this->setAttributesNS($namespacedAttributes);
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Fault|null
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

        /**
         * 5.4: To be recognized as carrying SOAP error information, a SOAP message MUST contain a single SOAP Fault
         *      element information item as the only child element information item of the SOAP Body .
         */
        $fault = Fault::getChildrenOfClass($xml);
        Assert::maxCount($fault, 1, ProtocolViolationException::class);

        return new static(
            array_pop($fault),
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
            $child->toXML($e);
        }

        return $e;
    }
}
