<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;

use function array_pop;

/**
 * Class representing a ds:Reference element.
 *
 * @package simplesamlphp/xml-security
 */
final class Reference extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a ds:Reference
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestMethod $digestMethod
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestValue $digestValue
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms|null $transforms
     * @param string|null $Id
     * @param string|null $Type
     * @param string|null $URI
     */
    public function __construct(
        protected DigestMethod $digestMethod,
        protected DigestValue $digestValue,
        protected ?Transforms $transforms = null,
        protected ?string $Id = null,
        protected ?string $Type = null,
        protected ?string $URI = null,
    ) {
        Assert::nullOrValidNCName($Id);
        Assert::nullOrValidURI($Type);
        Assert::nullOrValidURI($URI);
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\Transforms|null
     */
    public function getTransforms(): ?Transforms
    {
        return $this->transforms;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\DigestMethod
     */
    public function getDigestMethod(): DigestMethod
    {
        return $this->digestMethod;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\DigestValue
     */
    public function getDigestValue(): DigestValue
    {
        return $this->digestValue;
    }


    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->Type;
    }


    /**
     * @return string|null
     */
    public function getURI(): ?string
    {
        return $this->URI;
    }


    /**
     * Determine whether this is an xpointer reference.
     *
     * @return bool
     */
    public function isXPointer(): bool
    {
        return !empty($this->URI) && str_starts_with($this->URI, '#xpointer');
    }


    /**
     * Convert XML into a Reference element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Reference', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Reference::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);
        $Type = self::getOptionalAttribute($xml, 'Type', null);
        $URI = self::getOptionalAttribute($xml, 'URI', null);

        $transforms = Transforms::getChildrenOfClass($xml);
        Assert::maxCount(
            $transforms,
            1,
            'A <ds:Reference> may contain just one <ds:Transforms>.',
            TooManyElementsException::class,
        );

        $digestMethod = DigestMethod::getChildrenOfClass($xml);
        Assert::count(
            $digestMethod,
            1,
            'A <ds:Reference> must contain a <ds:DigestMethod>.',
            MissingElementException::class,
        );

        $digestValue = DigestValue::getChildrenOfClass($xml);
        Assert::count(
            $digestValue,
            1,
            'A <ds:Reference> must contain a <ds:DigestValue>.',
            MissingElementException::class,
        );

        return new static(
            array_pop($digestMethod),
            array_pop($digestValue),
            empty($transforms) ? null : array_pop($transforms),
            $Id,
            $Type,
            $URI,
        );
    }


    /**
     * Convert this Reference element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Reference element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }
        if ($this->getType() !== null) {
            $e->setAttribute('Type', $this->getType());
        }
        if ($this->getURI() !== null) {
            $e->setAttribute('URI', $this->getURI());
        }

        $this->getTransforms()?->toXML($e);
        $this->getDigestMethod()->toXML($e);
        $this->getDigestValue()->toXML($e);

        return $e;
    }
}
