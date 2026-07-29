<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class IOException
 *
 * This exception is thrown when an I/O operation cannot be handled
 *
 * @package simplesamlphp/xml-security
 */
class IOException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Generic I/O Exception.')
    {
        parent::__construct($message);
    }
}
