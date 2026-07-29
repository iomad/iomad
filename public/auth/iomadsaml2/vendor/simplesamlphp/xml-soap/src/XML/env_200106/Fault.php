<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200106;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a env:Fault element.
 *
 * @package simplesaml/xml-soap
 */
final class Fault extends AbstractSoapElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a env:Fault
     *
     * @param \SimpleSAML\SOAP\XML\env_200106\FaultCode $faultCode
     * @param \SimpleSAML\SOAP\XML\env_200106\FaultString $faultString
     * @param \SimpleSAML\SOAP\XML\env_200106\FaultActor|null $faultActor
     * @param \SimpleSAML\SOAP\XML\env_200106\Detail|null $detail
     */
    public function __construct(
        protected FaultCode $faultCode,
        protected FaultString $faultString,
        protected ?FaultActor $faultActor = null,
        protected ?Detail $detail = null,
    ) {
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200106\FaultCode
     */
    public function getFaultCode(): FaultCode
    {
        return $this->faultCode;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200106\FaultString
     */
    public function getFaultString(): FaultString
    {
        return $this->faultString;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200106\FaultActor|null
     */
    public function getFaultActor(): ?FaultActor
    {
        return $this->faultActor;
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200106\Detail|null
     */
    public function getDetail(): ?Detail
    {
        return $this->detail;
    }


    /**
     * Convert XML into an Fault element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Fault', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Fault::NS, InvalidDOMElementException::class);

        $faultCode = FaultCode::getChildrenOfClass($xml);
        Assert::count($faultCode, 1, 'Must contain exactly one faultcode', MissingElementException::class);

        $faultString = FaultString::getChildrenOfClass($xml);
        Assert::count($faultString, 1, 'Must contain exactly one faultstring', MissingElementException::class);

        $faultActor = FaultActor::getChildrenOfClass($xml);
        Assert::maxCount(
            $faultActor,
            1,
            'Cannot process more than one faultactor element.',
            TooManyElementsException::class,
        );

        $detail = Detail::getChildrenOfClass($xml);
        Assert::maxCount($detail, 1, 'Cannot process more than one detail element.', TooManyElementsException::class);

        return new self(
            array_pop($faultCode),
            array_pop($faultString),
            array_pop($faultActor),
            array_pop($detail),
        );
    }


    /**
     * Convert this Fault to XML.
     *
     * @param \DOMElement|null $parent The element we should add this fault to.
     * @return \DOMElement This Fault-element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getFaultCode()->toXML($e);
        $this->getFaultString()->toXML($e);
        $this->getFaultActor()?->toXML($e);

        if ($this->getDetail() !== null && !$this->getDetail()->isEmptyElement()) {
            $this->getDetail()->toXML($e);
        }

        return $e;
    }
}
