<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing <xenc:EncryptionPropertiesType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractEncryptionPropertiesType extends AbstractXencElement implements
    SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * EncryptionProperty constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\EncryptionProperty[] $encryptionProperty
     * @param string|null $Id
     */
    final public function __construct(
        protected array $encryptionProperty,
        protected ?string $Id = null,
    ) {
        Assert::minCount($encryptionProperty, 1, MissingElementException::class);
        Assert::nullOrValidNCName($Id, SchemaViolationException::class);
    }


    /**
     * Get the value of the $encryptionProperty property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptionProperty[]
     */
    public function getEncryptionProperty(): array
    {
        return $this->encryptionProperty;
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
            EncryptionProperty::getChildrenOfClass($xml),
            self::getOptionalAttribute($xml, 'Id', null),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getEncryptionProperty() as $ep) {
            $ep->toXML($e);
        }

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        return $e;
    }
}
