<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

/**
 * Class OpenSSLException
 *
 * This exception is thrown when an error occurs during a call to the openssl backend.
 *
 * @package simplesamlphp/xml-security
 */
class OpenSSLException extends RuntimeException
{
    /**
     * @param string $message
     */
    public function __construct(string $message = 'Generic OpenSSL exception')
    {
        $stack = [];
        while (($msg = openssl_error_string()) !== false) {
            $stack[] = $msg;
        }

        foreach ($stack as $line) {
            $message .= '; ' . $line;
        }
        $message .= '.';

        parent::__construct($message);
    }
}
