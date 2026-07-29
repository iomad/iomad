<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use SimpleSAML\XML\Base64ElementTrait;

/**
 * Class representing a dsig11:PublicKey element.
 *
 * @package simplesaml/xml-security
 */
final class PublicKey extends AbstractDsig11Element
{
    use Base64ElementTrait;


    /**
     * Initialize a PublicKey element.
     *
     * @param string $value
     */
    public function __construct(
        string $value,
    ) {
        $this->setContent($value);
    }
}
