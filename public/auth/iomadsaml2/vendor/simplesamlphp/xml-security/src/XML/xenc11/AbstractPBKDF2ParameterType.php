<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

use function array_pop;

/**
 * Class representing <xenc11:PBKDF2ParameterType>.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractPBKDF2ParameterType extends AbstractXenc11Element implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * PBKDF2ParameterType constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\Salt $salt
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\IterationCount $iterationCount
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\KeyLength $keyLength
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\PRF $prf
     */
    final public function __construct(
        protected Salt $salt,
        protected IterationCount $iterationCount,
        protected KeyLength $keyLength,
        protected PRF $prf,
    ) {
    }


    /**
     * Get the value of the $salt property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\Salt
     */
    public function getSalt(): Salt
    {
        return $this->salt;
    }


    /**
     * Get the value of the $iterationCount property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\IterationCount
     */
    public function getIterationCount(): IterationCount
    {
        return $this->iterationCount;
    }


    /**
     * Get the value of the $keyLength property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\KeyLength
     */
    public function getKeyLength(): KeyLength
    {
        return $this->keyLength;
    }


    /**
     * Get the value of the $prf property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\PRF
     */
    public function getPRF(): PRF
    {
        return $this->prf;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getLocalName(), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::getNamespaceURI(), InvalidDOMElementException::class);

        $salt = Salt::getChildrenOfClass($xml);
        Assert::minCount($salt, 1, MissingElementException::class);
        Assert::maxCount($salt, 1, TooManyElementsException::class);

        $iterationCount = IterationCount::getChildrenOfClass($xml);
        Assert::minCount($iterationCount, 1, MissingElementException::class);
        Assert::maxCount($iterationCount, 1, TooManyElementsException::class);

        $keyLength = KeyLength::getChildrenOfClass($xml);
        Assert::minCount($keyLength, 1, MissingElementException::class);
        Assert::maxCount($keyLength, 1, TooManyElementsException::class);

        $prf = PRF::getChildrenOfClass($xml);
        Assert::minCount($prf, 1, MissingElementException::class);
        Assert::maxCount($prf, 1, TooManyElementsException::class);

        return new static(
            array_pop($salt),
            array_pop($iterationCount),
            array_pop($keyLength),
            array_pop($prf),
        );
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getSalt()->toXML($e);
        $this->getIterationCount()->toXML($e);
        $this->getKeyLength()->toXML($e);
        $this->getPRF()->toXML($e);

        return $e;
    }
}
