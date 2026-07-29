<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;

/**
 * interface class to be implemented by all the classes that represent a serializable XML element
 *
 * @package simplesamlphp/xml-common
 */
interface SerializableElementInterface extends ElementInterface
{
    /**
     * Output the class as an XML-formatted string
     *
     * @return string
     */
    public function __toString(): string;


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool;


    /**
     * Create a class from XML
     *
     * @param \DOMElement $xml
     * @return static
     */
    public static function fromXML(DOMElement $xml): static;


    /**
     * Create XML from this class
     *
     * @param \DOMElement|null $parent
     * @return \DOMElement
     */
    public function toXML(?DOMElement $parent = null): DOMElement;
}
