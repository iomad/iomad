<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML;

use SimpleSAML\XML\ElementInterface;

/**
 * An interface for objects that can be canonicalized.
 *
 * Objects implementing this interface should retain the original XML structure that was used to create them with
 * fromXML(), in order to guarantee that the original canonicalization of the object is the same as the one produced
 * by this interface.
 *
 * If the original DOM object is retained, please remember to implement the serialization magic methods so that the
 * result of serialisation is the original string representation of the object, since DOM objects are not serializable.
 *
 * @package simplesamlphp/xml-security
 */
interface CanonicalizableElementInterface extends ElementInterface
{
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
    public function canonicalize(string $method, ?array $xpaths = null, ?array $prefixes = null): string;
}
