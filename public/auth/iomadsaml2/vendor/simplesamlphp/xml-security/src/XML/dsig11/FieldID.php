<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a dsig11:FieldID element.
 *
 * @package simplesaml/xml-security
 */
final class FieldID extends AbstractFieldIDType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Convert XML into a FieldID element
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

        $fieldId = array_merge(
            Prime::getChildrenOfClass($xml),
            TnB::getChildrenOfClass($xml),
            PnB::getChildrenOfClass($xml),
            GnB::getChildrenOfClass($xml),
            self::getChildElementsFromXML($xml),
        );

        Assert::count(
            $fieldId,
            1,
            'A <dsig11:FieldID> must contain exactly one child element',
            TooManyElementsException::class,
        );

        return new static(
            array_pop($fieldId),
        );
    }
}
