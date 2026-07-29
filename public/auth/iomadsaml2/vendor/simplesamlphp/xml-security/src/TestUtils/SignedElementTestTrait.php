<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\TestUtils;

use DOMDocument;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\Exception\NoSignatureFoundException;
use SimpleSAML\XMLSecurity\Exception\SignatureVerificationFailedException;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\ds\X509Certificate;
use SimpleSAML\XMLSecurity\XML\ds\X509Data;

use function array_keys;
use function boolval;
use function class_exists;
use function hexdec;
use function sprintf;

/**
 * A trait providing basic tests for signed elements.
 *
 * Only to be used by classes extending \PHPUnit\Framework\TestCase. Make sure to assign the class name of the class
 * you are testing to the $testedClass property.
 *
 * @package simplesamlphp/xml-security
 */
trait SignedElementTestTrait
{
    /**
     * A base document that we can reuse in our tests.
     *
     * @var \DOMDocument
     */
    protected static DOMDocument $xmlRepresentation;

    /**
     * The name of the class we are testing.
     *
     * @var class-string
     */
    protected static string $testedClass;


    /**
     * Test signing / verifying
     */
    public function testSignatures(): void
    {
        if (!class_exists(self::$testedClass)) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testSignatures(). Please set ' . self::class
                . ':$testedClass to a class-string representing the XML-class being tested',
            );
        } elseif (empty(self::$xmlRepresentation)) {
            $this->markTestSkipped(
                'Unable to run ' . self::class . '::testSignatures(). Please set ' . self::class
                . ':$xmlRepresentation to a DOMDocument representing the XML-class being tested',
            );
        } else {
            $algorithms = array_keys(C::$RSA_DIGESTS);
            foreach ($algorithms as $algorithm) {
                if (
                    boolval(OPENSSL_VERSION_NUMBER >= hexdec('0x30000000')) === true
                    && ($algorithm === C::SIG_RSA_SHA1 || $algorithm === C::SIG_RSA_RIPEMD160)
                ) {
                    // OpenSSL 3.0 disabled SHA1 and RIPEMD160 support
                    continue;
                }

                //
                // sign with two certificates
                //
                $signer = (new SignatureAlgorithmFactory([]))->getAlgorithm(
                    $algorithm,
                    PEMCertificatesMock::getPrivateKey(PEMCertificatesMock::PRIVATE_KEY),
                );

                $keyInfo = new KeyInfo([
                    new X509Data([new X509Certificate(
                        PEMCertificatesMock::getPlainPublicKeyContents(PEMCertificatesMock::PUBLIC_KEY),
                    )]),
                    new X509Data([new X509Certificate(
                        PEMCertificatesMock::getPlainPublicKeyContents(PEMCertificatesMock::OTHER_PUBLIC_KEY),
                    )]),
                ]);

                $unsigned = self::$testedClass::fromXML(self::$xmlRepresentation->documentElement);
                $unsigned->sign($signer, C::C14N_EXCLUSIVE_WITHOUT_COMMENTS, $keyInfo);
                $signed = self::$testedClass::fromXML($unsigned->toXML());
                $this->assertEquals(
                    $algorithm,
                    $signed->getSignature()->getSignedInfo()->getSignatureMethod()->getAlgorithm(),
                );

                // verify signature
                $verifier = (new SignatureAlgorithmFactory([]))->getAlgorithm(
                    $signed->getSignature()->getSignedInfo()->getSignatureMethod()->getAlgorithm(),
                    PEMCertificatesMock::getPublicKey(PEMCertificatesMock::PUBLIC_KEY),
                );

                try {
                    $verified = $signed->verify($verifier);
                } catch (
                    NoSignatureFoundException |
                    InvalidArgumentException |
                    SignatureVerificationFailedException $s
                ) {
                    $this->fail(sprintf('%s:  %s', $algorithm, $e->getMessage()));
                }
                $this->assertInstanceOf(self::$testedClass, $verified);

                $this->assertEquals(
                    PEMCertificatesMock::getPublicKey(PEMCertificatesMock::PUBLIC_KEY),
                    $verified->getVerifyingKey(),
                    sprintf('No validating certificate for algorithm: %s', $algorithm),
                );

                //
                // sign without certificates
                //
                $unsigned->sign($signer, C::C14N_EXCLUSIVE_WITHOUT_COMMENTS, null);
                $signed = self::$testedClass::fromXML($unsigned->toXML());

                // verify signature
                try {
                    $verified = $signed->verify($verifier);
                } catch (
                    NoSignatureFoundException |
                    InvalidArgumentException |
                    SignatureVerificationFailedException $e
                ) {
                    $this->fail(sprintf('%s:  %s', $algorithm, $e->getMessage()));
                }
                $this->assertInstanceOf(self::$testedClass, $verified);

                $this->assertEquals(
                    PEMCertificatesMock::getPublicKey(PEMCertificatesMock::PUBLIC_KEY),
                    $verified->getVerifyingKey(),
                    sprintf('No validating certificate for algorithm: %s', $algorithm),
                );

                //
                // verify with wrong key
                //
                $signer = (new SignatureAlgorithmFactory([]))->getAlgorithm(
                    $algorithm,
                    PEMCertificatesMock::getPrivateKey(PEMCertificatesMock::OTHER_PRIVATE_KEY),
                );
                $unsigned->sign($signer, C::C14N_EXCLUSIVE_WITHOUT_COMMENTS, null);
                $signed = self::$testedClass::fromXML($unsigned->toXML());

                // verify signature
                try {
                    $verified = $signed->verify($verifier);
                    $this->fail('Signature validated correctly with wrong certificate.');
                } catch (
                    NoSignatureFoundException |
                    InvalidArgumentException |
                    SignatureVerificationFailedException $e
                ) {
                    $this->assertEquals('Failed to verify signature.', $e->getMessage());
                }
                $this->assertInstanceOf(self::$testedClass, $verified);

                $this->assertEquals(
                    PEMCertificatesMock::getPublicKey(PEMCertificatesMock::PUBLIC_KEY),
                    $verified->getVerifyingKey(),
                    sprintf('No validating certificate for algorithm: %s', $algorithm),
                );
            }
        }
    }
}
