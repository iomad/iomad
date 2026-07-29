<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a ds:RSAKeyValue element.
 *
 * @package simplesamlphp/xml-security
 */
final class RSAKeyValue extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize an RSAKeyValue.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Modulus $modulus
     * @param \SimpleSAML\XMLSecurity\XML\ds\Exponent $exponent
     */
    final public function __construct(
        protected Modulus $modulus,
        protected Exponent $exponent,
    ) {
    }


    /**
     * Collect the value of the modulus-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Modulus
     */
    public function getModulus(): Modulus
    {
        return $this->modulus;
    }


    /**
     * Collect the value of the exponent-property
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Exponent
     */
    public function getExponent(): Exponent
    {
        return $this->exponent;
    }


    /**
     * Convert XML into a RSAKeyValue
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'RSAKeyValue', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, RSAKeyValue::NS, InvalidDOMElementException::class);

        $modulus = Modulus::getChildrenOfClass($xml);
        Assert::minCount(
            $modulus,
            1,
            'An <ds:RSAKeyValue> must contain exactly one <ds:Modulus>',
            MissingElementException::class,
        );
        Assert::maxCount(
            $modulus,
            1,
            'An <ds:RSAKeyValue> must contain exactly one <ds:Modulus>',
            TooManyElementsException::class,
        );

        $exponent = Exponent::getChildrenOfClass($xml);
        Assert::minCount(
            $exponent,
            1,
            'An <ds:RSAKeyValue> must contain exactly one <ds:Modulus>',
            MissingElementException::class,
        );
        Assert::maxCount(
            $exponent,
            1,
            'An <ds:RSAKeyValue> must contain exactly one <ds:Modulus>',
            TooManyElementsException::class,
        );

        return new static(array_pop($modulus), array_pop($exponent));
    }


    /**
     * Convert this RSAKeyValue element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this RSAKeyValue element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getModulus()->toXML($e);
        $this->getExponent()->toXML($e);

        return $e;
    }
}
