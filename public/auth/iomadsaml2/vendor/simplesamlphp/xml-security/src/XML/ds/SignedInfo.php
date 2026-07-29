<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\XML\CanonicalizableElementInterface;
use SimpleSAML\XMLSecurity\XML\CanonicalizableElementTrait;

use function array_pop;

/**
 * Class representing a ds:SignedInfo element.
 *
 * @package simplesamlphp/xml-security
 */
final class SignedInfo extends AbstractDsElement implements
    CanonicalizableElementInterface,
    SchemaValidatableElementInterface
{
    use CanonicalizableElementTrait;
    use SchemaValidatableElementTrait;

    /*
     * @var DOMElement
     */
    protected ?DOMElement $xml = null;


    /**
     * Initialize a SignedInfo.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\CanonicalizationMethod $canonicalizationMethod
     * @param \SimpleSAML\XMLSecurity\XML\ds\SignatureMethod $signatureMethod
     * @param \SimpleSAML\XMLSecurity\XML\ds\Reference[] $references
     * @param string|null $Id
     */
    public function __construct(
        protected CanonicalizationMethod $canonicalizationMethod,
        protected SignatureMethod $signatureMethod,
        protected array $references,
        protected ?string $Id = null,
    ) {
        Assert::maxCount($references, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($references, Reference::class, InvalidArgumentException::class);
        Assert::nullOrValidNCName($Id);
    }


    /**
     * Collect the value of the canonicalizationMethod-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\CanonicalizationMethod
     */
    public function getCanonicalizationMethod(): CanonicalizationMethod
    {
        return $this->canonicalizationMethod;
    }


    /**
     * Collect the value of the signatureMethod-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\SignatureMethod
     */
    public function getSignatureMethod(): SignatureMethod
    {
        return $this->signatureMethod;
    }


    /**
     * Collect the value of the references-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Reference[]
     */
    public function getReferences(): array
    {
        return $this->references;
    }


    /**
     * Collect the value of the Id-property
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * @inheritDoc
     */
    protected function getOriginalXML(): DOMElement
    {
        if ($this->xml !== null) {
            return $this->xml;
        }

        return $this->toXML();
    }


    /**
     * Convert XML into a SignedInfo instance
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'SignedInfo', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, SignedInfo::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);

        $canonicalizationMethod = CanonicalizationMethod::getChildrenOfClass($xml);
        Assert::minCount(
            $canonicalizationMethod,
            1,
            'A ds:SignedInfo element must contain exactly one ds:CanonicalizationMethod',
            MissingElementException::class,
        );
        Assert::maxCount(
            $canonicalizationMethod,
            1,
            'A ds:SignedInfo element must contain exactly one ds:CanonicalizationMethod',
            TooManyElementsException::class,
        );

        $signatureMethod = SignatureMethod::getChildrenOfClass($xml);
        Assert::minCount(
            $signatureMethod,
            1,
            'A ds:SignedInfo element must contain exactly one ds:SignatureMethod',
            MissingElementException::class,
        );
        Assert::maxCount(
            $signatureMethod,
            1,
            'A ds:SignedInfo element must contain exactly one ds:SignatureMethod',
            TooManyElementsException::class,
        );

        $references = Reference::getChildrenOfClass($xml);
        Assert::minCount(
            $references,
            1,
            'A ds:SignedInfo element must contain at least one ds:Reference',
            MissingElementException::class,
        );

        $signedInfo = new static(array_pop($canonicalizationMethod), array_pop($signatureMethod), $references, $Id);
        $signedInfo->xml = $xml;
        return $signedInfo;
    }


    /**
     * Convert this SignedInfo element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this SignedInfo element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        $this->getCanonicalizationMethod()->toXML($e);
        $this->getSignatureMethod()->toXML($e);

        foreach ($this->getReferences() as $ref) {
            $ref->toXML($e);
        }

        return $e;
    }
}
