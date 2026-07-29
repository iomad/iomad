<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\SOAP\Constants as C;
use SimpleSAML\SOAP\Exception\ProtocolViolationException;
use SimpleSAML\SOAP\XML\env_200305\FaultEnum;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

/**
 * Class representing a env:Code element.
 *
 * @package simplesaml/xml-soap
 */
final class Code extends AbstractSoapElement
{
    /**
     * Initialize a soap:Code
     *
     * @param \SimpleSAML\SOAP\XML\env_200305\Value $value
     * @param \SimpleSAML\SOAP\XML\env_200305\Subcode|null $subcode
     */
    public function __construct(
        protected Value $value,
        protected ?Subcode $subcode = null,
    ) {
        @list($prefix, $localName) = preg_split('/:/', $value->getContent(), 2);
        /** @var string|null $localName */
        if ($localName === null) {
            // We don't have a prefixed value here
            $localName = $prefix;
        }

        Assert::oneOf(
            FaultEnum::from($localName),
            FaultEnum::cases(),
            'Invalid top-level Value',
            ProtocolViolationException::class,
        );
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
     * Convert XML into an Code element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Code', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Code::NS, InvalidDOMElementException::class);

        $value = Value::getChildrenOfClass($xml);
        Assert::count($value, 1, 'Must contain exactly one Value', MissingElementException::class);

        // Assert that the namespace of the value matches the SOAP-ENV namespace
        @list($prefix, $localName) = preg_split('/:/', $value[0]->getContent(), 2);
        $namespace = $xml->lookupNamespaceUri($prefix);
        Assert::same($namespace, C::NS_SOAP_ENV_12);

        $subcode = Subcode::getChildrenOfClass($xml);
        Assert::maxCount($subcode, 1, 'Cannot process more than one Subcode element.', TooManyElementsException::class);

        return new static(
            array_pop($value),
            empty($subcode) ? null : array_pop($subcode),
        );
    }


    /**
     * Convert this Code to XML.
     *
     * @param \DOMElement|null $parent The element we should add this code to.
     * @return \DOMElement This Code-element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->value->toXML($e);
        $this->subcode?->toXML($e);

        return $e;
    }
}
