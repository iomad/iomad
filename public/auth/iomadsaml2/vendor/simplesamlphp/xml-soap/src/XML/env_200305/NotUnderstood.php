<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Attribute as XMLAttribute;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

use function preg_split;

/**
 * Class representing a env:NotUnderstood element.
 *
 * @package simplesaml/xml-soap
 */
final class NotUnderstood extends AbstractSoapElement implements SchemaValidatableElementInterface
{
    use SchemaValidatableElementTrait;

    /**
     * Initialize a env:NotUnderstood
     *
     * @param string $qname
     * @param string|null $namespaceUri
     */
    public function __construct(
        protected string $qname,
        protected ?string $namespaceUri = null,
    ) {
        Assert::validQName($qname);
        Assert::nullOrValidURI($namespaceUri, SchemaViolationException::class);
    }


    /**
     * @return string
     */
    public function getQName(): string
    {
        return $this->qname;
    }


    /**
     * Get the namespace URI.
     *
     * @return string|null
     */
    public function getContentNamespaceUri(): ?string
    {
        return $this->namespaceUri;
    }



    /**
     * Splits a QName into an array holding the prefix (or null if no prefix is available) and the localName
     *
     * @param string $qName  The qualified name
     * @psalm-return array{null|string, string}
     * @return array
     */
    private static function parseQName(string $qName): array
    {
        Assert::validQName($qName);

        @list($prefix, $localName) = preg_split('/:/', $qName, 2);
        /** @var string|null $localName */
        if ($localName === null) {
            $prefix = null;
            $localName = $qName;
        }

        Assert::nullOrValidNCName($prefix);
        Assert::validNCName($localName);

        return [$prefix, $localName];
    }


    /**
     * Convert XML into a NotUnderstood
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'NotUnderstood', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, NotUnderstood::NS, InvalidDOMElementException::class);

        $qname = self::getAttribute($xml, 'qname');

        list($prefix, $localName) = self::parseQName($qname);
        /** @psalm-suppress PossiblyNullArgument */
        $namespace = $xml->lookupNamespaceUri($prefix);

        return new static($qname, $namespace);
    }



    /**
     * Convert this element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this element to.
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        list($prefix, $localName) = self::parseQName($this->getQName());
        $namespaceUri = $this->getContentNamespaceUri();
        if ($namespaceUri !== null && $prefix !== null) {
            /** @phpstan-ignore-next-line */
            if ($e->lookupNamespaceUri($prefix) === null && $e->lookupPrefix($namespaceUri) === null) {
                // The namespace is not yet available in the document - insert it
//$attr = $e->createAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns');
//$attr->value = $namespaceUri;
                //$e->setAttributeNS(C::NS_XS, 'xmlns:' . $prefix, $namespaceUri);
                $attr = new XMLAttribute('http://www.w3.org/2000/xmlns/', 'xmlns', $prefix, $namespaceUri);
                $attr->toXML($e);
            }
        }

        $e->setAttribute('qname', ($prefix === null) ? $localName : ($prefix . ':' . $localName));

        return $e;
    }
}
