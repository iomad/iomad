<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

use function array_pop;

/**
 * Class representing <xenc:CipherData>.
 *
 * @package simplesamlphp/xml-security
 */
class CipherData extends AbstractXencElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * CipherData constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\CipherValue|null $cipherValue
     * @param \SimpleSAML\XMLSecurity\XML\xenc\CipherReference|null $cipherReference
     */
    final public function __construct(
        protected ?CipherValue $cipherValue,
        protected ?CipherReference $cipherReference = null,
    ) {
        Assert::oneOf(
            null,
            [$cipherValue, $cipherReference],
            'Can only have one of CipherValue/CipherReference',
        );

        Assert::false(
            is_null($cipherValue) && is_null($cipherReference),
            'You need either a CipherValue or a CipherReference',
        );
    }


    /**
     * Get the value of the $cipherValue property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\CipherValue|null
     */
    public function getCipherValue(): ?CipherValue
    {
        return $this->cipherValue;
    }


    /**
     * Get the CipherReference element inside this CipherData object.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\CipherReference|null
     */
    public function getCipherReference(): ?CipherReference
    {
        return $this->cipherReference;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'CipherData', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, CipherData::NS, InvalidDOMElementException::class);

        $cv = CipherValue::getChildrenOfClass($xml);
        Assert::maxCount(
            $cv,
            1,
            'More than one CipherValue element in <xenc:CipherData>',
            TooManyElementsException::class,
        );

        $cr = CipherReference::getChildrenOfClass($xml);
        Assert::maxCount(
            $cr,
            1,
            'More than one CipherReference element in <xenc:CipherData>',
            TooManyElementsException::class,
        );

        return new static(
            empty($cv) ? null : array_pop($cv),
            empty($cr) ? null : array_pop($cr),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getCipherValue()?->toXML($e);
        $this->getCipherReference()?->toXML($e);

        return $e;
    }
}
