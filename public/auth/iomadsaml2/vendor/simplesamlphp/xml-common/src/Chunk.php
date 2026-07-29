<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\XML\Assert\Assert;
use SimpleSAML\XML\Exception\MissingAttributeException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\SerializableElementTrait;

use function in_array;
use function intval;

/**
 * Serializable class used to hold an XML element.
 *
 * @package simplesamlphp/xml-common
 */
final class Chunk implements SerializableElementInterface
{
    use SerializableElementTrait;


    /**
     * The localName of the element.
     *
     * @var string
     */
    protected string $localName;

    /**
     * The namespaceURI of this element.
     *
     * @var string|null
     */
    protected ?string $namespaceURI;

    /**
     * The prefix of this element.
     *
     * @var string
     */
    protected string $prefix;


    /**
     * Create an XML Chunk from a copy of the given \DOMElement.
     *
     * @param \DOMElement $xml The element we should copy.
     */
    public function __construct(
        protected DOMElement $xml,
    ) {
        $this->setLocalName($xml->localName);
        $this->setNamespaceURI($xml->namespaceURI);
        $this->setPrefix($xml->prefix);
    }


    /**
     * Collect the value of the localName-property
     *
     * @return string
     */
    public function getLocalName(): string
    {
        return $this->localName;
    }


    /**
     * Set the value of the localName-property
     *
     * @param string $localName
     * @throws \SimpleSAML\Assert\AssertionFailedException if $localName is an empty string
     */
    public function setLocalName(string $localName): void
    {
        Assert::validNCName($localName, SchemaViolationException::class); // Covers the empty string
        $this->localName = $localName;
    }


    /**
     * Collect the value of the namespaceURI-property
     *
     * @return string|null
     */
    public function getNamespaceURI(): ?string
    {
        return $this->namespaceURI;
    }


    /**
     * Set the value of the namespaceURI-property
     *
     * @param string|null $namespaceURI
     */
    protected function setNamespaceURI(?string $namespaceURI = null): void
    {
        Assert::nullOrValidURI($namespaceURI, SchemaViolationException::class);
        $this->namespaceURI = $namespaceURI;
    }


    /**
     * Get this \DOMElement.
     *
     * @return \DOMElement This element.
     */
    public function getXML(): DOMElement
    {
        return $this->xml;
    }


    /**
     * Collect the value of the prefix-property
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }


    /**
     * Set the value of the prefix-property
     *
     * @param string|null $prefix
     */
    protected function setPrefix(?string $prefix = null): void
    {
        $this->prefix = strval($prefix);
    }


    /**
     * Get the XML qualified name (prefix:name, or just name when not prefixed)
     *  of the element represented by this class.
     *
     * @return string
     */
    public function getQualifiedName(): string
    {
        $prefix = $this->getPrefix();

        if (empty($prefix)) {
            return $this->getLocalName();
        } else {
            return $prefix . ':' . $this->getLocalName();
        }
    }


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return string
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     */
    public static function getAttribute(DOMElement $xml, string $name): string
    {
        Assert::true(
            $xml->hasAttribute($name),
            'Missing \'' . $name . '\' attribute on ' . $xml->prefix . ':' . $xml->localName . '.',
            MissingAttributeException::class,
        );

        return $xml->getAttribute($name);
    }


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param string|null $default The default to return in case the attribute does not exist and it is optional.
     * @return ($default is string ? string : null)
     */
    public static function getOptionalAttribute(DOMElement $xml, string $name, ?string $default = null): ?string
    {
        if (!$xml->hasAttribute($name)) {
            return $default;
        }

        return $xml->getAttribute($name);
    }


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return bool
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not a boolean
     */
    public static function getBooleanAttribute(DOMElement $xml, string $name): bool
    {
        $value = self::getAttribute($xml, $name);

        Assert::oneOf(
            $value,
            ['0', '1', 'false', 'true'],
            'The \'' . $name . '\' attribute of ' . $xml->prefix . ':' . $xml->localName . ' must be boolean.',
        );

        return in_array($value, ['1', 'true'], true);
    }


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param bool|null   $default The default to return in case the attribute does not exist and it is optional.
     * @return ($default is bool ? bool : null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not a boolean
     */
    public static function getOptionalBooleanAttribute(DOMElement $xml, string $name, ?bool $default = null): ?bool
    {
        if (!$xml->hasAttribute($name)) {
            return $default;
        }

        return self::getBooleanAttribute($xml, $name);
    }


    /**
     * Get the integer value of an attribute from a given element.
     *
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return int
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not an integer
     */
    public static function getIntegerAttribute(DOMElement $xml, string $name): int
    {
        $value = self::getAttribute($xml, $name);

        Assert::numeric(
            $value,
            'The \'' . $name . '\' attribute of ' . $xml->prefix . ':' . $xml->localName . ' must be numerical.',
        );

        return intval($value);
    }


    /**
     * Get the integer value of an attribute from a given element.
     *
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param int|null    $default The default to return in case the attribute does not exist and it is optional.
     * @return ($default is int ? int : null)
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not an integer
     */
    public static function getOptionalIntegerAttribute(DOMElement $xml, string $name, ?int $default = null): ?int
    {
        if (!$xml->hasAttribute($name)) {
            return $default;
        }

        return self::getIntegerAttribute($xml, $name);
    }


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool
    {
        /** @var \DOMElement $xml */
        $xml = $this->getXML();
        return ($xml->childNodes->length === 0) && ($xml->attributes->count() === 0);
    }


    /**
     * @param \DOMElement $xml
     * @return static
     */
    public static function fromXML(DOMElement $xml): static
    {
        return new static($xml);
    }


    /**
     * Append this XML element to a different XML element.
     *
     * @param  \DOMElement|null $parent The element we should append this element to.
     * @return \DOMElement The new element.
     */
    public function toXML(?DOMElement $parent = null): DOMElement
    {
        if ($parent === null) {
            $doc = DOMDocumentFactory::create();
        } else {
            $doc = $parent->ownerDocument;
            Assert::notNull($doc);
        }

        if ($parent === null) {
            $parent = $doc;
        }

        $parent->appendChild($doc->importNode($this->getXML(), true));

        return $doc->documentElement;
    }
}
