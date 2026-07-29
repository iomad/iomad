<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;

use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait HexBinTrait
{
    /** @var string */
    private static string $hexbin_regex = '/^([0-9a-fA-F]{2})+$/D';

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
    protected static function validHexBinary(string $value, string $message = ''): void
    {
        $result = true;

        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$hexbin_regex]]) === false) {
            $result = false;
        }

        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid hexBinary string',
                $value,
            ));
        }
    }
}
