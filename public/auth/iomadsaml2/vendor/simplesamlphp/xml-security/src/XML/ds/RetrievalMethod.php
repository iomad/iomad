<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a ds:RetrievalMethod element.
 *
 * @package simplesamlphp/xml-security
 */
final class RetrievalMethod extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a ds:RetrievalMethod
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms|null $transforms
     * @param string $URI
     * @param string|null $Type
     */
    final public function __construct(
        protected ?Transforms $transforms,
        protected string $URI,
        protected ?string $Type = null,
    ) {
        Assert::validURI($URI, SchemaViolationException::class); // Covers the empty string
        Assert::nullOrValidURI($Type, SchemaViolationException::class); // Covers the empty string
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\Transforms|null
     */
    public function getTransforms(): ?Transforms
    {
        return $this->transforms;
    }


    /**
     * @return string
     */
    public function getURI(): string
    {
        return $this->URI;
    }


    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->Type;
    }


    /**
     * Convert XML into a RetrievalMethod element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'RetrievalMethod', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, RetrievalMethod::NS, InvalidDOMElementException::class);

        $URI = self::getAttribute($xml, 'URI');
        $Type = self::getOptionalAttribute($xml, 'Type', null);

        $transforms = Transforms::getChildrenOfClass($xml);
        Assert::maxCount(
            $transforms,
            1,
            'A <ds:RetrievalMethod> may contain a maximum of one <ds:Transforms>.',
            TooManyElementsException::class,
        );

        return new static(
            array_pop($transforms),
            $URI,
            $Type,
        );
    }


    /**
     * Convert this RetrievalMethod element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this RetrievalMethod element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getURI());

        if ($this->getType() !== null) {
            $e->setAttribute('Type', $this->getType());
        }

        $this->getTransforms()?->toXML($e);

        return $e;
    }
}
