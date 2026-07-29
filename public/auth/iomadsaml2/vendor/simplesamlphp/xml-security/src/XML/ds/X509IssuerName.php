<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use SimpleSAML\XML\StringElementTrait;

/**
 * Class representing a ds:X509IssuerName element.
 *
 * @package simplesaml/xml-security
 */
final class X509IssuerName extends AbstractDsElement
{
    use StringElementTrait;


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
