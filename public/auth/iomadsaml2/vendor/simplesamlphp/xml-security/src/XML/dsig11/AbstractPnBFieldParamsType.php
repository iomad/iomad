<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:PnBFieldParamsType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractPnBFieldParamsType extends AbstractCharTwoFieldParamsType
{
    /**
     * Initialize a PnBFieldParamsType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\M $m
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\K1 $k1
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\K2 $k2
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\K3 $k3
     */
    public function __construct(
        M $m,
        protected K1 $k1,
        protected K2 $k2,
        protected K3 $k3,
    ) {
        parent::__construct($m);
    }


    /**
     * Collect the value of the k1-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\K1
     */
    public function getK1(): K1
    {
        return $this->k1;
    }


    /**
     * Collect the value of the k2-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\K2
     */
    public function getK2(): K2
    {
        return $this->k2;
    }


    /**
     * Collect the value of the k3-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\K3
     */
    public function getK3(): K3
    {
        return $this->k3;
    }


    /**
     * Convert this PnBFieldParamsType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this PnBFieldParamsType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = parent::toXML($parent);
        $this->getK1()->toXML($e);
        $this->getK2()->toXML($e);
        $this->getK3()->toXML($e);

        return $e;
    }
}
