<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ec;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;

use function explode;
use function join;

/**
 * Class implementing InclusiveNamespaces
 *
 * @package simplesamlphp/xml-security
 */
class InclusiveNamespaces extends AbstractEcElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize the InclusiveNamespaces element.
     *
     * @param string[] $prefixes
     */
    final public function __construct(
        protected array $prefixes,
    ) {
        Assert::maxCount($prefixes, C::UNBOUNDED_LIMIT);
        Assert::allString(
            $prefixes,
            'Can only add string InclusiveNamespaces prefixes.',
            InvalidArgumentException::class,
        );
        Assert::allRegex($prefixes, '/^[a-z0-9._\\-:]*$/i', SchemaViolationException::class); // xsd:NMTOKEN
    }


    /**
     * Get the prefixes specified by this element.
     *
     * @return string[]
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }


    /**
     * Convert XML into an InclusiveNamespaces element.
     *
     * @param \DOMElement $xml The XML element we should load.
     * @return static
     */
    public static function fromXML(DOMElement $xml): static
    {
        $prefixes = self::getOptionalAttribute($xml, 'PrefixList', '');

        return new static(array_filter(explode(' ', $prefixes)));
    }

    /**
     * Convert this InclusiveNamespaces to XML.
     *
     * @param \DOMElement|null $parent The element we should append this InclusiveNamespaces to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if (!empty($this->getPrefixes())) {
            $e->setAttribute('PrefixList', join(' ', $this->getPrefixes()));
        }

        return $e;
    }
}
