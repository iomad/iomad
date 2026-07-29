<?php

declare(strict_types=1);

namespace SimpleSAML\Assert;

use InvalidArgumentException;

use function base64_decode;
use function base64_encode;
use function filter_var;
use function sprintf;

/**
 * @package simplesamlphp/assert
 */
trait Base64Trait
{
    /** @var string */
    private static string $base64_regex = '/^(?:[a-z0-9+\/]{4})*(?:[a-z0-9+\/]{2}==|[a-z0-9+\/]{3}=)?$/i';


    /***********************************************************************************
     *  NOTE:  Custom assertions may be added below this line.                         *
     *         They SHOULD be marked as `protected` to ensure the call is forced       *
     *          through __callStatic().                                                *
     *         Assertions marked `public` are called directly and will                 *
     *          not handle any custom exception passed to it.                          *
     ***********************************************************************************/


    /**
     * Note: This test is not bullet-proof but prevents a string containing illegal characters
     * from being passed and ensures the string roughly follows the correct format for a Base64 encoded string
     *
     * @param string $value
     * @param string $message
     */
    protected static function validBase64(string $value, string $message = ''): void
    {
        $result = true;

        if (filter_var($value, FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => self::$base64_regex]]) === false) {
            $result = false;
        } else {
            $decoded = base64_decode($value, true);
            if (empty($decoded)) { // Invalid _or_ empty string
                $result = false;
            } elseif (base64_encode($decoded) !== $value) {
                $result = false;
            }
        }

        if ($result === false) {
            throw new InvalidArgumentException(sprintf(
                $message ?: '\'%s\' is not a valid Base64 encoded string',
                $value,
            ));
        }
    }
}
