<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;

use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait DurationTrait
{
    /** @var string */
    private static string $duration_regex = '/^([-+]?)P(?!$)(?:(?<years>\d+(?:[\.\,]\d+)?)Y)?(?:(?<months>\d+(?:[\.\,]\d+)?)M)?(?:(?<weeks>\d+(?:[\.\,]\d+)?)W)?(?:(?<days>\d+(?:[\.\,]\d+)?)D)?(T(?=\d)(?:(?<hours>\d+(?:[\.\,]\d+)?)H)?(?:(?<minutes>\d+(?:[\.\,]\d+)?)M)?(?:(?<seconds>\d+(?:[\.\,]\d+)?)S)?)?$/D';

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
    protected static function validDuration(string $value, string $message = ''): void
    {
        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$duration_regex]]) === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid xs:duration',
                $value,
            ));
        }
    }
}
