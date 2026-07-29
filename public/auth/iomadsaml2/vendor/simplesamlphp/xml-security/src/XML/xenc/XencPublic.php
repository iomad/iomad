<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use SimpleSAML\XML\Base64ElementTrait;

/**
 * Class representing a xenc:Public element.
 *
 * @package simplesaml/xml-security
 */
final class XencPublic extends AbstractXencElement
{
    use Base64ElementTrait;

    /** @var string */
    public const LOCALNAME = 'Public';


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
