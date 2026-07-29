<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:TnBFieldParamsType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractTnBFieldParamsType extends AbstractCharTwoFieldParamsType
{
    /**
     * Initialize a TnBFieldParamsType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\M $m
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\K $k
     */
    public function __construct(
        M $m,
        protected K $k,
    ) {
        parent::__construct($m);
    }


    /**
     * Collect the value of the k-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\K
     */
    public function getK(): K
    {
        return $this->k;
    }


    /**
     * Convert this TnBFieldParamsType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this TnBFieldParamsType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = parent::toXML($parent);
        $this->getK()->toXML($e);

        return $e;
    }
}
