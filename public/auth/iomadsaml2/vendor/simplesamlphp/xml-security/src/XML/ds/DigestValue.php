<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use SimpleSAML\XML\Base64ElementTrait;
use SimpleSAML\XML\SchemaValidatableElementInterface;
use SimpleSAML\XML\SchemaValidatableElementTrait;

/**
 * Class representing a ds:DigestValue element.
 *
 * @package simplesaml/xml-security
 */
final class DigestValue extends AbstractDsElement implements SchemaValidatableElementInterface
{
    use Base64ElementTrait;
    use SchemaValidatableElementTrait;


    /**
     * @param string $content
     */
    public function __construct(string $content)
    {
        $this->setContent($content);
    }
}
