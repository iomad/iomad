<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use DOMElement;
use SimpleSAML\XMLSecurity\Utils\XML;

/**
 * A trait implementing the CanonicalizableElementInterface.
 *
 * @package simplesamlphp/xml-security
 */
trait CanonicalizableElementTrait
{
    /**
     * This trait uses the php DOM extension. As such, it requires you to keep track (or produce) the DOMElement
     * necessary to perform the canonicalisation.
     *
     * Implement this method to return the DOMElement with the proper representation of this object. Whatever is
     * returned here will be used both to perform canonicalisation and to serialize the object, so that it can be
     * recovered later in its exact original state.
     *
     * @return \DOMElement
     */
    abstract protected function getOriginalXML(): DOMElement;


    /**
     * Get the canonical (string) representation of this object.
     *
     * Note that if this object was created using fromXML(), it might be necessary to keep the original DOM
     * representation of the object.
     *
     * @param string $method The canonicalization method to use.
     * @param string[]|null $xpaths An array of XPaths to filter the nodes by. Defaults to null (no filters).
     * @param string[]|null $prefixes An array of namespace prefixes to filter the nodes by. Defaults to null (no
     * filters).
     * @return string
     */
    public function canonicalize(string $method, ?array $xpaths = null, ?array $prefixes = null): string
    {
        return XML::canonicalizeData($this->getOriginalXML(), $method, $xpaths, $prefixes);
    }


    /**
     * Serialize this canonicalisable element.
     *
     * @return array{0: string} The serialized chunk.
     */
    public function __serialize(): array
    {
        $xml = $this->getOriginalXML();
        return [$xml->ownerDocument->saveXML($xml)];
    }
}
