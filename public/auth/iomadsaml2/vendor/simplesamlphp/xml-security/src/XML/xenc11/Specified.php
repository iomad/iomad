<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc11;

use SimpleSAML\XML\Base64ElementTrait;

/**
 * Class representing a xenc11:Specified element.
 *
 * @package simplesamlphp/xml-security
 */
final class Specified extends AbstractXenc11Element
{
    use Base64ElementTrait;


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
