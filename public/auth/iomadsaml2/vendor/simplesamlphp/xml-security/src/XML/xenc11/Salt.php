<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function array_merge;
use function array_pop;

/**
 * Class representing <xenc11:Salt>.
 *
 * @package simplesamlphp/xml-security
 */
final class Salt extends AbstractXenc11Element
{
    /**
     * Salt constructor.
     *
     * @param \SimpleSAML\XMLSecurity\XML\xenc11\OtherSource|\SimpleSAML\XMLSecurity\XML\xenc11\Specified $content
     */
    public function __construct(
        protected OtherSource|Specified $content,
    ) {
    }


    /**
     * Get the value of the $content property.
     *
     * @return \SimpleSAML\XMLSecurity\XML\xenc11\OtherSource|\SimpleSAML\XMLSecurity\XML\xenc11\Specified
     */
    public function getContent(): OtherSource|Specified
    {
        return $this->content;
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

        $otherSource = OtherSource::getChildrenOfClass($xml);
        $specified = Specified::getChildrenOfClass($xml);

        $content = array_merge($otherSource, $specified);
        Assert::minCount($content, 1, MissingElementException::class);
        Assert::maxCount($content, 1, TooManyElementsException::class);

        return new static(array_pop($content));
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $this->getContent()->toXML($e);

        return $e;
    }
}
