<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;

/**
 * Abstract class representing encrypted data.
 *
 * Note: <xenc:EncryptionProperties> elements are not supported.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractEncryptedType extends AbstractXencElement
{
    /**
     * EncryptedData constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\CipherData $cipherData The CipherData object of this EncryptedData.
     * @param string|null $id The Id attribute of this object. Optional.
     * @param string|null $type The Type attribute of this object. Optional.
     * @param string|null $mimeType The MimeType attribute of this object. Optional.
     * @param string|null $encoding The Encoding attribute of this object. Optional.
     * @param \SimpleSAML\XMLSecurity\XML\xenc\EncryptionMethod|null $encryptionMethod
     *   The EncryptionMethod object of this EncryptedData. Optional.
     * @param \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null $keyInfo The KeyInfo object of this EncryptedData. Optional.
     */
    public function __construct(
        protected CipherData $cipherData,
        protected ?string $id = null,
        protected ?string $type = null,
        protected ?string $mimeType = null,
        protected ?string $encoding = null,
        protected ?EncryptionMethod $encryptionMethod = null,
        protected ?KeyInfo $keyInfo = null,
    ) {
        Assert::nullOrValidNCName($id, SchemaViolationException::class); // Covers the empty string
        Assert::nullOrValidURI($type, SchemaViolationException::class); // Covers the empty string
        Assert::nullOrValidURI($encoding, SchemaViolationException::class); // Covers the empty string
    }


    /**
     * Get the CipherData object.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\CipherData
     */
    public function getCipherData(): CipherData
    {
        return $this->cipherData;
    }


    /**
     * Get the value of the Encoding attribute.
     *
     * @return string|null
     */
    public function getEncoding(): ?string
    {
        return $this->encoding;
    }


    /**
     * Get the EncryptionMethod object.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptionMethod|null
     */
    public function getEncryptionMethod(): ?EncryptionMethod
    {
        return $this->encryptionMethod;
    }


    /**
     * Get the value of the Id attribute.
     *
     * @return string
     */
    public function getID(): ?string
    {
        return $this->id;
    }


    /**
     * Get the KeyInfo object.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null
     */
    public function getKeyInfo(): ?KeyInfo
    {
        return $this->keyInfo;
    }


    /**
     * Get the value of the MimeType attribute.
     *
     * @return string
     */
    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }


    /**
     * Get the value of the Type attribute.
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $id = $this->getId();
        if ($id !== null) {
            $e->setAttribute('Id', $id);
        }

        $type = $this->getType();
        if ($type !== null) {
            $e->setAttribute('Type', $type);
        }

        $mimeType = $this->getMimeType();
        if ($mimeType !== null) {
            $e->setAttribute('MimeType', $mimeType);
        }

        $encoding = $this->getEncoding();
        if ($encoding !== null) {
            $e->setAttribute('Encoding', $encoding);
        }

        $this->getEncryptionMethod()?->toXML($e);
        $this->getKeyInfo()?->toXML($e);
        $this->getCipherData()->toXML($e);

        return $e;
    }
}
