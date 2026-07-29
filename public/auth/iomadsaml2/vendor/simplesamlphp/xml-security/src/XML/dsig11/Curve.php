<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XMLSecurity\XML\dsig11\A;
use SimpleSAML\XMLSecurity\XML\dsig11\B;

/**
 * Class representing a dsig11:Curve element.
 *
 * @package simplesaml/xml-security
 */
final class Curve extends AbstractCurveType
{
    /**
     * Convert XML into a class instance
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

        $a = A::getChildrenOfClass($xml);
        Assert::minCount($a, 1, MissingElementException::class);
        Assert::maxCount($a, 1, TooManyElementsException::class);

        $b = B::getChildrenOfClass($xml);
        Assert::minCount($b, 1, MissingElementException::class);
        Assert::maxCount($b, 1, TooManyElementsException::class);

        return new static(
            array_pop($a),
            array_pop($b),
        );
    }
}
