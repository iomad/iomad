<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\XML\xenc\ReferenceList;

use function array_pop;

/**
 * Class representing <xenc11:DerivedKeyType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractDerivedKeyType extends AbstractXenc11Element implements
    SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * DerivedKey constructor.
     *
     * @param string|null $recipient
     * @param string|null $id
     * @param string|null $type
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\KeyDerivationMethod|null $keyDerivationMethod
     * @param \SimpleSAML\XMLSecurity\XML\xenc\ReferenceList|null $referenceList
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\DerivedKeyName|null $derivedKeyName
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\MasterKeyName|null $masterKeyName
     */
    final public function __construct(
        protected ?string $recipient = null,
        protected ?string $id = null,
        protected ?string $type = null,
        protected ?KeyDerivationMethod $keyDerivationMethod = null,
        protected ?ReferenceList $referenceList = null,
        protected ?DerivedKeyName $derivedKeyName = null,
        protected ?MasterKeyName $masterKeyName = null,
    ) {
        Assert::nullOrValidNCName($id, SchemaViolationException::class);
        Assert::nullOrValidURI($type, SchemaViolationException::class);
    }


    /**
     * Get the value of the $recipient property.
     *
     * @return string|null
     */
    public function getRecipient(): ?string
    {
        return $this->recipient;
    }


    /**
     * Get the value of the $id property.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    /**
     * Get the value of the $type property.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * Get the value of the $keyDerivationMethod property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\KeyDerivationMethod|null
     */
    public function getKeyDerivationMethod(): ?KeyDerivationMethod
    {
        return $this->keyDerivationMethod;
    }


    /**
     * Get the value of the $referenceList property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\ReferenceList|null
     */
    public function getReferenceList(): ?ReferenceList
    {
        return $this->referenceList;
    }


    /**
     * Get the value of the $derivedKeyName property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\DerivedKeyName|null
     */
    public function getDerivedKeyName(): ?DerivedKeyName
    {
        return $this->derivedKeyName;
    }


    /**
     * Get the value of the $masterKeyName property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\MasterKeyName|null
     */
    public function getMasterKeyName(): ?MasterKeyName
    {
        return $this->masterKeyName;
    }


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool
    {
        return empty($this->getKeyDerivationMethod())
            && empty($this->getReferenceList())
            && empty($this->getDerivedKeyName())
            && empty($this->getMasterKeyName())
            && empty($this->getRecipient())
            && empty($this->getId())
            && empty($this->getType());
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

        $keyDerivationMethod = KeyDerivationMethod::getChildrenOfClass($xml);
        Assert::maxCount($keyDerivationMethod, 1, TooManyElementsException::class);

        $referenceList = ReferenceList::getChildrenOfClass($xml);
        Assert::maxCount($referenceList, 1, TooManyElementsException::class);

        $derivedKeyName = DerivedKeyName::getChildrenOfClass($xml);
        Assert::maxCount($derivedKeyName, 1, TooManyElementsException::class);

        $masterKeyName = MasterKeyName::getChildrenOfClass($xml);
        Assert::maxCount($masterKeyName, 1, TooManyElementsException::class);

        return new static(
            self::getOptionalAttribute($xml, 'Recipient', null),
            self::getOptionalAttribute($xml, 'Id', null),
            self::getOptionalAttribute($xml, 'Type', null),
            array_pop($keyDerivationMethod),
            array_pop($referenceList),
            array_pop($derivedKeyName),
            array_pop($masterKeyName),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getRecipient() !== null) {
            $e->setAttribute('Recipient', $this->getRecipient());
        }

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        if ($this->getType() !== null) {
            $e->setAttribute('Type', $this->getType());
        }

        $this->getKeyDerivationMethod()?->toXML($e);
        $this->getReferenceList()?->toXML($e);
        $this->getDerivedKeyName()?->toXML($e);
        $this->getMasterKeyName()?->toXML($e);

        return $e;
    }
}
