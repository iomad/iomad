<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableAttributesTrait;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing <xenc:EncryptionPropertyType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractEncryptionPropertyType extends AbstractXencElement implements
    SchemaValidatableElementInterface
{
    use ExtendableAttributesTrait;
    use ExtendableElementTrait;
    use SchemaValidatableElementTrait;

    /** The namespace-attribute for the xs:anyAttribute element */
    public const XS_ANY_ATTR_NAMESPACE = [C::NS_XML];

    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * EncryptionProperty constructor.
     *
     * @param \SimpleSAML\XML\SerializableElementInterface[] $children
     * @param string|null $Target
     * @param string|null $Id
     * @param \SimpleSAML\XML\Attribute[] $namespacedAttributes
     */
    final public function __construct(
        array $children,
        protected ?string $Target = null,
        protected ?string $Id = null,
        array $namespacedAttributes = [],
    ) {
        Assert::minCount($children, 1, MissingElementException::class);
        Assert::nullOrValidURI($Target, SchemaViolationException::class);
        Assert::nullOrValidNCName($Id, SchemaViolationException::class);

        $this->setElements($children);
        $this->setAttributesNS($namespacedAttributes);
    }


    /**
     * Get the value of the $Target property.
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->Target;
    }


    /**
     * Get the value of the $Id property.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getLocalName(), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::getNamespaceURI(), InvalidDOMElementException::class);

        return new static(
            self::getChildElementsFromXML($xml),
            self::getOptionalAttribute($xml, 'Target', null),
            self::getOptionalAttribute($xml, 'Id', null),
            self::getAttributesNSFromXML($xml),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getTarget() !== null) {
            $e->setAttribute('Target', $this->getTarget());
        }

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        foreach ($this->getAttributesNS() as $attr) {
            $attr->toXML($e);
        }

        foreach ($this->getElements() as $child) {
            if (!$child->isEmptyElement()) {
                $child->toXML($e);
            }
        }

        return $e;
    }
}
