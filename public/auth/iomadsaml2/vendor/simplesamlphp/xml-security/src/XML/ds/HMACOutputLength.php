<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use SimpleSAML\XML\IntegerElementTrait;

/**
 * Class representing a ds:HMACOutputLength element.
 *
 * @package simplesamlphp/xml-security
 */
final class HMACOutputLength extends AbstractDsElement
{
    use IntegerElementTrait;


    /**
     * @param string $length
     */
    public function __construct(string $length)
    {
        $this->setContent($length);
    }
}
