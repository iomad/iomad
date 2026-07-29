<?php

declare(strict_types=1);

namespace SimpleSAML\Assert;

use InvalidArgumentException;

use function array_map;
use function implode;
use function in_array;
use function sprintf;

/**
 * @package simplesamlphp/assert
 */
trait NotInArrayTrait
{
    /***********************************************************************************
     *  NOTE:  Custom assertions may be added below this line.                         *
     *         They SHOULD be marked as `protected` to ensure the call is forced       *
     *          through __callStatic().                                                *
     *         Assertions marked `public` are called directly and will                 *
     *          not handle any custom exception passed to it.                          *
     ***********************************************************************************/


    /**
     * @param mixed $value
     * @param array<mixed> $values
     * @param string $message
     */
    protected static function notInArray($value, array $values, string $message = ''): void
    {
        if (in_array($value, $values, true)) {
            $callable = function (mixed $val) {
                return self::valueToString($val);
            };

            throw new InvalidArgumentException(sprintf(
                $message ?: 'Expected none of: %2$s. Got: %s',
                self::valueToString($value),
                implode(', ', array_map($callable, $values)),
            ));
        }
    }
}
