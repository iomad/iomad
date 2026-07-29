<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a env:Upgrade element.
 *
 * @package simplesaml/xml-soap
 */
final class Upgrade extends AbstractSoapElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a env:Upgrade
     *
     * @param \SimpleSAML\SOAP\XML\env_200305\SupportedEnvelope[] $supportedEnvelope
     */
    public function __construct(
        protected array $supportedEnvelope,
    ) {
        Assert::maxCount($supportedEnvelope, C::UNBOUNDED_LIMIT);
        Assert::minCount($supportedEnvelope, 1, SchemaViolationException::class);
        Assert::allIsInstanceOf($supportedEnvelope, SupportedEnvelope::class, SchemaViolationException::class);
    }


    /**
     * @return \SimpleSAML\SOAP\XML\env_200305\SupportedEnvelope[]
     */
    public function getSupportedEnvelope(): array
    {
        return $this->supportedEnvelope;
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

        foreach ($this->getSupportedEnvelope() as $supportedEnvelope) {
            $supportedEnvelope->toXML($e);
        }

        return $e;
    }

    /**
     * Convert XML into a Upgrade
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Upgrade', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Upgrade::NS, InvalidDOMElementException::class);

        $supportedEnvelope = SupportedEnvelope::getChildrenOfClass($xml);
        Assert::minCount($supportedEnvelope, 1, SchemaViolationException::class);

        return new static($supportedEnvelope);
    }
}
