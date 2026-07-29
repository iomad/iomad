<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:CharTwoFieldParamsType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractCharTwoFieldParamsType extends AbstractDsig11Element
{
    /**
     * Initialize a CharTwoFieldParamsType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\M $m
     */
    public function __construct(
        protected M $m,
    ) {
    }


    /**
     * Collect the value of the m-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\M
     */
    public function getM(): M
    {
        return $this->m;
    }


    /**
     * Convert this CharTwoFieldParamsType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this CharTwoFieldParamsType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $this->getM()->toXML($e);

        return $e;
    }
}
