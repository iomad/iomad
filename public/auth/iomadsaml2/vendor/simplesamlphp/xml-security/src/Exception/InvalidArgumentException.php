<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Exception;

use InvalidArgumentException as BuiltinInvalidArgumentException;

use function gettype;
use function is_object;
use function sprintf;

/**
 * Class InvalidArgumentException
 *
 * This exception is thrown when a parameter is passed to a method with the wrong type or contents.
 *
 * @package simplesamlphp/xml-security
 */
class InvalidArgumentException extends BuiltinInvalidArgumentException
{
    /**
     * @param string $expected description of expected type
     * @param mixed  $parameter the parameter that is not of the expected type.
     *
     * @return \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException
     */
    public static function invalidType(string $expected, mixed $parameter): self
    {
        $message = sprintf(
            'Invalid Argument type: "%s" expected, "%s" given',
            $expected,
            is_object($parameter) ? $parameter::class : gettype($parameter),
        );

        return new self($message);
    }
}
