<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\XsNamespace as NS;

use function array_pop;

/**
 * A class implementing the xenc:AbstractEncryptionMethod element.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractEncryptionMethod extends AbstractXencElement
{
    use ExtendableElementTrait;

    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * EncryptionMethod constructor.
     *
     * @param string $algorithm
     * @param \SimpleSAML\XMLSecurity\XML\xenc\KeySize|null $keySize
     * @param \SimpleSAML\XMLSecurity\XML\xenc\OAEPparams|null $oaepParams
     * @param list<\SimpleSAML\XML\SerializableElementInterface> $children
     */
    final public function __construct(
        protected string $algorithm,
        protected ?KeySize $keySize = null,
        protected ?OAEPparams $oaepParams = null,
        protected array $children = [],
    ) {
        Assert::validURI($algorithm, SchemaViolationException::class); // Covers the empty string

        $this->setElements($children);
    }


    /**
     * Get the URI identifying the algorithm used by this encryption method.
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }


    /**
     * Get the size of the key used by this encryption method.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\KeySize|null
     */
    public function getKeySize(): ?KeySize
    {
        return $this->keySize;
    }


    /**
     * Get the OAEP parameters.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\OAEPparams|null
     */
    public function getOAEPParams(): ?OAEPparams
    {
        return $this->oaepParams;
    }


    /**
     * Initialize an EncryptionMethod object from an existing XML.
     *
     * @param \DOMElement $xml
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException
     *   if the supplied element is missing one of the mandatory attributes
     * @throws \SimpleSAML\XML\Exception\TooManyElementsException
     *   if too many child-elements of a type are specified
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'EncryptionMethod', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $algorithm = self::getAttribute($xml, 'Algorithm');

        $keySize = KeySize::getChildrenOfClass($xml);
        Assert::maxCount($keySize, 1, TooManyElementsException::class);

        $oaepParams = OAEPparams::getChildrenOfClass($xml);
        Assert::maxCount($oaepParams, 1, TooManyElementsException::class);

        $children = self::getChildElementsFromXML($xml);

        return new static($algorithm, array_pop($keySize), array_pop($oaepParams), $children);
    }


    /**
     * Convert this EncryptionMethod object to XML.
     *
     * @param \DOMElement|null $parent The element we should append this EncryptionMethod to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('Algorithm', $this->getAlgorithm());

        $this->getKeySize()?->toXML($e);
        $this->getOAEPparams()?->toXML($e);

        foreach ($this->getElements() as $child) {
            $child->toXML($e);
        }

        return $e;
    }
}
