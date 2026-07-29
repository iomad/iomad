<?php

declare(strict_types=1);

namespace SimpleSAML\Assert;

use GuzzleHttp\Psr7\Exception\MalformedUriException;
use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;

use function sprintf;
use function strlen;
use function substr;

/**
 * @package simplesamlphp/assert
 */
trait URITrait
{
    /***********************************************************************************
     *  NOTE:  Custom assertions may be added below this line.                         *
     *         They SHOULD be marked as `protected` to ensure the call is forced       *
     *          through __callStatic().                                                *
     *         Assertions marked `public` are called directly and will                 *
     *          not handle any custom exception passed to it.                          *
     ***********************************************************************************/


    /**
     * @param string $value
     * @param string $message
     */
    protected static function validURN(string $value, string $message = ''): void
    {
        try {
            $uri = new Uri($value);
        } catch (MalformedUriException $e) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid RFC3986 compliant URI',
                $value,
            ));
        }

        if (
            $uri->getScheme() !== 'urn'
            || (($uri->getScheme() !== null) && $uri->getPath() !== substr($value, strlen($uri->getScheme()) + 1))
        ) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid RFC8141 compliant URN',
                $value,
            ));
        }
    }


    /**
     * @param string $value
     * @param string $message
     */
    protected static function validURL(string $value, string $message = ''): void
    {
        try {
            $uri = new Uri($value);
        } catch (MalformedUriException $e) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid RFC3986 compliant URI',
                $value,
            ));
        }

        if ($uri->getScheme() !== 'http' && $uri->getScheme() !== 'https') {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid RFC2396 compliant URL',
                $value,
            ));
        }
    }


    /**
     * @param string $value
     * @param string $message
     */
    protected static function validURI(string $value, string $message = ''): void
    {
        try {
            new Uri($value);
        } catch (MalformedUriException $e) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid RFC3986 compliant URI',
                $value,
            ));
        }
    }
}
