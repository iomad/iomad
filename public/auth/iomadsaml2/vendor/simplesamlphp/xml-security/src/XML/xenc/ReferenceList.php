<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;

use function array_merge;

/**
 * A class containing a list of references to either encrypted data or encryption keys.
 *
 * @package simplesamlphp/xml-security
 */
class ReferenceList extends AbstractXencElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * ReferenceList constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\DataReference[] $dataReferences
     * @param \SimpleSAML\XMLSecurity\XML\xenc\KeyReference[] $keyReferences
     */
    final public function __construct(
        protected array $dataReferences,
        protected array $keyReferences = [],
    ) {
        Assert::maxCount($dataReferences, C::UNBOUNDED_LIMIT);
        Assert::maxCount($keyReferences, C::UNBOUNDED_LIMIT);
        Assert::minCount(
            array_merge($dataReferences, $keyReferences),
            1,
            'At least one <xenc:DataReference> or <xenc:KeyReference> element required in <xenc:ReferenceList>.',
            MissingElementException::class,
        );

        Assert::allIsInstanceOf(
            $dataReferences,
            DataReference::class,
            'All data references must be an instance of <xenc:DataReference>.',
            InvalidArgumentException::class,
        );

        Assert::allIsInstanceOf(
            $keyReferences,
            KeyReference::class,
            'All key references must be an instance of <xenc:KeyReference>.',
            InvalidArgumentException::class,
        );
    }


    /**
     * Get the list of DataReference objects.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\DataReference[]
     */
    public function getDataReferences(): array
    {
        return $this->dataReferences;
    }


    /**
     * Get the list of KeyReference objects.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\KeyReference[]
     */
    public function getKeyReferences(): array
    {
        return $this->keyReferences;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'ReferenceList', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, ReferenceList::NS, InvalidDOMElementException::class);

        $dataReferences = DataReference::getChildrenOfClass($xml);
        $keyReferences = KeyReference::getChildrenOfClass($xml);

        return new static($dataReferences, $keyReferences);
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        foreach ($this->getDataReferences() as $dref) {
            $dref->toXML($e);
        }

        foreach ($this->getKeyReferences() as $kref) {
            $kref->toXML($e);
        }

        return $e;
    }
}
