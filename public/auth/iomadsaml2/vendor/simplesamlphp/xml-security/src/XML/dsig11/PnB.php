<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

use function array_pop;

/**
 * Class representing a dsig11:PnB element.
 *
 * @package simplesaml/xml-security
 */
final class PnB extends AbstractPnBFieldParamsType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Convert XML into a PnB element
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
        Assert::same($xml->namespaceURI, static::getNamespaceURI(), InvalidDOMElementException::class);

        $k1 = K1::getChildrenOfClass($xml);
        Assert::minCount($k1, 1, MissingElementException::class);
        Assert::maxCount($k1, 1, TooManyElementsException::class);

        $k2 = K2::getChildrenOfClass($xml);
        Assert::minCount($k2, 1, MissingElementException::class);
        Assert::maxCount($k2, 1, TooManyElementsException::class);

        $k3 = K3::getChildrenOfClass($xml);
        Assert::minCount($k3, 1, MissingElementException::class);
        Assert::maxCount($k3, 1, TooManyElementsException::class);

        $m = M::getChildrenOfClass($xml);
        Assert::minCount($m, 1, MissingElementException::class);
        Assert::maxCount($m, 1, TooManyElementsException::class);

        return new static(
            array_pop($m),
            array_pop($k1),
            array_pop($k2),
            array_pop($k3),
        );
    }
}
