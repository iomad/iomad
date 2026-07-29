<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;
use SimpleSAML\XML\DOMDocumentFactory;

use function array_pop;
use function get_object_vars;

/**
 * Trait grouping common functionality for elements implementing the SerializableElement element.
 *
 * @package simplesamlphp/xml-common
 */
trait SerializableElementTrait
{
    /**
     * Whether to format the string output of this element or not.
     *
     * Defaults to true. Override to disable output formatting.
     *
     * @var bool
     */
    protected bool $formatOutput = true;


    /**
     * Output the class as an XML-formatted string
     *
     * @return string
     */
    public function __toString(): string
    {
        $xml = $this->toXML();

        $xmlString = $xml->ownerDocument->saveXML();

        $doc = DOMDocumentFactory::fromString($xmlString);
        $doc->formatOutput = $this->formatOutput;

        return $doc->saveXML($doc->firstChild);
    }


    /**
     * Serialize this XML chunk.
     *
     * This method will be invoked by any calls to serialize().
     *
     * @return array{0: string} The serialized representation of this XML object.
     */
    public function __serialize(): array
    {
        $xml = $this->toXML();
        return [$xml->ownerDocument->saveXML($xml)];
    }


    /**
     * Unserialize an XML object and load it..
     *
     * This method will be invoked by any calls to unserialize(), allowing us to restore any data that might not
     * be serializable in its original form (e.g.: DOM objects).
     *
     * @param array{0: string} $serialized The XML object that we want to restore.
     */
    public function __unserialize(array $serialized): void
    {
        $xml = static::fromXML(
            DOMDocumentFactory::fromString(array_pop($serialized))->documentElement,
        );

        $vars = get_object_vars($xml);
        foreach ($vars as $k => $v) {
            $this->$k = $v;
        }
    }


    /**
     * Create XML from this class
     *
     * @param \DOMElement|null $parent
     * @return \DOMElement
     */
    abstract public function toXML(?DOMElement $parent = null): DOMElement;
}
