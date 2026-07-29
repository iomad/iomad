<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\XML\ds\AbstractDsElement;

use function array_pop;

/**
 * Abstract class representing the PGPDataType.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractPGPDataType extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use ExtendableElementTrait;
    use SchemaValidatableElementTrait;

    /** @var \SimpleSAML\XML\XsNamespace */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * Initialize a PGPData element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\PGPKeyID|null $pgpKeyId
     * @param \SimpleSAML\XMLSecurity\XML\ds\PGPKeyPacket|null $pgpKeyPacket
     * @param array<\SimpleSAML\XML\SerializableElementInterface> $children
     * @throws \SimpleSAML\XML\Exception\SchemaViolationException
     */
    final public function __construct(
        protected ?PGPKeyID $pgpKeyId = null,
        protected ?PGPKeyPacket $pgpKeyPacket = null,
        array $children = [],
    ) {
        if ($pgpKeyId === null && $pgpKeyPacket === null) {
            throw new SchemaViolationException("ds:PGPKeyID and ds:PGPKeyPacket can't both be null.");
        }

        $this->setElements($children);
    }


    /**
     * Collect the value of the PGPKeyID-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\PGPKeyID|null
     */
    public function getPGPKeyID(): ?PGPKeyID
    {
        return $this->pgpKeyId;
    }


    /**
     * Collect the value of the PGPKeyPacket-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\PGPKeyPacket|null
     */
    public function getPGPKeyPacket(): ?PGPKeyPacket
    {
        return $this->pgpKeyPacket;
    }


    /**
     * Convert XML into a PGPData
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getLocalName(), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $pgpKeyId = PGPKeyID::getChildrenOfClass($xml);
        Assert::maxCount($pgpKeyId, 1, TooManyElementsException::class);

        $pgpKeyPacket = PGPKeyPacket::getChildrenOfClass($xml);
        Assert::maxCount($pgpKeyPacket, 1, TooManyElementsException::class);

        return new static(
            array_pop($pgpKeyId),
            array_pop($pgpKeyPacket),
            self::getChildElementsFromXML($xml),
        );
    }


    /**
     * Convert this PGPData to XML.
     *
     * @param \DOMElement|null $parent The element we should append this PGPData to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getPGPKeyId()?->toXML($e);
        $this->getPGPKeyPacket()?->toXML($e);

        foreach ($this->getElements() as $elt) {
            $elt->toXML($e);
        }

        return $e;
    }
}
