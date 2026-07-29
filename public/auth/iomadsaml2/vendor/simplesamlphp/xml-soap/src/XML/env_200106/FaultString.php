<?php

declare(strict_types=1);

namespace SimpleSAML\SOAP\XML\env_200106;

use SimpleSAML\XML\AbstractElement;
use SimpleSAML\XML\StringElementTrait;

/**
 * Class representing a faultstring element.
 *
 * @package simplesaml/xml-soap
 */
final class FaultString extends AbstractElement
{
    use StringElementTrait;

    /** @var string */
    public const LOCALNAME = 'faultstring';

    /** @var null */
    public const NS = null;

    /** @var null */
    public const NS_PREFIX = null;


    /**
     * Initialize an faultstring
     *
     * @param string $faultString
     */
    public function __construct(string $faultString)
    {
        $this->setContent($faultString);
    }
}
