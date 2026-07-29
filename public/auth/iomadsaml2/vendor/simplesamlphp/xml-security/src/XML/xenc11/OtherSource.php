<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function array_pop;

/**
 * A class implementing the xenc11:OtherSource element.
 *
 * @package simplesamlphp/xml-security
 */
final class OtherSource extends AbstractAlgorithmIdentifierType
{
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

        $parameter = Parameters::getChildrenOfClass($xml);
        Assert::maxCount($parameter, 1, TooManyElementsException::class);

        return new static(
            self::getOptionalAttribute($xml, 'Algorithm', null),
            array_pop($parameter),
        );
    }
}
