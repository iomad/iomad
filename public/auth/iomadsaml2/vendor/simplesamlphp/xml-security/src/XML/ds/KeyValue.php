<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XML\SerializableElementInterface;
use SimpleSAML\XML\XsNamespace as NS;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\XML\dsig11\ECKeyValue;

use function array_merge;
use function array_pop;

/**
 * Class representing a ds:KeyValue element.
 *
 * @package simplesamlphp/xml-security
 */
final class KeyValue extends AbstractDsElement implements SchemaValidatableElementInterface
{
    // We use our own getter instead of the trait's one, so we prevent their use by marking them private
    use ExtendableElementTrait {
        getElements as private;
        setElements as private;
    }
    use SchemaValidatableElementTrait;


    /** The namespace-attribute for the xs:any element */
    public const XS_ANY_ELT_NAMESPACE = NS::OTHER;


    /**
     * Initialize an KeyValue.
     *
     * @param \SimpleSAML\XML\SerializableElementInterface $keyValue
     */
    final public function __construct(
        protected RSAKeyValue|DSAKeyValue|ECKeyValue|SerializableElementInterface $keyValue,
    ) {
        /** @var \SimpleSAML\XML\AbstractElement|\SimpleSAML\XML\Chunk $keyValue */
        if (
            !($keyValue instanceof RSAKeyValue
            || $keyValue instanceof DSAKeyValue
            || $keyValue instanceof ECKeyValue)
        ) {
            Assert::true(
                (($keyValue instanceof Chunk) ? $keyValue->getNamespaceURI() : $keyValue::getNameSpaceURI())
                !== C::NS_XDSIG,
                'A <ds:KeyValue> requires either a RSAKeyValue, DSAKeyValue, ECKeyValue '
                . 'or an element in namespace ##other',
                SchemaViolationException::class,
            );
        }
    }


    /**
     * Collect the value of the RSAKeyValue-property
     *
     * @return (\SimpleSAML\XMLSecurity\XML\ds\RSAKeyValue|
     *         \SimpleSAML\XMLSecurity\XML\ds\DSAKeyValue|
     *         \SimpleSAML\XMLSecurity\XML\dsig11\ECKeyValue|
     *         \SimpleSAML\XML\SerializableElementInterface)
     */
    public function getKeyValue(): RSAKeyValue|DSAKeyValue|ECKeyValue|SerializableElementInterface
    {
        return $this->keyValue;
    }


    /**
     * Convert XML into a KeyValue
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'KeyValue', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, KeyValue::NS, InvalidDOMElementException::class);

        $keyValue = array_merge(
            RSAKeyValue::getChildrenOfClass($xml),
            DSAKeyValue::getChildrenOfClass($xml),
            self::getChildElementsFromXML($xml),
        );

        Assert::count(
            $keyValue,
            1,
            'A <ds:KeyValue> must contain exactly one child element',
            TooManyElementsException::class,
        );

        return new static(array_pop($keyValue));
    }


    /**
     * Convert this KeyValue element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this KeyValue element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getKeyValue()->toXML($e);

        return $e;
    }
}
