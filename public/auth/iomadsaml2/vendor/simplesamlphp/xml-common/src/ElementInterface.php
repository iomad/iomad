<?php

declare(strict_types=1);

namespace SimpleSAML\XML;

use DOMElement;

/**
 * interface class to be implemented by all the classes that represent an XML element
 *
 * @package simplesamlphp/xml-common
 */
interface ElementInterface
{
    /**
     * Get the XML qualified name (prefix:name) of the element represented by this class.
     *
     * @return string
     */
    public function getQualifiedName(): string;


    /**
     * Get the value of an attribute from a given element.
     *
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return string
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     */
    public static function getAttribute(DOMElement $xml, string $name): string;


    /**
     * Get the value of an attribute from a given element.
     *
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param string|null $default The default to return in case the attribute does not exist and it is optional.
     * @return string|null
     */
    public static function getOptionalAttribute(DOMElement $xml, string $name, ?string $default = null): ?string;


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return bool
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not a boolean
     */
    public static function getBooleanAttribute(DOMElement $xml, string $name): bool;


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param bool|null   $default The default to return in case the attribute does not exist and it is optional.
     * @return bool|null
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not a boolean
     */
    public static function getOptionalBooleanAttribute(DOMElement $xml, string $name, ?bool $default = null): ?bool;


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @return int
     *
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException if the attribute is missing from the element
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not an integer
     */
    public static function getIntegerAttribute(DOMElement $xml, string $name): int;


    /**
     * @param \DOMElement $xml The element where we should search for the attribute.
     * @param string      $name The name of the attribute.
     * @param int|null    $default The default to return in case the attribute does not exist and it is optional.
     * @return int|null
     *
     * @throws \SimpleSAML\Assert\AssertionFailedException if the attribute is not an integer
     */
    public static function getOptionalIntegerAttribute(DOMElement $xml, string $name, ?int $default = null): ?int;
}
