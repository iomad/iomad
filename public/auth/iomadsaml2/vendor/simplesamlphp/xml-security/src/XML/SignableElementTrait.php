<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmInterface;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\RuntimeException;
use SimpleSAML\XMLSecurity\Exception\UnsupportedAlgorithmException;
use SimpleSAML\XMLSecurity\Utils\XML;
use SimpleSAML\XMLSecurity\XML\ds\CanonicalizationMethod;
use SimpleSAML\XMLSecurity\XML\ds\DigestMethod;
use SimpleSAML\XMLSecurity\XML\ds\DigestValue;
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\ds\Reference;
use SimpleSAML\XMLSecurity\XML\ds\Signature;
use SimpleSAML\XMLSecurity\XML\ds\SignatureMethod;
use SimpleSAML\XMLSecurity\XML\ds\SignatureValue;
use SimpleSAML\XMLSecurity\XML\ds\SignedInfo;
use SimpleSAML\XMLSecurity\XML\ds\Transform;
use SimpleSAML\XMLSecurity\XML\ds\Transforms;

use function base64_encode;
use function hash;
use function in_array;

/**
 * Trait SignableElementTrait
 *
 * @package simplesamlphp/xml-security
 */
trait SignableElementTrait
{
    use CanonicalizableElementTrait;

    /** @var \SimpleSAML\XMLSecurity\XML\ds\Signature|null */
    protected ?Signature $signature = null;

    /** @var string */
    private string $c14nAlg = C::C14N_EXCLUSIVE_WITHOUT_COMMENTS;

    /** @var \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null */
    private ?KeyInfo $keyInfo = null;

    /** @var \SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmInterface|null */
    protected ?SignatureAlgorithmInterface $signer = null;


    /**
     * Get the ID of this element.
     *
     * When this method returns null, the signature created for this object will reference the entire document.
     *
     * @return string|null The ID of this element, or null if we don't have one.
     */
    abstract public function getId(): ?string;


    /**
     * Sign the current element.
     *
     * The signature will not be applied until toXML() is called.
     *
     * @param \SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmInterface $signer The actual signer implementation
     * to use.
     * @param string $canonicalizationAlg The identifier of the canonicalization algorithm to use.
     * @param \SimpleSAML\XMLSecurity\XML\ds\KeyInfo|null $keyInfo A KeyInfo object to add to the signature.
     */
    public function sign(
        SignatureAlgorithmInterface $signer,
        string $canonicalizationAlg = C::C14N_EXCLUSIVE_WITHOUT_COMMENTS,
        ?KeyInfo $keyInfo = null,
    ): void {
        $this->signer = $signer;
        $this->keyInfo = $keyInfo;
        Assert::oneOf(
            $canonicalizationAlg,
            [
                C::C14N_INCLUSIVE_WITH_COMMENTS,
                C::C14N_INCLUSIVE_WITHOUT_COMMENTS,
                C::C14N_EXCLUSIVE_WITH_COMMENTS,
                C::C14N_EXCLUSIVE_WITHOUT_COMMENTS,
            ],
            'Unsupported canonicalization algorithm: %s',
            UnsupportedAlgorithmException::class,
        );
        $this->c14nAlg = $canonicalizationAlg;
    }


    /**
     * Get a ds:Reference pointing to this object.
     *
     * @param string $digestAlg The digest algorithm to use.
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms $transforms The transforms to apply to the object.
     */
    private function getReference(
        string $digestAlg,
        Transforms $transforms,
        DOMElement $xml,
        string $canonicalDocument,
    ): Reference {
        $id = $this->getId();
        $uri = null;
        if (empty($id)) { // document reference
            Assert::notNull(
                $xml->ownerDocument->documentElement,
                'Cannot create a document reference without a root element in the document.',
                RuntimeException::class,
            );
            Assert::true(
                $xml->isSameNode($xml->ownerDocument->documentElement),
                'Cannot create a document reference when signing an object that is not the root of the document. ' .
                'Please give your object an identifier.',
                RuntimeException::class,
            );
            if (in_array($this->c14nAlg, [C::C14N_INCLUSIVE_WITH_COMMENTS, C::C14N_EXCLUSIVE_WITH_COMMENTS])) {
                $uri = '#xpointer(/)';
            }
        } elseif (in_array($this->c14nAlg, [C::C14N_INCLUSIVE_WITH_COMMENTS, C::C14N_EXCLUSIVE_WITH_COMMENTS])) {
            // regular reference, but must retain comments
            $uri = '#xpointer(id(' . $id . '))';
        } else { // regular reference, can ignore comments
            $uri = '#' . $id;
        }

        return new Reference(
            new DigestMethod($digestAlg),
            new DigestValue(base64_encode(hash(C::$DIGEST_ALGORITHMS[$digestAlg], $canonicalDocument, true))),
            $transforms,
            null,
            null,
            $uri,
        );
    }


    /**
     * Do the actual signing of the document.
     *
     * Note that this method does not insert the signature in the returned \DOMElement. The signature will be available
     * in $this->signature as a \SimpleSAML\XMLSecurity\XML\ds\Signature object, which can then be converted to XML
     * calling toXML() on it, passing the \DOMElement value returned here as a parameter. The resulting \DOMElement
     * can then be inserted in the position desired.
     *
     * E.g.:
     *     $xml = // our XML to sign
     *     $signedXML = $this->doSign($xml);
     *     $signedXML->appendChild($this->signature->toXML($signedXML));
     *
     * @param \DOMElement $xml The element to sign.
     * @return \DOMElement The signed element, without the signature attached to it just yet.
     */
    protected function doSign(DOMElement $xml): DOMElement
    {
        Assert::notNull(
            $this->signer,
            'Cannot call toSignedXML() without calling sign() first.',
            RuntimeException::class,
        );

        $algorithm = $this->signer->getAlgorithmId();
        $digest = $this->signer->getDigest();

        $transforms = new Transforms([
            new Transform(C::XMLDSIG_ENVELOPED),
            new Transform($this->c14nAlg),
        ]);

        $canonicalDocument = XML::processTransforms($transforms, $xml);

        $signedInfo = new SignedInfo(
            new CanonicalizationMethod($this->c14nAlg),
            new SignatureMethod($algorithm),
            [$this->getReference($digest, $transforms, $xml, $canonicalDocument)],
        );

        $signingData = $signedInfo->canonicalize($this->c14nAlg);
        $signedData = base64_encode($this->signer->sign($signingData));

        $this->setSignature(new Signature($signedInfo, new SignatureValue($signedData), $this->keyInfo));
        return DOMDocumentFactory::fromString($canonicalDocument)->documentElement;
    }

    /**
     * Get the list of algorithms that are blacklisted for any signing operation.
     *
     * @return string[]|null An array with all algorithm identifiers that are blacklisted, or null to use this
     * libraries default.
     */
    abstract public function getBlacklistedAlgorithms(): ?array;
}
