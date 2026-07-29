<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

/**
 * Class representing a env:Subcode element.
 *
 * @package simplesaml/xml-soap
 */
final class Subcode extends AbstractSoapElement
{
    /**
     * Initialize a soap:Subcode
     *
     * @param \SimpleSAML\SOAP\XML\env_200305\Value $value
     * @param \SimpleSAML\SOAP\XML\env_200305\Subcode|null $subcode
     */
    public function __construct(
        protected Value $value,
        protected ?Subcode $subcode = null,
    ) {
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Value
     */
    public function getValue(): Value
    {
        return $this->value;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\Subcode|null
     */
    public function getSubcode(): ?Subcode
    {
        return $this->subcode;
    }


    /**
     * Convert XML into an Subcode element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Subcode', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Subcode::NS, InvalidDOMElementException::class);

        $value = Value::getChildrenOfClass($xml);
        Assert::count($value, 1, 'Must contain exactly one Value', MissingElementException::class);

        $subcode = Subcode::getChildrenOfClass($xml);
        Assert::maxCount($subcode, 1, 'Cannot process more than one Subcode element.', TooManyElementsException::class);

        return new static(
            array_pop($value),
            empty($subcode) ? null : array_pop($subcode),
        );
    }


    /**
     * Convert this Subcode to XML.
     *
     * @param \DOMElement|null $parent The element we should add this subcode to.
     * @return \DOMElement This Subcode-element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getValue()->toXML($e);
        $this->getSubcode()?->toXML($e);

        return $e;
    }
}
