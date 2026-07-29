<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;

/**
 * Abstract class representing a dsig11:NamedCurveType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractNamedCurveType extends AbstractDsig11Element
{
    /**
     * Initialize a NamedCurveType element.
     *
     * @param string $URI
     */
    public function __construct(
        protected string $URI,
    ) {
        Assert::validURI($URI, SchemaViolationException::class);
    }


    /**
     * Collect the value of the URI-property
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->URI;
    }


    /**
     * Convert this NamedCurveType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this NamedCurveType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getURI());

        return $e;
    }
}
