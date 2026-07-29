<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\dsig11;

use SimpleSAML\XML\Base64ElementTrait;

/**
 * Class representing a dsig11:Seed element.
 *
 * @package simplesaml/xml-security
 */
final class Seed extends AbstractDsig11Element
{
    use Base64ElementTrait;

    /** @var string */
    public const LOCALNAME = 'seed';


    /**
     * Initialize a Seed element.
     *
     * @param string $value
     */
    public function __construct(
        string $value,
    ) {
        $this->setContent($value);
    }
}
