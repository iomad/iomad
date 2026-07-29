<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

use function array_pop;

/**
 * A class implementing the xenc:AbstractDHKeyValueType element.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractDHKeyValueType extends AbstractXencElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * DHKeyValueType constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc\XencPublic $xencPublic
     * @param \SimpleSAML\XMLSecurity\XML\xenc\P|null $p
     * @param \SimpleSAML\XMLSecurity\XML\xenc\Q|null $q
     * @param \SimpleSAML\XMLSecurity\XML\xenc\Generator|null $generator
     * @param \SimpleSAML\XMLSecurity\XML\xenc\Seed|null $seed
     * @param \SimpleSAML\XMLSecurity\XML\xenc\PgenCounter|null $pgenCounter
     */
    final public function __construct(
        protected XencPublic $xencPublic,
        protected ?P $p = null,
        protected ?Q $q = null,
        protected ?Generator $generator = null,
        protected ?Seed $seed = null,
        protected ?PgenCounter $pgenCounter = null,
    ) {
        if ($p !== null || $q !== null || $generator !== null) {
            Assert::allNotNull([$p, $q, $generator], SchemaViolationException::class);
        } else {
            Assert::allNull([$p, $q, $generator], SchemaViolationException::class);
        }

        if ($seed !== null || $pgenCounter !== null) {
            Assert::allNotNull([$seed, $pgenCounter], SchemaViolationException::class);
        } else {
            Assert::allNull([$seed, $pgenCounter], SchemaViolationException::class);
        }
    }


    /**
     * Get the Public.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\XencPublic
     */
    public function getPublic(): XencPublic
    {
        return $this->xencPublic;
    }


    /**
     * Get the P.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\P|null
     */
    public function getP(): ?P
    {
        return $this->p;
    }


    /**
     * Get the Q.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\Q|null
     */
    public function getQ(): ?Q
    {
        return $this->q;
    }


    /**
     * Get the Generator.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\Generator|null
     */
    public function getGenerator(): ?Generator
    {
        return $this->generator;
    }


    /**
     * Get the Seed.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\Seed|null
     */
    public function getSeed(): ?Seed
    {
        return $this->seed;
    }


    /**
     * Get the PgenCounter.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc\PgenCounter|null
     */
    public function getPgenCounter(): ?PgenCounter
    {
        return $this->pgenCounter;
    }


    /**
     * Initialize an DHKeyValue object from an existing XML.
     *
     * @param \DOMElement $xml
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException
     *   if the supplied element is missing one of the mandatory attributes
     * @throws \SimpleSAML\XML\Exception\TooManyElementsException
     *   if too many child-elements of a type are specified
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'DHKeyValue', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $xencPublic = XencPublic::getChildrenOfClass($xml);
        Assert::minCount($xencPublic, 1, MissingElementException::class);
        Assert::maxCount($xencPublic, 1, TooManyElementsException::class);

        $p = P::getChildrenOfClass($xml);
        Assert::maxCount($p, 1, TooManyElementsException::class);

        $q = Q::getChildrenOfClass($xml);
        Assert::maxCount($q, 1, TooManyElementsException::class);

        $generator = Generator::getChildrenOfClass($xml);
        Assert::maxCount($generator, 1, TooManyElementsException::class);

        $seed = Seed::getChildrenOfClass($xml);
        Assert::maxCount($seed, 1, TooManyElementsException::class);

        $pgenCounter = PgenCounter::getChildrenOfClass($xml);
        Assert::maxCount($pgenCounter, 1, TooManyElementsException::class);

        return new static(
            array_pop($xencPublic),
            array_pop($p),
            array_pop($q),
            array_pop($generator),
            array_pop($seed),
            array_pop($pgenCounter),
        );
    }


    /**
     * Convert this DHKeyValue object to XML.
     *
     * @param \DOMElement|null $parent The element we should append this DHKeyValue to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getP()?->toXML($e);
        $this->getQ()?->toXML($e);
        $this->getGenerator()?->toXML($e);
        $this->getPublic()->toXML($e);
        $this->getSeed()?->toXML($e);
        $this->getPgenCounter()?->toXML($e);

        return $e;
    }
}
