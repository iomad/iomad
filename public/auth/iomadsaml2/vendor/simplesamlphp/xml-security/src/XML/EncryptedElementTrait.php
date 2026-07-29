<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\Exception\NoEncryptedDataException;
use SimpleSAML\XMLSecurity\Exception\RuntimeException;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedData;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedKey;

/**
 * Trait aggregating functionality for encrypted elements.
 *
 * @package simplesamlphp/xml-security
 */
trait EncryptedElementTrait
{
    /** @var \SimpleSAML\XMLSecurity\XML\xenc\EncryptedKey|null */
    protected ?EncryptedKey $encryptedKey = null;


    /**
     * Constructor for encrypted elements.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\EncryptedData $encryptedData The EncryptedData object.
     */
    public function __construct(
        protected EncryptedData $encryptedData,
    ) {
        $keyInfo = $this->encryptedData->getKeyInfo();
        if ($keyInfo === null) {
            return;
        }

        foreach ($keyInfo->getInfo() as $info) {
            if ($info instanceof EncryptedKey) {
                $this->encryptedKey = $info;
                break;
            }
        }
    }


    /**
     * Whether the encrypted object is accompanied by the decryption key or not.
     *
     * @return bool
     */
    public function hasDecryptionKey(): bool
    {
        return $this->encryptedKey !== null;
    }


    /**
     * Get the encrypted key used to encrypt the current element.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptedKey|null
     */
    public function getEncryptedKey(): ?EncryptedKey
    {
        return $this->encryptedKey;
    }


    /**
     * Get the EncryptedData object.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\EncryptedData
     */
    public function getEncryptedData(): EncryptedData
    {
        return $this->encryptedData;
    }


    /**
     * Decrypt the data in any given element.
     *
     * Use this method to decrypt an EncryptedData XML elemento into a string. If the resulting plaintext represents
     * an XML document which has a corresponding implementation extending \SimpleSAML\XML\ElementInterface, you
     * can call this method to build an object from the resulting plaintext:
     *
     *     $data = $this->decryptData($decryptor);
     *     $xml = \SimpleSAML\XML\DOMDocumentFactory::fromString($data);
     *     $object = MyObject::fromXML($xml->documentElement);
     *
     * If the class using this trait implements \SimpleSAML\XMLSecurity\XML\EncryptedElementInterface, then the
     * decrypt() method will only need the proposed code and return the object.
     *
     * @param \SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface $decryptor The decryptor to use to
     * decrypt the object.
     *
     * @return string The decrypted data.
     */
    protected function decryptData(EncryptionAlgorithmInterface $decryptor): string
    {
        $encData = $this->getEncryptedData();
        if (!$encData instanceof EncryptedData) {
            throw new NoEncryptedDataException();
        }

        $algId = $decryptor->getAlgorithmId();
        $encMethod = $this->getEncryptedData()->getEncryptionMethod();
        if ($encMethod !== null) {
            $algId = $encMethod->getAlgorithm();
        }

        if (in_array($decryptor->getAlgorithmId(), C::$KEY_TRANSPORT_ALGORITHMS)) {
            // the decryptor uses a key transport algorithm, check if we have a session key
            if ($this->hasDecryptionKey() === null) {
                throw new RuntimeException('Cannot use a key transport algorithm to decrypt an object.');
            }

            if ($encMethod === null) {
                throw new RuntimeException('Cannot decrypt data with a session key and no EncryptionMethod.');
            }

            $encryptedKey = $this->getEncryptedKey();
            $decryptionKey = $encryptedKey->decrypt($decryptor);

            $factory = new EncryptionAlgorithmFactory(
                $this->getBlacklistedAlgorithms() ?? EncryptionAlgorithmFactory::DEFAULT_BLACKLIST,
            );
            $decryptor = $factory->getAlgorithm($encMethod->getAlgorithm(), new SymmetricKey($decryptionKey));
            $decryptor->setBackend($this->getEncryptionBackend());
        }

        if ($algId !== $decryptor->getAlgorithmId()) {
            throw new InvalidArgumentException('Decryption algorithm does not match EncryptionMethod.');
        }

        return $decryptor->decrypt(base64_decode($encData->getCipherData()->getCipherValue()->getContent(), true));
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same(
            $xml->localName,
            AbstractElement::getClassName(static::class),
            InvalidDOMElementException::class,
        );
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $ed = EncryptedData::getChildrenOfClass($xml);
        Assert::count(
            $ed,
            1,
            sprintf(
                'No more or less than one EncryptedData element allowed in %s.',
                AbstractElement::getClassName(static::class),
            ),
            TooManyElementsException::class,
        );

        return new static($ed[0]);
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $this->encryptedData->toXML($e);
        return $e;
    }


    /**
     * Create a document structure for this element.
     *
     * The AbstractElement class implements this method. If your object inherits from that class, you will already
     * have this method out of the box.
     *
     * @param \DOMElement|null $parent The element we should append to.
     * @return \DOMElement
     */
    abstract public function instantiateParentElement(?DOMElement $parent = null): DOMElement;


    /**
     * Get the encryption backend to use for any encryption operation.
     *
     * @return \SimpleSAML\XMLSecurity\Backend\EncryptionBackend|null The encryption backend to use, or null if we
     * want to use the default.
     */
    abstract public function getEncryptionBackend(): ?EncryptionBackend;


    /**
     * Get the list of algorithms that are blacklisted for any encryption operation.
     *
     * @return string[]|null An array with all algorithm identifiers that are blacklisted, or null to use this
     * libraries default.
     */
    abstract public function getBlacklistedAlgorithms(): ?array;
}
