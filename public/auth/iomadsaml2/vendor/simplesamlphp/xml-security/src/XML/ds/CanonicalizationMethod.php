<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;

/**
 * Class representing a ds:CanonicalizationMethod element.
 *
 * @package simplesamlphp/xml-security
 */
final class CanonicalizationMethod extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a CanonicalizationMethod element.
     *
     * @param string $Algorithm
     */
    public function __construct(
        protected string $Algorithm,
    ) {
        Assert::validURI($Algorithm, SchemaViolationException::class);
        Assert::oneOf(
            $Algorithm,
            [
                C::C14N_EXCLUSIVE_WITH_COMMENTS,
                C::C14N_EXCLUSIVE_WITHOUT_COMMENTS,
                C::C14N_INCLUSIVE_WITH_COMMENTS,
                C::C14N_INCLUSIVE_WITHOUT_COMMENTS,
            ],
            'Invalid canonicalization method: %s',
            InvalidArgumentException::class,
        );
    }


    /**
     * Collect the value of the Algorithm-property
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->Algorithm;
    }


    /**
     * Convert XML into a CanonicalizationMethod
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'CanonicalizationMethod', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, CanonicalizationMethod::NS, InvalidDOMElementException::class);

        $Algorithm = CanonicalizationMethod::getAttribute($xml, 'Algorithm');

        return new static($Algorithm);
    }


    /**
     * Convert this CanonicalizationMethod element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this KeyName element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('Algorithm', $this->getAlgorithm());

        return $e;
    }
}
