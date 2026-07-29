<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200106;

use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XML\QNameElementTrait;

/**
 * Class representing a faultcode element.
 *
 * @package simplesaml/xml-soap
 */
final class FaultCode extends AbstractElement
{
    use QNameElementTrait;

    /** @var string */
    public const LOCALNAME = 'faultcode';

    /** @var null */
    public const NS = null;

    /** @var null */
    public const NS_PREFIX = null;


    /**
     * Initialize an faultcode
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
