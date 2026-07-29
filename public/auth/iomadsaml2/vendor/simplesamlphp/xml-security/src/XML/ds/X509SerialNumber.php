<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use SimpleSAML\XML\IntegerElementTrait;

/**
 * Class representing a ds:X509SerialNumber element.
 *
 * @package simplesaml/xml-security
 */
final class X509SerialNumber extends AbstractDsElement
{
    use IntegerElementTrait;


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
