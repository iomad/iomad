<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;

use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait DateTimeTrait
{
    /** @var string */
    private static string $datetime_regex = '/-?[0-9]{4}-(((0(1|3|5|7|8)|1(0|2))-(0[1-9]|(1|2)[0-9]|3[0-1]))|((0(4|6|9)|11)-(0[1-9]|(1|2)[0-9]|30))|(02-(0[1-9]|(1|2)[0-9])))T([0-1][0-9]|2[0-4]):(0[0-9]|[1-5][0-9]):(0[0-9]|[1-5][0-9])(\.[0-999])?((\+|-)([0-1][0-9]|2[0-4]):(0[0-9]|[1-5][0-9])|Z)?/i';

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
    protected static function validDateTime(string $value, string $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$datetime_regex]]) === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid xs:dateTime',
                $value,
            ));
        }
    }
}
