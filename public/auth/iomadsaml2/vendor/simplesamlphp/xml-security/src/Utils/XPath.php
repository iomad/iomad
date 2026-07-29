<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Utils;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use SimpleSAML\XML\Utils\XPath as XPathUtils;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\RuntimeException;

/**
 * Compilation of utilities for XPath.
 *
 * @package simplesamlphp/xml-security
 */
class XPath extends XPathUtils
{
    /**
     * Get a DOMXPath object that can be used to search for XMLDSIG elements.
     *
     * @param \DOMNode $node The document to associate to the DOMXPath object.
     *
     * @return \DOMXPath A DOMXPath object ready to use in the given document, with the XMLDSIG namespace already
     * registered.
     */
    public static function getXPath(DOMNode $node): DOMXPath
    {
        $xp = parent::getXPath($node);
        $xp->registerNamespace('ds', C::NS_XDSIG);
        $xp->registerNamespace('xenc', C::NS_XENC);
        return $xp;
    }


    /**
     * Search for an element with a certain name among the children of a reference element.
     *
     * @param \DOMNode $ref The DOMDocument or DOMElement where encrypted data is expected to be found as a child.
     * @param string $name The name (possibly prefixed) of the element we are looking for.
     *
     * @return \DOMElement|false The element we are looking for, or false when not found.
     *
     * @throws RuntimeException If no DOM document is available.
     */
    public static function findElement(DOMNode $ref, string $name): DOMElement|false
    {
        $doc = $ref instanceof DOMDocument ? $ref : $ref->ownerDocument;
        if ($doc === null) {
            throw new RuntimeException('Cannot search, no DOM document available');
        }

        $nodeset = self::getXPath($doc)->query('./' . $name, $ref);

        return $nodeset->item(0) ?? false;
    }
}
