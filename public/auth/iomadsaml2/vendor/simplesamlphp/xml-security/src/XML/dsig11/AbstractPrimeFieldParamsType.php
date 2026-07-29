<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:PrimeFieldParamsType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractPrimeFieldParamsType extends AbstractDsig11Element
{
    /**
     * Initialize a PrimeFieldParamsType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\P $p
     */
    public function __construct(
        protected P $p,
    ) {
    }


    /**
     * Collect the value of the p-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\P
     */
    public function getP(): P
    {
        return $this->p;
    }


    /**
     * Convert this PrimeFieldParamsType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this PrimeFieldParamsType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $this->getP()->toXML($e);

        return $e;
    }
}
