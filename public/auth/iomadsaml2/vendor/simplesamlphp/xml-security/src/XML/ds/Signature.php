<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\Constants as C;

use function array_pop;

/**
 * Class representing a ds:Signature element.
 *
 * @package simplesamlphp/xml-security
 */
final class Signature extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Signature constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\SignedInfo $signedInfo
     * @param \SimpleSAML\XMLSecurity\XML\ds\SignatureValue $signatureValue
     * @param \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null $keyInfo
     * @param \SimpleSAML\XMLSecurity\XML\ds\DsObject[] $objects
     * @param string|null $Id
     */
    public function __construct(
        protected SignedInfo $signedInfo,
        protected SignatureValue $signatureValue,
        protected ?KeyInfo $keyInfo,
        protected array $objects = [],
        protected ?string $Id = null,
    ) {
        Assert::maxCount($objects, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($objects, DsObject::class);
        Assert::nullOrValidNCName($Id);
    }


    /**
     * Get the Id used for this signature.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\SignedInfo
     */
    public function getSignedInfo(): SignedInfo
    {
        return $this->signedInfo;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\SignatureValue
     */
    public function getSignatureValue(): SignatureValue
    {
        return $this->signatureValue;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null
     */
    public function getKeyInfo(): ?KeyInfo
    {
        return $this->keyInfo;
    }


    /**
     * Get the array of ds:Object elements attached to this signature.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\DsObject[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }


    /**
     * Convert XML into a Signature element
     *
     * @param \DOMElement $xml
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Signature', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Signature::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);

        $signedInfo = SignedInfo::getChildrenOfClass($xml);
        Assert::minCount(
            $signedInfo,
            1,
            'ds:Signature needs exactly one ds:SignedInfo element.',
            MissingElementException::class,
        );
        Assert::maxCount(
            $signedInfo,
            1,
            'ds:Signature needs exactly one ds:SignedInfo element.',
            TooManyElementsException::class,
        );

        $signatureValue = SignatureValue::getChildrenOfClass($xml);
        Assert::minCount(
            $signatureValue,
            1,
            'ds:Signature needs exactly one ds:SignatureValue element.',
            MissingElementException::class,
        );
        Assert::maxCount(
            $signatureValue,
            1,
            'ds:Signature needs exactly one ds:SignatureValue element.',
            TooManyElementsException::class,
        );

        $keyInfo = KeyInfo::getChildrenOfClass($xml);
        Assert::maxCount(
            $keyInfo,
            1,
            'ds:Signature can hold a maximum of one ds:KeyInfo element.',
            TooManyElementsException::class,
        );

        $objects = DsObject::getChildrenOfClass($xml);

        return new static(
            array_pop($signedInfo),
            array_pop($signatureValue),
            empty($keyInfo) ? null : array_pop($keyInfo),
            $objects,
            $Id,
        );
    }


    /**
     * Convert this Signature element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Signature element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        $this->getSignedInfo()->toXML($e);
        $this->getSignatureValue()->toXML($e);
        $this->getKeyInfo()?->toXML($e);

        foreach ($this->getObjects() as $o) {
            $o->toXML($e);
        }

        return $e;
    }
}
