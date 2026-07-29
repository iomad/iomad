<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SerializableElementInterface;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\Constants as C;

/**
 * Abstract class representing a dsig11:FieldIDType
 *
 * @package simplesaml/xml-security
 */
abstract class AbstractFieldIDType extends AbstractDsig11Element
{
    // We use our own getter instead of the trait's one, so we prevent their use by marking them private
    use ExtendableElementTrait {
        getElements as private;
        setElements as private;
    }

    /** @var \SimpleSAML\XML\XsNamespace */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * Initialize a FieldIDType element.
     *
     * @param \SimpleSAML\XML\SerializableElementInterface $fieldId
     */
    public function __construct(
        protected Prime|TnB|PnB|GnB|SerializableElementInterface $fieldId,
    ) {
        /** @var \SimpleSAML\XML\AbstractElement|\SimpleSAML\XML\Chunk $fieldId */
        if (
            !($fieldId instanceof Prime
            || $fieldId instanceof TnB
            || $fieldId instanceof PnB
            || $fieldId instanceof GnB)
        ) {
            Assert::true(
                (($fieldId instanceof Chunk) ? $fieldId->getNamespaceURI() : $fieldId::getNameSpaceURI())
                !== C::NS_XDSIG11,
                'A <dsig11:FieldIDType> requires either a Prime, TnB, PnB, GnB or an element in namespace ##other',
                SchemaViolationException::class,
            );
        }
    }


    /**
     * Collect the value of the fieldId-property
     *
     * @return (\SimpleSAML\XMLSecurity\XML\dsig11\Prime|
     *         \SimpleSAML\XMLSecurity\XML\dsig11\TnB|
     *         \SimpleSAML\XMLSecurity\XML\dsig11\PnB|
     *         \SimpleSAML\XMLSecurity\XML\dsig11\GnB|
     *         \SimpleSAML\XML\SerializableElementInterface)
     */
    public function getFieldId(): Prime|TnB|PnB|GnB|SerializableElementInterface
    {
        return $this->fieldId;
    }


    /**
     * Convert this FieldIDType element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this FieldIDType element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getFieldId()->toXML($e);

        return $e;
    }
}
