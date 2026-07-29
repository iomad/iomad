<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:CurveType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractCurveType extends AbstractDsig11Element
{
    /**
     * Initialize a CurveType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\A $a
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\B $b
     */
    public function __construct(
        protected A $a,
        protected B $b,
    ) {
    }


    /**
     * Collect the value of the a-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\A
     */
    public function getA(): A
    {
        return $this->a;
    }


    /**
     * Collect the value of the b-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\B
     */
    public function getB(): B
    {
        return $this->b;
    }


    /**
     * Convert this CurveType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this CurveType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getA()->toXML($e);
        $this->getB()->toXML($e);

        return $e;
    }
}
