<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Utils;

use function bin2hex;
use function random_bytes;

/**
 * @package simplesamlphp/xml-common
 */
class Random
{
    /**
     * The fixed length of random identifiers.
     */
    public const ID_LENGTH = 43;

    /**
     * This function will generate a unique ID that is valid for use
     * in an xs:ID attribute
     */
    public function generateID(): string
    {
        return '_' . bin2hex(random_bytes((self::ID_LENGTH - 1) / 2));
    }
}
