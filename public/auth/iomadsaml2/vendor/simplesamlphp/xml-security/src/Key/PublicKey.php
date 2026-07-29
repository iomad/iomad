<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Key;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\CryptoEncoding\PEM;

use function base64_encode;
use function chr;
use function chunk_split;
use function ord;
use function pack;
use function sprintf;

/**
 * A class modeling public keys for their use in asymmetric algorithms.
 *
 * @package simplesamlphp/xml-security
 */
class PublicKey extends AsymmetricKey
{
    /** @var int */
    public const ASN1_TYPE_INTEGER = 0x02; // 2

    /** @var int */
    public const ASN1_TYPE_BIT_STRING = 0x03; // 3

    /** @var int */
    public const ASN1_TYPE_SEQUENCE = 0x30; // 16

    /** @var int */
    public const ASN1_SIZE_128 = 0x80; // 128

    /** @var int */
    public const ASN1_SIZE_256 = 0x0100; // 256

    /** @var int */
    public const ASN1_SIZE_65535 = 0x010000; // 65535


    /**
     * Create a new public key from the PEM-encoded key material.
     *
     * @param \SimpleSAML\XMLSecurity\CryptoEncoding\PEM $key The PEM-encoded key material.
     */
    final public function __construct(
        #[\SensitiveParameter]
        PEM $key,
    ) {
        Assert::oneOf(
            $key->type(),
            [PEM::TYPE_PUBLIC_KEY, PEM::TYPE_RSA_PUBLIC_KEY],
            "PEM structure has the wrong type %s.",
        );

        parent::__construct($key);
    }


    /**
     * Encode data in ASN.1.
     *
     * @param int $type The type of data.
     * @param string $string The data to encode.
     *
     * @return null|string The encoded data, or null if it was too long.
     */
    protected static function makeASN1Segment(int $type, string $string): ?string
    {
        switch ($type) {
            case self::ASN1_TYPE_INTEGER:
                if (ord($string) > self::ASN1_SIZE_128 - 1) {
                    $string = chr(0) . $string;
                }
                break;
            case self::ASN1_TYPE_BIT_STRING:
                $string = chr(0) . $string;
                break;
        }

        $length = strlen($string);
        Assert::lessThan($length, self::ASN1_SIZE_65535);

        if ($length < self::ASN1_SIZE_128) {
            $output = sprintf("%c%c%s", $type, $length, $string);
        } elseif ($length < self::ASN1_SIZE_256) {
            $output = sprintf("%c%c%c%s", $type, self::ASN1_SIZE_128 + 1, $length, $string);
        } else { // ($length < self::ASN1_SIZE_65535)
            $output = sprintf(
                "%c%c%c%c%s",
                $type,
                self::ASN1_SIZE_128 + 2,
                $length / 0x0100,
                $length % 0x0100,
                $string,
            );
        }

        return $output;
    }


    /**
     * Create a new public key from its RSA details (modulus and exponent).
     *
     * @param string $modulus The modulus of the given key.
     * @param string $exponent The exponent of the given key.
     *
     * @return \SimpleSAML\XMLSecurity\Key\PublicKey A new public key with the given modulus and exponent.
     */
    public static function fromDetails(string $modulus, string $exponent): PublicKey
    {
        return new static(PEM::fromString(
            "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(
                base64_encode(
                    self::makeASN1Segment(
                        self::ASN1_TYPE_SEQUENCE,
                        pack("H*", "300D06092A864886F70D0101010500") . // RSA alg id
                        self::makeASN1Segment( // bitstring
                            self::ASN1_TYPE_BIT_STRING,
                            self::makeASN1Segment( // sequence
                                self::ASN1_TYPE_SEQUENCE,
                                self::makeASN1Segment(self::ASN1_TYPE_INTEGER, $modulus)
                                . self::makeASN1Segment(self::ASN1_TYPE_INTEGER, $exponent),
                            ),
                        ),
                    ),
                ),
                64,
                "\n",
            ) .
            "-----END PUBLIC KEY-----\n",
        ));
    }


    /**
     * Get a new public key from a file.
     *
     * @param string $file The file where the PEM-encoded private key is stored.
     *
     * @return static A new public key.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException If the file cannot be read.
     */
    public static function fromFile(string $file): static
    {
        return new static(PEM::fromFile($file));
    }
}
