<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Utils;

use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;

use function array_unshift;
use function chunk_split;
use function implode;
use function is_array;
use function preg_match;

/**
 * Collection of Utility functions specifically for certificates
 */
class Certificate
{
    /**
     * The pattern that the contents of a certificate should adhere to
     */
    public const CERTIFICATE_PATTERN = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
    public const PUBLIC_KEY_PATTERN = '/^-----BEGIN PUBLIC KEY-----([^-]*)^-----END PUBLIC KEY-----/m';
    public const PRIVATE_KEY_PATTERN = '/^-----BEGIN RSA PRIVATE KEY-----([^-]*)^-----END RSA PRIVATE KEY-----/m';


    /**
     * @param string $certificate
     * @param string $pattern
     *
     * @return bool
     */
    public static function hasValidStructure(string $certificate, string $pattern = self::PUBLIC_KEY_PATTERN): bool
    {
        return !!preg_match($pattern, $certificate);
    }


    /**
     * @param string $X509CertificateContents
     *
     * @return string
     */
    public static function convertToCertificate(string $X509CertificateContents): string
    {
        return "-----BEGIN CERTIFICATE-----\n"
                . chunk_split($X509CertificateContents, 64, "\n")
                . "-----END CERTIFICATE-----";
    }


    /**
     * @param array<string, mixed>|string $issuer
     *
     * @return string
     */
    public static function parseIssuer(array|string $issuer): string
    {
        if (is_array($issuer)) {
            $parts = [];
            foreach ($issuer as $key => $value) {
                array_unshift($parts, $key . '=' . $value);
            }
            return implode(',', $parts);
        }

        return $issuer;
    }


    /**
     * @param string $key The PEM-encoded key
     * @param string $pattern The pattern to use
     * @return string The stripped key
     */
    public static function stripHeaders(string $key, string $pattern = self::PUBLIC_KEY_PATTERN): string
    {
        $matches = [];
        $result = preg_match($pattern, $key, $matches);
        if ($result === false) {
            throw new InvalidArgumentException('Could not find content matching the provided pattern.');
        }

        return preg_replace('/\s+/', '', $matches[1]);
    }
}
