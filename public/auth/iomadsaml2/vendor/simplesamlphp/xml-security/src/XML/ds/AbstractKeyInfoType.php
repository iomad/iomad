<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SerializableElementInterface;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\Assert\Assert;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\XML\dsig11\AbstractDsig11Element;
use SimpleSAML\XMLSecurity\XML\dsig11\DEREncodedKeyValue;

/**
 * Abstract class representing the KeyInfoType.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractKeyInfoType extends AbstractDsElement
{
    use ExtendableElementTrait;

    /** @var \SimpleSAML\XML\XsNamespace */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * Initialize a KeyInfo element.
     *
     * @param (
     *     \SimpleSAML\XMLSecurity\XML\ds\KeyName|
     *     \SimpleSAML\XMLSecurity\XML\ds\KeyValue|
     *     \SimpleSAML\XMLSecurity\XML\ds\RetrievalMethod|
     *     \SimpleSAML\XMLSecurity\XML\ds\X509Data|
     *     \SimpleSAML\XMLSecurity\XML\ds\PGPData|
     *     \SimpleSAML\XMLSecurity\XML\ds\SPKIData|
     *     \SimpleSAML\XMLSecurity\XML\ds\MgmtData|
     *     \SimpleSAML\XMLSecurity\XML\dsig11\DEREncodedKeyValue|
     *     \SimpleSAML\XML\SerializableElementInterface
     * )[] $info
     * @param string|null $Id
     */
    final public function __construct(
        protected array $info,
        protected ?string $Id = null,
    ) {
        Assert::notEmpty(
            $info,
            sprintf(
                '%s:%s cannot be empty',
                static::getNamespacePrefix(),
                static::getLocalName(),
            ),
            InvalidArgumentException::class,
        );
        Assert::maxCount($info, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf(
            $info,
            SerializableElementInterface::class,
            InvalidArgumentException::class,
        );
        Assert::nullOrValidNCName($Id);

        foreach ($info as $item) {
            if ($item instanceof AbstractDsElement) {
                Assert::isInstanceOfAny(
                    $item,
                    [
                        KeyName::class,
                        KeyValue::class,
                        RetrievalMethod::class,
                        X509Data::class,
                        PGPData::class,
                        SPKIData::class,
                        MgmtData::class,
                    ],
                    SchemaViolationException::class,
                );
            } elseif ($item instanceof AbstractDsig11Element) {
                Assert::isInstanceOfAny(
                    $item,
                    [
                        DEREncodedKeyValue::class,
                    ],
                    SchemaViolationException::class,
                );
            }
        }
    }


    /**
     * Collect the value of the Id-property
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Collect the value of the info-property
     *
     * @return list<\SimpleSAML\XML\SerializableElementInterface>
     */
    public function getInfo(): array
    {
        return $this->info;
    }


    /**
     * Convert this KeyInfo to XML.
     *
     * @param \DOMElement|null $parent The element we should append this KeyInfo to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        foreach ($this->getInfo() as $elt) {
            $elt->toXML($e);
        }

        return $e;
    }
}
