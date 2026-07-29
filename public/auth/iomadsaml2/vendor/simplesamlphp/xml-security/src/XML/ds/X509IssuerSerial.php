<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function array_pop;

/**
 * Class representing a ds:X509IssuerSerial element.
 *
 * @package simplesaml/xml-security
 */
final class X509IssuerSerial extends AbstractDsElement
{
    /**
     * Initialize a X509SubjectName element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\X509IssuerName $X509IssuerName
     * @param \SimpleSAML\XMLSecurity\XML\ds\X509SerialNumber $X509SerialNumber
     */
    public function __construct(
        protected X509IssuerName $X509IssuerName,
        protected X509SerialNumber $X509SerialNumber,
    ) {
    }


    /**
     * Collect the value of the X509IssuerName-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\X509IssuerName
     */
    public function getIssuerName(): X509IssuerName
    {
        return $this->X509IssuerName;
    }


    /**
     * Collect the value of the X509SerialNumber-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\X509SerialNumber
     */
    public function getSerialNumber(): X509SerialNumber
    {
        return $this->X509SerialNumber;
    }


    /**
     * Convert XML into a X509IssuerSerial
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'X509IssuerSerial', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, X509IssuerSerial::NS, InvalidDOMElementException::class);

        $issuer = X509IssuerName::getChildrenOfClass($xml);
        $serial = X509SerialNumber::getChildrenOfClass($xml);

        Assert::minCount($issuer, 1, MissingElementException::class);
        Assert::maxCount($issuer, 1, TooManyElementsException::class);

        Assert::minCount($serial, 1, MissingElementException::class);
        Assert::maxCount($serial, 1, TooManyElementsException::class);

        return new static(
            array_pop($issuer),
            array_pop($serial),
        );
    }


    /**
     * Convert this X509IssuerSerial element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this X509IssuerSerial element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getIssuerName()->toXML($e);
        $this->getSerialNumber()->toXML($e);

        return $e;
    }
}
