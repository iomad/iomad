<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Utils;

use DOMDocument;
use DOMNode;
use DOMXPath;
use SimpleSAML\Assert\Assert;

/**
 * XPath helper functions for the XML library.
 *
 * @package simplesamlphp/xml-common
 */
class XPath
{
    /**
     * Get an instance of DOMXPath associated with a DOMNode
     *
     * @param \DOMNode $node The associated node
     * @return \DOMXPath
     */
    public static function getXPath(DOMNode $node): DOMXPath
    {
        static $xpCache = null;

        if ($node instanceof DOMDocument) {
            $doc = $node;
        } else {
            $doc = $node->ownerDocument;
            Assert::notNull($doc);
        }

        if ($xpCache === null || !$xpCache->document->isSameNode($doc)) {
            $xpCache = new DOMXPath($doc);
        }

        return $xpCache;
    }

    /**
     * Do an XPath query on an XML node.
     *
     * @param \DOMNode $node  The XML node.
     * @param string $query The query.
     * @param \DOMXPath $xpCache The DOMXPath object
     * @return array<int<0, max>, \DOMNameSpaceNode|\DOMNode|null> Array with matching DOM nodes.
     */
    public static function xpQuery(DOMNode $node, string $query, DOMXPath $xpCache): array
    {
        $ret = [];

        $results = $xpCache->query($query, $node);
        for ($i = 0; $i < $results->length; $i++) {
            $ret[$i] = $results->item($i);
        }

        return $ret;
    }
}
