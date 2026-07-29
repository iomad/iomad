<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;
use SimpleSAML\XMLSecurity\XML\xenc\Transforms;

/**
 * Class representing a CipherReference.
 *
 * @package simplesamlphp/xml-security
 */
final class CipherReference extends AbstractXencElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * AbstractReference constructor.
     *
     * @param string $uri
     * @param \SimpleSAML\XMLSecurity\XML\xenc\Transforms[] $transforms
     */
    final public function __construct(
        protected string $uri,
        protected array $transforms = [],
    ) {
        Assert::validURI($uri, SchemaViolationException::class); // Covers the empty string
        Assert::maxCount($transforms, C::UNBOUNDED_LIMIT);
        Assert::allIsInstanceOf($transforms, Transforms::class, SchemaViolationException::class);
    }


    /**
     * Get the value of the URI attribute of this reference.
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->uri;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException
     *   if the supplied element is missing one of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getClassName(static::class), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        $URI = self::getAttribute($xml, 'URI');
        $transforms = Transforms::getChildrenOfClass($xml);

        return new static($URI, $transforms);
    }


    /**
     * @inheritDoc
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getUri());

        foreach ($this->transforms as $transforms) {
            $transforms->toXML($e);
        }

        return $e;
    }
}
