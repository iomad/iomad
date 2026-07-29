<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\XML\ds\Transform;

/**
 * Class representing a xenc:Transforms element.
 *
 * @package simplesamlphp/xml-security
 */
final class Transforms extends AbstractXencElement
{
    /**
     * Initialize a xenc:Transforms
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transform[] $transform
     */
    public function __construct(
        protected array $transform,
    ) {
        Assert::maxCount($transform, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($transform, Transform::class, InvalidArgumentException::class);
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\Transform[]
     */
    public function getTransform(): array
    {
        return $this->transform;
    }


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool
    {
        return empty($this->transform);
    }


    /**
     * Convert XML into a Transforms element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Transforms', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Transforms::NS, InvalidDOMElementException::class);

        $transform = Transform::getChildrenOfClass($xml);

        return new static($transform);
    }


    /**
     * Convert this Transforms element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Transforms element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getTransform() as $t) {
            if (!$t->isEmptyElement()) {
                $t->toXML($e);
            }
        }

        return $e;
    }
}
