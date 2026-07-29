<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

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
 * A class implementing the ds:AbstractDSAKeyValueType element.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractDSAKeyValueType extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * DSAKeyValueType constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Y $y
     * @param \SimpleSAML\XMLSecurity\XML\ds\G|null $g
     * @param \SimpleSAML\XMLSecurity\XML\ds\J|null $j
     * @param \SimpleSAML\XMLSecurity\XML\ds\P|null $p
     * @param \SimpleSAML\XMLSecurity\XML\ds\Q|null $q
     * @param \SimpleSAML\XMLSecurity\XML\ds\Seed|null $seed
     * @param \SimpleSAML\XMLSecurity\XML\ds\PgenCounter|null $pgenCounter
     */
    final public function __construct(
        protected Y $y,
        protected ?G $g = null,
        protected ?J $j = null,
        protected ?P $p = null,
        protected ?Q $q = null,
        protected ?Seed $seed = null,
        protected ?PgenCounter $pgenCounter = null,
    ) {
        if ($p !== null || $q !== null) {
            Assert::allNotNull([$p, $q], SchemaViolationException::class);
        } else {
            Assert::allNull([$p, $q], SchemaViolationException::class);
        }

        if ($seed !== null || $pgenCounter !== null) {
            Assert::allNotNull([$seed, $pgenCounter], SchemaViolationException::class);
        } else {
            Assert::allNull([$seed, $pgenCounter], SchemaViolationException::class);
        }
    }


    /**
     * Get the Y.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Y
     */
    public function getY(): Y
    {
        return $this->y;
    }


    /**
     * Get the G.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\G|null
     */
    public function getG(): ?G
    {
        return $this->g;
    }


    /**
     * Get the J.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\J|null
     */
    public function getJ(): ?J
    {
        return $this->j;
    }


    /**
     * Get the P.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\P|null
     */
    public function getP(): ?P
    {
        return $this->p;
    }


    /**
     * Get the Q.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Q|null
     */
    public function getQ(): ?Q
    {
        return $this->q;
    }


    /**
     * Get the Seed.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\Seed|null
     */
    public function getSeed(): ?Seed
    {
        return $this->seed;
    }


    /**
     * Get the PgenCounter.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\PgenCounter|null
     */
    public function getPgenCounter(): ?PgenCounter
    {
        return $this->pgenCounter;
    }


    /**
     * Initialize an DSAKeyValue object from an existing XML.
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
        Assert::same($xml->localName, 'DSAKeyValue', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $y = Y::getChildrenOfClass($xml);
        Assert::minCount($y, 1, MissingElementException::class);
        Assert::maxCount($y, 1, TooManyElementsException::class);

        $g = G::getChildrenOfClass($xml);
        Assert::maxCount($g, 1, TooManyElementsException::class);

        $j = J::getChildrenOfClass($xml);
        Assert::maxCount($j, 1, TooManyElementsException::class);

        $p = P::getChildrenOfClass($xml);
        Assert::maxCount($p, 1, TooManyElementsException::class);

        $q = Q::getChildrenOfClass($xml);
        Assert::maxCount($q, 1, TooManyElementsException::class);

        $seed = Seed::getChildrenOfClass($xml);
        Assert::maxCount($seed, 1, TooManyElementsException::class);

        $pgenCounter = PgenCounter::getChildrenOfClass($xml);
        Assert::maxCount($pgenCounter, 1, TooManyElementsException::class);

        return new static(
            array_pop($y),
            array_pop($g),
            array_pop($j),
            array_pop($p),
            array_pop($q),
            array_pop($seed),
            array_pop($pgenCounter),
        );
    }


    /**
     * Convert this DSAKeyValue object to XML.
     *
     * @param \DOMElement|null $parent The element we should append this DSAKeyValue to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $this->getP()?->toXML($e);
        $this->getQ()?->toXML($e);
        $this->getG()?->toXML($e);
        $this->getY()->toXML($e);
        $this->getJ()?->toXML($e);
        $this->getSeed()?->toXML($e);
        $this->getPgenCounter()?->toXML($e);

        return $e;
    }
}
