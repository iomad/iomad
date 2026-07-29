<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use SimpleSAML\XML\StringElementTrait;

/**
 * Class representing a xenc:CarriedKeyName element.
 *
 * @package simplesamlphp/xml-security
 */
final class CarriedKeyName extends AbstractXencElement
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
