<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Key;

use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\CryptoEncoding\PEM;
use SimpleSAML\XMLSecurity\Exception\OpenSSLException;
use SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException;

use function openssl_pkey_get_details;
use function openssl_pkey_get_public;
use function openssl_x509_fingerprint;
use function openssl_x509_parse;

/**
 * A class modeling X509 certificates.
 *
 * @package simplesamlphp/xml-security
 */
class X509Certificate
{
    /** @var \SimpleSAML\XMLSecurity\Key\PublicKey */
    protected PublicKey $publicKey;

    /** @var array<string, string> */
    protected array $thumbprint = [];

    /** @var array<string, mixed> */
    protected array $parsed = [];


    /**
     * Create a new X509 certificate from its PEM-encoded representation.
     *
     * @param \SimpleSAML\XMLSecurity\CryptoEncoding\PEM $material
     *   The PEM-encoded certificate or the path to a file containing it.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\OpenSSLException If the certificate cannot be exported to PEM format.
     */
    final public function __construct(
        protected PEM $material,
    ) {
        Assert::oneOf($material->type(), [PEM::TYPE_CERTIFICATE], "PEM structure has the wrong type %s.");

        if (($key = openssl_pkey_get_public($material->string())) === false) {
            throw new OpenSSLException('Failed to read key');
        }

        if (($details = openssl_pkey_get_details($key)) === false) {
            throw new OpenSSLException('Failed to export key');
        }

        $this->publicKey = new PublicKey(PEM::fromString($details['key']));

        $this->thumbprint[C::DIGEST_SHA1] = $this->getRawThumbprint();
        $this->parsed = openssl_x509_parse($material->string());
    }


    /**
     * Return the public key associated with this certificate.
     *
     * @return \SimpleSAML\XMLSecurity\Key\PublicKey The public key.
     */
    public function getPublicKey(): PublicKey
    {
        return $this->publicKey;
    }


    /**
     * Return the key material associated with this key.
     *
     * @return string The key material.
     */
    public function getMaterial(): string
    {
        return $this->material->string();
    }


    /**
     * Return the raw PEM-object associated with this key.
     *
     * @return \SimpleSAML\XMLSecurity\CryptoEncoding\PEM The raw material.
     */
    public function getPEM(): PEM
    {
        return $this->material;
    }


    /**
     * Get the raw thumbprint of a certificate
     *
     * @param string $alg The digest algorithm to use. Defaults to SHA1.
     *
     * @return string The thumbprint associated with the given certificate.
     */
    public function getRawThumbprint(string $alg = C::DIGEST_SHA1): string
    {
        if (isset($this->thumbprint[$alg])) {
            return $this->thumbprint[$alg];
        }

        Assert::keyExists(
            C::$DIGEST_ALGORITHMS,
            $alg,
            'Invalid digest algorithm identifier',
            UnsupportedAlgorithmException::class,
        );

        return $this->thumbprint[$alg] = openssl_x509_fingerprint(
            $this->material->string(),
            C::$DIGEST_ALGORITHMS[$alg],
        );
    }


    /**
     * Get the details of this certificate.
     *
     * @return array<string, mixed> An array with all the details of the certificate.
     *
     * @see openssl_x509_parse()
     */
    public function getCertificateDetails(): array
    {
        return $this->parsed;
    }


    /**
     * Get a new X509 certificate from a file.
     *
     * @param string $file The file where the PEM-encoded certificate is stored.
     *
     * @return static A new X509Certificate key.
     */
    public static function fromFile(string $file): static
    {
        return new static(PEM::fromFile($file));
    }
}
