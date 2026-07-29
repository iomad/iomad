<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;

use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait TokensTrait
{
    /** @var string */
    private static string $nmtoken_regex = '/^[\w.:-]+$/Du';

    /** @var string */
    private static string $nmtokens_regex = '/^([\w.:-]+)([\s][\w.:-]+)*$/Du';

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
    protected static function validNMToken(string $value, string $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$nmtoken_regex]]) === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid xs:NMTOKEN',
                $value,
            ));
        }
    }


    /**
     * @param string $value
     * @param string $message
     */
    protected static function validNMTokens(string $value, string $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$nmtokens_regex]]) === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid xs:NMTOKENS',
                $value,
            ));
        }
    }
}
