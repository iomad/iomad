<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\XML\ds\DigestMethod;

use function array_pop;

/**
 * Class representing <xenc11:ConcatKDFParamsType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractConcatKDFParamsType extends AbstractXenc11Element implements
    SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * ConcatKDFParams constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestMethod $digestMethod
     * @param string|null $AlgorithmID
     * @param string|null $PartyUInfo
     * @param string|null $PartyVInfo
     * @param string|null $SuppPubInfo
     * @param string|null $SuppPrivInfo
     */
    final public function __construct(
        protected DigestMethod $digestMethod,
        protected ?string $AlgorithmID = null,
        protected ?string $PartyUInfo = null,
        protected ?string $PartyVInfo = null,
        protected ?string $SuppPubInfo = null,
        protected ?string $SuppPrivInfo = null,
    ) {
        Assert::validHexBinary($AlgorithmID, SchemaViolationException::class);
        Assert::validHexBinary($PartyUInfo, SchemaViolationException::class);
        Assert::validHexBinary($PartyVInfo, SchemaViolationException::class);
        Assert::validHexBinary($SuppPubInfo, SchemaViolationException::class);
        Assert::validHexBinary($SuppPrivInfo, SchemaViolationException::class);
    }


    /**
     * Get the value of the $digestMethod property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\DigestMethod
     */
    public function getDigestMethod(): DigestMethod
    {
        return $this->digestMethod;
    }


    /**
     * Get the value of the $AlgorithmID property.
     *
     * @return string|null
     */
    public function getAlgorithmID(): ?string
    {
        return $this->AlgorithmID;
    }


    /**
     * Get the value of the $PartyUInfo property.
     *
     * @return string|null
     */
    public function getPartyUInfo(): ?string
    {
        return $this->PartyUInfo;
    }


    /**
     * Get the value of the $PartyVInfo property.
     *
     * @return string|null
     */
    public function getPartyVInfo(): ?string
    {
        return $this->PartyVInfo;
    }


    /**
     * Get the value of the $SuppPubInfo property.
     *
     * @return string|null
     */
    public function getSuppPubInfo(): ?string
    {
        return $this->SuppPubInfo;
    }


    /**
     * Get the value of the $SuppPrivInfo property.
     *
     * @return string|null
     */
    public function getSuppPrivInfo(): ?string
    {
        return $this->SuppPrivInfo;
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

        $digestMethod = DigestMethod::getChildrenOfClass($xml);
        Assert::minCount($digestMethod, 1, MissingElementException::class);
        Assert::maxCount($digestMethod, 1, TooManyElementsException::class);

        return new static(
            array_pop($digestMethod),
            self::getOptionalAttribute($xml, 'AlgorithmID', null),
            self::getOptionalAttribute($xml, 'PartyUInfo', null),
            self::getOptionalAttribute($xml, 'PartyVInfo', null),
            self::getOptionalAttribute($xml, 'SuppPubInfo', null),
            self::getOptionalAttribute($xml, 'SuppPrivInfo', null),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getAlgorithmID() !== null) {
            $e->setAttribute('AlgorithmID', $this->getAlgorithmID());
        }

        if ($this->getPartyUInfo() !== null) {
            $e->setAttribute('PartyUInfo', $this->getPartyUInfo());
        }

        if ($this->getPartyVInfo() !== null) {
            $e->setAttribute('PartyVInfo', $this->getPartyVInfo());
        }

        if ($this->getSuppPubInfo() !== null) {
            $e->setAttribute('SuppPubInfo', $this->getSuppPubInfo());
        }

        if ($this->getSuppPrivInfo() !== null) {
            $e->setAttribute('SuppPrivInfo', $this->getSuppPrivInfo());
        }

        $this->getDigestMethod()->toXML($e);

        return $e;
    }
}
