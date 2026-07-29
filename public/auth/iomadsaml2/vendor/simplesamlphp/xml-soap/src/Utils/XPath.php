<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\Utils;

use DOMNode;
use DOMXPath;
use SimpleSAML\SOAP\Constants as C;

/**
 * Compilation of utilities for XPath.
 *
 * @package simplesamlphp/xml-soap
 */
class XPath extends \SimpleSAML\XML\Utils\XPath
{
    /**
     * Get a DOMXPath object that can be used to search for SAML elements.
     *
     * @param \DOMNode $node The document to associate to the DOMXPath object.
     *
     * @return \DOMXPath A DOMXPath object ready to use in the given document, with several
     *   saml-related namespaces already registered.
     */
    public static function getXPath(DOMNode $node): DOMXPath
    {
        $xp = parent::getXPath($node);
        $xp->registerNamespace('env11', C::NS_SOAP_ENV_11);
        $xp->registerNamespace('enc11', C::NS_SOAP_ENC_11);
        $xp->registerNamespace('env12', C::NS_SOAP_ENV_12);
        $xp->registerNamespace('enc12', C::NS_SOAP_ENC_12);

        return $xp;
    }
}
