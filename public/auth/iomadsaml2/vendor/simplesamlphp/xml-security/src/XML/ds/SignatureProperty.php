<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing a ds:SignatureProperty element.
 *
 * @package simplesamlphp/xml-security
 */
final class SignatureProperty extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use ExtendableElementTrait;
    use SchemaValidatableElementTrait;

    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * Initialize a ds:SignatureProperty
     *
     * @param \SimpleSAML\XML\SerializableElementInterface[] $elements
     * @param string $Target
     * @param string|null $Id
     */
    public function __construct(
        array $elements,
        protected string $Target,
        protected ?string $Id = null,
    ) {
        Assert::validURI($Target, SchemaViolationException::class); // Covers the empty string
        Assert::nullOrValidNCName($Id);

        $this->setElements($elements);
    }


    /**
     * @return string
     */
    public function getTarget(): string
    {
        return $this->Target;
    }


    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Convert XML into a SignatureProperty element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'SignatureProperty', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, SignatureProperty::NS, InvalidDOMElementException::class);

        $Target = self::getAttribute($xml, 'Target');
        $Id = self::getOptionalAttribute($xml, 'Id', null);

        $children = self::getChildElementsFromXML($xml);
        Assert::minCount(
            $children,
            1,
            'A <ds:SignatureProperty> must contain at least one element.',
            MissingElementException::class,
        );

        return new static(
            $children,
            $Target,
            $Id,
        );
    }


    /**
     * Convert this SignatureProperty element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this SignatureProperty element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('Target', $this->getTarget());

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        foreach ($this->getElements() as $element) {
            $element->toXML($e);
        }

        return $e;
    }
}
