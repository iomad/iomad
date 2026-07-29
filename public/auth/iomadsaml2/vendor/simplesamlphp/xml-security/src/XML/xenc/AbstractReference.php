<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;

/**
 * Abstract class representing references. No custom elements are allowed.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractReference extends AbstractXencElement
{
    use ExtendableElementTrait;

    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * AbstractReference constructor.
     *
     * @param string $uri
     * @param \SimpleSAML\XML\SerializableElementInterface[] $elements
     */
    final public function __construct(
        protected string $uri,
        array $elements = [],
    ) {
        Assert::validURI($uri, SchemaViolationException::class); // Covers the empty string

        $this->setElements($elements);
    }


    /**
     * Get the value of the URI attribute of this reference.
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->uri;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException
     *   if the supplied element is missing one of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getClassName(static::class), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $URI = self::getAttribute($xml, 'URI');
        $elements = self::getChildElementsFromXML($xml);

        return new static($URI, $elements);
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getUri());

        foreach ($this->getElements() as $elt) {
            $elt->toXML($e);
        }

        return $e;
    }
}
