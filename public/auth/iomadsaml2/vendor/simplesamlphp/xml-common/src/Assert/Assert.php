<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use SimpleSAML\Assert\Assert as BaseAssert;

/**
 * @package simplesamlphp/xml-common
 *
 * @method static void validAllowedXPathFilter(mixed $value, array $allowed_axes, array $allowed_functions, string $message = '', string $exception = '')
 * @method static void validHexBinary(mixed $value, string $message = '', string $exception = '')
 * @method static void validNMToken(mixed $value, string $message = '', string $exception = '')
 * @method static void validNMTokens(mixed $value, string $message = '', string $exception = '')
 * @method static void validDuration(mixed $value, string $message = '', string $exception = '')
 * @method static void validDateTime(mixed $value, string $message = '', string $exception = '')
 * @method static void validNCName(mixed $value, string $message = '', string $exception = '')
 * @method static void validQName(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidAllowedXPathFilter(mixed $value, array $allowed_axes, array $allowed_functions, string $message = '', string $exception = '')
 * @method static void nullOrValidHexBinary(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidNMToken(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidNMTokens(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidDuration(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidDateTime(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidNCName(mixed $value, string $message = '', string $exception = '')
 * @method static void nullOrValidQName(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidAllowedXPathFilter(mixed $value, array $allowed_axes, array $allowed_functions, string $message = '', string $exception = '')
 * @method static void allValidHexBinary(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidNMToken(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidNMTokens(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidDuration(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidDateTime(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidNCName(mixed $value, string $message = '', string $exception = '')
 * @method static void allValidQName(mixed $value, string $message = '', string $exception = '')
 */
class Assert extends BaseAssert
{
    use DateTimeTrait;
    use DurationTrait;
    use HexBinTrait;
    use NamesTrait;
    use TokensTrait;
    use XPathFilterTrait;
}
