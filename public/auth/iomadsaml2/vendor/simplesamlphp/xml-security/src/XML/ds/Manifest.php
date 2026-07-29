<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Assert\Assert;

/**
 * Class representing a ds:Manifest element.
 *
 * @package simplesamlphp/xml-security
 */
final class Manifest extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a ds:Manifest
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Reference[] $references
     * @param string|null $Id
     */
    public function __construct(
        protected array $references,
        protected ?string $Id = null,
    ) {
        Assert::maxCount($references, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($references, Reference::class);
        Assert::nullOrValidNCName($Id);
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\Reference[]
     */
    public function getReferences(): array
    {
        return $this->references;
    }


    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Convert XML into a Manifest element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Manifest', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Manifest::NS, InvalidDOMElementException::class);

        $Id = self::getOptionalAttribute($xml, 'Id', null);

        $references = Reference::getChildrenOfClass($xml);
        Assert::minCount(
            $references,
            1,
            'A <ds:Manifest> must contain at least one <ds:Reference>.',
            MissingElementException::class,
        );

        return new static(
            $references,
            $Id,
        );
    }


    /**
     * Convert this Manifest element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Manifest element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        foreach ($this->getReferences() as $reference) {
            $reference->toXML($e);
        }

        return $e;
    }
}
