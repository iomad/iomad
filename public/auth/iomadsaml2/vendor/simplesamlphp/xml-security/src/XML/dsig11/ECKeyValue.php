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
 * Class representing a dsig11:ECKeyValue element.
 *
 * @package simplesaml/xml-security
 */
final class ECKeyValue extends AbstractECKeyValueType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Convert XML into a ECKeyValue element
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

        $publicKey = PublicKey::getChildrenOfClass($xml);
        Assert::minCount($publicKey, 1, MissingElementException::class);
        Assert::maxCount($publicKey, 1, TooManyElementsException::class);

        $ecParameters = ECParameters::getChildrenOfClass($xml);
        Assert::maxCount($ecParameters, 1, TooManyElementsException::class);

        $namedCurve = NamedCurve::getChildrenOfClass($xml);
        Assert::maxCount($namedCurve, 1, TooManyElementsException::class);

        return new static(
            array_pop($publicKey),
            self::getOptionalAttribute($xml, 'Id', null),
            array_pop($ecParameters),
            array_pop($namedCurve),
        );
    }
}
