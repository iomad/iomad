<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Exception\SchemaViolationException;

/**
 * Abstract class representing a dsig11:ECKeyValueType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractECKeyValueType extends AbstractDsig11Element
{
    /**
     * Initialize a FieldIDType element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\PublicKey $publicKey
     * @param string|null $id
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\ECParameters|null $ecParameters
     * @param \SimpleSAML\XMLSecurity\XML\dsig11\NamedCurve|null $namedCurve
     */
    public function __construct(
        protected PublicKey $publicKey,
        protected ?string $id = null,
        protected ?ECParameters $ecParameters = null,
        protected ?NamedCurve $namedCurve = null,
    ) {
        Assert::validNCName($id, SchemaViolationException::class);
        Assert::oneOf(
            null,
            [$ecParameters, $namedCurve],
            'The ECParameters and NamedCurve are mutually exclusive; please specify one or the other.',
            SchemaViolationException::class,
        );
    }


    /**
     * Collect the value of the ecParameters-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\ECParameters|null
     */
    public function getECParameters(): ?ECParameters
    {
        return $this->ecParameters;
    }


    /**
     * Collect the value of the namedCurve-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\NamedCurve|null
     */
    public function getNamedCurve(): ?NamedCurve
    {
        return $this->namedCurve;
    }


    /**
     * Collect the value of the publicKey-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\dsig11\PublicKey
     */
    public function getPublicKey(): PublicKey
    {
        return $this->publicKey;
    }


    /**
     * Collect the value of the id-property
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    /**
     * Convert this ECKeyValueType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this ECKeyValueType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        $this->getECParameters()?->toXML($e);
        $this->getNamedCurve()?->toXML($e);
        $this->getPublicKey()->toXML($e);

        return $e;
    }
}
