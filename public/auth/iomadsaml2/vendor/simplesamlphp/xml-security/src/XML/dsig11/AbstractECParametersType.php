<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;

/**
 * Abstract class representing a dsig11:ECParametersType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractECParametersType extends AbstractDsig11Element
{
    /**
     * Initialize a ECParametersType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\FieldID $fieldId
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\Curve $curve
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\Base $base
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\Order $order
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\CoFactor|null $coFactor
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\ValidationData|null $validationData
     */
    public function __construct(
        protected FieldID $fieldId,
        protected Curve $curve,
        protected Base $base,
        protected Order $order,
        protected ?CoFactor $coFactor = null,
        protected ?ValidationData $validationData = null,
    ) {
    }


    /**
     * Collect the value of the fieldId-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\FieldID
     */
    public function getFieldID(): FieldID
    {
        return $this->fieldId;
    }


    /**
     * Collect the value of the curve-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\Curve
     */
    public function getCurve(): Curve
    {
        return $this->curve;
    }


    /**
     * Collect the value of the base-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\Base
     */
    public function getBase(): Base
    {
        return $this->base;
    }


    /**
     * Collect the value of the order-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }


    /**
     * Collect the value of the coFactor-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\CoFactor|null
     */
    public function getCoFactor(): ?CoFactor
    {
        return $this->coFactor;
    }


    /**
     * Collect the value of the validationData-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\ValidationData|null
     */
    public function getValidationData(): ?ValidationData
    {
        return $this->validationData;
    }


    /**
     * Convert this ECParametersType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this ECParametersType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getFieldId()->toXML($e);
        $this->getCurve()->toXML($e);
        $this->getBase()->toXML($e);
        $this->getOrder()->toXML($e);
        $this->getCoFactor()?->toXML($e);
        $this->getValidationData()?->toXML($e);

        return $e;
    }
}
