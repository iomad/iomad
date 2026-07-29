<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Utils;

use Random\RandomException;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;
use SimpleSAML\XMLSecurity\Exception\RuntimeException;
use ValueError;

use function random_bytes;

/**
 * A collection of utilities to generate cryptographically-secure random data.
 *
 * @package SimpleSAML\XMLSecurity\Utils
 */
class Random
{
    /**
     * Generate a given amount of cryptographically secure random bytes.
     *
     * @param positive-int $length The amount of bytes required.
     *
     * @return string A random string of $length length.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\InvalidArgumentException
     *   If $length is not an integer greater than zero.
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException
     *   If no appropriate sources of cryptographically secure random generators are available.
     */
    public static function generateRandomBytes(int $length): string
    {
        Assert::positiveInteger(
            $length,
            'Invalid length received to generate random bytes.',
            InvalidArgumentException::class,
        );

        try {
            return random_bytes($length);
        } catch (ValueError) { // @phpstan-ignore-line
            throw new InvalidArgumentException('Invalid length received to generate random bytes.');
        } catch (RandomException) {
            throw new RuntimeException(
                'Cannot generate random bytes, no cryptographically secure random generator available.',
            );
        }
    }
}
