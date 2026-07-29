<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Utils;

use DOMElement;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\Exception\CanonicalizationFailedException;
use SimpleSAML\XMLSecurity\XML\ds\Transforms;

use function count;
use function is_null;

/**
 * Class with utility methods for XML manipulation.
 *
 * @package simplesamlphp/xml-security
 */
class XML
{
    /**
     * Canonicalize any given node.
     *
     * @param \DOMElement $element The DOM element that needs canonicalization.
     * @param string $c14nMethod The identifier of the canonicalization algorithm to use.
     *   See \SimpleSAML\XMLSecurity\Constants.
     * @param string[]|null $xpaths An array of xpaths to filter the nodes by. Defaults to null (no filters).
     * @param string[]|null $prefixes An array of namespace prefixes to filter the nodes by.
     *   Defaults to null (no filters).
     *
     * @return string The canonical representation of the given DOM node, according to the algorithm requested.
     */
    public static function canonicalizeData(
        DOMElement $element,
        string $c14nMethod,
        ?array $xpaths = null,
        ?array $prefixes = null,
    ): string {
        $withComments = match ($c14nMethod) {
            C::C14N_EXCLUSIVE_WITH_COMMENTS, C::C14N_INCLUSIVE_WITH_COMMENTS => true,
            default => false,
        };
        $exclusive = match ($c14nMethod) {
            C::C14N_EXCLUSIVE_WITH_COMMENTS, C::C14N_EXCLUSIVE_WITHOUT_COMMENTS => true,
            default => false,
        };

        if (
            is_null($xpaths)
            && ($element->ownerDocument !== null)
            && ($element->ownerDocument->documentElement !== null)
            && $element->isSameNode($element->ownerDocument->documentElement)
        ) {
            // check for any PI or comments as they would have been excluded
            $current = $element;
            for ($refNode = $current->previousSibling; $refNode !== null; $current = $refNode) {
                if (
                    (($refNode->nodeType === XML_COMMENT_NODE) && $withComments)
                    || $refNode->nodeType === XML_PI_NODE
                ) {
                    break;
                }
            }

            if ($refNode === null) {
                $element = $element->ownerDocument;
            }
        }

        $ret = $element->C14N($exclusive, $withComments, $xpaths, $prefixes);
        if ($ret === false) {
            // GHSA-h25p-2wxc-6584
            throw new CanonicalizationFailedException();
        }
        return $ret;
    }


    /**
     * Process all transforms specified by a given Reference element.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms $transforms The transforms to apply.
     * @param \DOMElement $data The data referenced.
     *
     * @return string The canonicalized data after applying all transforms specified by $ref.
     *
     * @see http://www.w3.org/TR/xmldsig-core/#sec-ReferenceProcessingModel
     */
    public static function processTransforms(
        Transforms $transforms,
        DOMElement $data,
    ): string {
        $canonicalMethod = C::C14N_EXCLUSIVE_WITHOUT_COMMENTS;
        $arXPath = null;
        $prefixList = null;
        foreach ($transforms->getTransform() as $transform) {
            $canonicalMethod = $transform->getAlgorithm();
            switch ($canonicalMethod) {
                case C::C14N_EXCLUSIVE_WITHOUT_COMMENTS:
                case C::C14N_EXCLUSIVE_WITH_COMMENTS:
                    $inclusiveNamespaces = $transform->getInclusiveNamespaces();
                    if ($inclusiveNamespaces !== null) {
                        $prefixes = $inclusiveNamespaces->getPrefixes();
                        if (count($prefixes) > 0) {
                            $prefixList = $prefixes;
                        }
                    }
                    break;
                case C::XPATH10_URI:
                    $xpath = $transform->getXPath();
                    if ($xpath !== null) {
                        $arXPath = [];
                        $arXPath['query'] = '(.//. | .//@* | .//namespace::*)[' . $xpath->getExpression() . ']';
//                        $arXpath['namespaces'] = $xpath->getNamespaces();
                        // TODO: review if $nsnode->localName is equivalent to the keys in getNamespaces()
//                        $nslist = $xp->query('./namespace::*', $node);
//                        foreach ($nslist as $nsnode) {
//                            if ($nsnode->localName != "xml") {
//                                $arXPath['namespaces'][$nsnode->localName] = $nsnode->nodeValue;
//                            }
//                        }
                    }
                    break;
            }
        }

        return self::canonicalizeData($data, $canonicalMethod, $arXPath, $prefixList);
    }
}
