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
 * Class representing a dsig11:TnB element.
 *
 * @package simplesaml/xml-security
 */
final class TnB extends AbstractTnBFieldParamsType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Convert XML into a TnB element
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

        $k = K::getChildrenOfClass($xml);
        Assert::minCount($k, 1, MissingElementException::class);
        Assert::maxCount($k, 1, TooManyElementsException::class);

        $m = M::getChildrenOfClass($xml);
        Assert::minCount($m, 1, MissingElementException::class);
        Assert::maxCount($m, 1, TooManyElementsException::class);

        return new static(
            array_pop($m),
            array_pop($k),
        );
    }
}
