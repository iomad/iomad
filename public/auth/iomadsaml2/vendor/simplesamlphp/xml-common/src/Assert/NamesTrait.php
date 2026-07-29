<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;

use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait NamesTrait
{
    /** @var string */
    private static string $ncname_regex = '/^[a-zA-Z_][\w.-]*$/D';

    /** @var string */
    private static string $qname_regex = '/^[a-zA-Z_][\w.-]*:[a-zA-Z_][\w.-]*$/D';

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
    protected static function validNCName(string $value, string $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$ncname_regex]]) === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid non-colonized name (NCName)',
                $value,
            ));
        }
    }


    /**
     * @param string $value
     * @param string $message
     */
    protected static function validQName(string $value, string $message = ''): void
    {
        if (
            filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$qname_regex]]) === false &&
            filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$ncname_regex]]) === false
        ) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid qualified name (QName)',
                $value,
            ));
        }
    }
}
