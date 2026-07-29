<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\{SchemaValidatableElementInterface, SchemaValidatableElementTrait};
use SimpleSAML\XMLSecurity\XML\dsig11\DEREncodedKeyValue;

use function array_merge;

/**
 * Class representing a ds:KeyInfo element.
 *
 * @package simplesamlphp/xml-security
 */
final class KeyInfo extends AbstractKeyInfoType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Convert XML into a KeyInfo
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'KeyInfo', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, KeyInfo::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);

        $keyName = KeyName::getChildrenOfClass($xml);
        $keyValue = KeyValue::getChildrenOfClass($xml);
        $retrievalMethod = RetrievalMethod::getChildrenOfClass($xml);
        $x509Data = X509Data::getChildrenOfClass($xml);
        $pgpData = PGPData::getChildrenOfClass($xml);
        $spkiData = SPKIData::getChildrenOfClass($xml);
        $mgmtData = MgmtData::getChildrenOfClass($xml);
        $derEncodedKeyValue = DEREncodedKeyValue::getChildrenOfClass($xml);
        $other = self::getChildElementsFromXML($xml);

        $info = array_merge(
            $keyName,
            $keyValue,
            $retrievalMethod,
            $x509Data,
            $pgpData,
            $spkiData,
            $mgmtData,
            $derEncodedKeyValue,
            $other,
        );

        return new static($info, $Id);
    }
}
