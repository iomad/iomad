<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200305;

use SimpleSAML\XML\QNameElementTrait;

/**
 * Class representing a env:Value element.
 *
 * @package simplesaml/xml-soap
 */
final class Value extends AbstractSoapElement
{
    use QNameElementTrait;


    /**
     * Initialize a env:Value
     *
     * @param string $qname
     * @param string|null $namespaceUri
     */
    public function __construct(string $qname, ?string $namespaceUri = null)
    {
        $this->setContent($qname);
        $this->setContentNamespaceUri($namespaceUri);
    }
}
