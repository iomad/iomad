<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\xenc\CipherData;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptionMethod;

use function array_pop;

/**
 * Class containing encrypted data.
 *
 * Note: <xenc:EncryptionProperties> elements are not supported.
 *
 * @package simplesamlphp/xml-security
 */
final class EncryptedData extends AbstractEncryptedType implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingElementException
     *   If one of the mandatory child-elements is missing
     * @throws \SimpleSAML\XML\Exception\TooManyElementsException
     *   If too many child-elements of a type are specified
     */
    final public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'EncryptedData', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, EncryptedData::NS, InvalidDOMElementException::class);

        $cipherData = CipherData::getChildrenOfClass($xml);
        Assert::minCount(
            $cipherData,
            1,
            'At least one CipherData element found in <xenc:EncryptedData>.',
            MissingElementException::class,
        );
        Assert::maxCount(
            $cipherData,
            1,
            'No or more than one CipherData element found in <xenc:EncryptedData>.',
            TooManyElementsException::class,
        );

        $encryptionMethod = EncryptionMethod::getChildrenOfClass($xml);
        Assert::maxCount(
            $encryptionMethod,
            1,
            'No more than one EncryptionMethod element allowed in <xenc:EncryptedData>.',
            TooManyElementsException::class,
        );

        $keyInfo = KeyInfo::getChildrenOfClass($xml);
        Assert::maxCount(
            $keyInfo,
            1,
            'No more than one KeyInfo element allowed in <xenc:EncryptedData>.',
            TooManyElementsException::class,
        );

        return new static(
            $cipherData[0],
            self::getOptionalAttribute($xml, 'Id', null),
            self::getOptionalAttribute($xml, 'Type', null),
            self::getOptionalAttribute($xml, 'MimeType', null),
            self::getOptionalAttribute($xml, 'Encoding', null),
            array_pop($encryptionMethod),
            array_pop($keyInfo),
        );
    }
}
