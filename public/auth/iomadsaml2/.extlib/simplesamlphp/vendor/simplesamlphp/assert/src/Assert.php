<?php

declare(strict_types=1);

namespace SimpleSAML\Assert;

use BadMethodCallException;
use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;
use Webmozart\Assert\Assert as Webmozart;

use function array_pop;
use function array_unshift;
use function call_user_func_array;
use function end;
use function get_class;
use function is_array;
use function is_string;
use function is_object;
use function is_resource;
use function is_subclass_of;
use function lcfirst;
use function method_exists;
use function preg_match;
use function strval;

/**
 * Webmozart\Assert wrapper class
 *
 * @package simplesamlphp/assert
 *
 * @method static void string(mixed $value, string $message = '', class-string $exception = '')
 * @method static void stringNotEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void integer(mixed $value, string $message = '', class-string $exception = '')
 * @method static void integerish(mixed $value, string $message = '', class-string $exception = '')
 * @method static void positiveInteger(mixed $value, string $message = '', class-string $exception = '')
 * @method static void float(mixed $value, string $message = '', class-string $exception = '')
 * @method static void numeric(mixed $value, string $message = '', class-string $exception = '')
 * @method static void natural(mixed $value, string $message = '', class-string $exception = '')
 * @method static void boolean(mixed $value, string $message = '', class-string $exception = '')
 * @method static void scalar(mixed $value, string $message = '', class-string $exception = '')
 * @method static void object(mixed $value, string $message = '', class-string $exception = '')
 * @method static void resource(mixed $value, string|null $type, string $message = '', class-string $exception = '')
 * @method static void isCallable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isArray(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isTraversable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isArrayAccessible(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isCountable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isIterable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void isInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void notInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void isInstanceOfAny(mixed $value, array<object|string> $classes, string $message = '', class-string $exception = '')
 * @method static void isAOf(string|object $value, string $class, string $message = '', class-string $exception = '')
 * @method static void isNotA(string|object $value, string $class, string $message = '', class-string $exception = '')
 * @method static void isAnyOf(string|object $value, string[] $classes, string $message = '', class-string $exception = '')
 * @method static void isEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void notEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void null(mixed $value, string $message = '', class-string $exception = '')
 * @method static void notNull(mixed $value, string $message = '', class-string $exception = '')
 * @method static void true(mixed $value, string $message = '', class-string $exception = '')
 * @method static void false(mixed $value, string $message = '', class-string $exception = '')
 * @method static void notFalse(mixed $value, string $message = '', class-string $exception = '')
 * @method static void ip(mixed $value, string $message = '', class-string $exception = '')
 * @method static void ipv4(mixed $value, string $message = '', class-string $exception = '')
 * @method static void ipv6(mixed $value, string $message = '', class-string $exception = '')
 * @method static void email(mixed $value, string $message = '', class-string $exception = '')
 * @method static void uniqueValues(array $values, string $message = '', class-string $exception = '')
 * @method static void eq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void notEq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void same(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void notSame(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void greaterThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void greaterThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void lessThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void lessThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void range(mixed $value, mixed $min, mixed $max, string $message = '', class-string $exception = '')
 * @method static void oneOf(mixed $value, array $values, string $message = '', class-string $exception = '')
 * @method static void inArray(mixed $value, mixed $values, string $message = '', class-string $exception = '')
 * @method static void contains(string $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void notContains(string $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void notWhitespaceOnly($value, string $message = '', class-string $exception = '')
 * @method static void startsWith(string $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void notStartsWith(string $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void startsWithLetter(mixed $value, string $message = '', class-string $exception = '')
 * @method static void endsWith(string $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void notEndsWith(string $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void regex(string $value, string $pattern, string $message = '', class-string $exception = '')
 * @method static void notRegex(string $value, string $pattern, string $message = '', class-string $exception = '')
 * @method static void unicodeLetters(mixed $value, string $message = '', class-string $exception = '')
 * @method static void alpha(mixed $value, string $message = '', class-string $exception = '')
 * @method static void digits(string $value, string $message = '', class-string $exception = '')
 * @method static void alnum(string $value, string $message = '', class-string $exception = '')
 * @method static void lower(string $value, string $message = '', class-string $exception = '')
 * @method static void upper(string $value, string $message = '', class-string $exception = '')
 * @method static void length(string $value, int $length, string $message = '', class-string $exception = '')
 * @method static void minLength(string $value, int|float $min, string $message = '', class-string $exception = '')
 * @method static void maxLength(string $value, int|float $max, string $message = '', class-string $exception = '')
 * @method static void lengthBetween(string $value, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void fileExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void file(mixed $value, string $message = '', class-string $exception = '')
 * @method static void directory(mixed $value, string $message = '', class-string $exception = '')
 * @method static void readable(string $value, string $message = '', class-string $exception = '')
 * @method static void writable(string $value, string $message = '', class-string $exception = '')
 * @method static void classExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void subclassOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void interfaceExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void implementsInterface(mixed $value, mixed $interface, string $message = '', class-string $exception = '')
 * @method static void propertyExists(string|object $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void propertyNotExists(string|object $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void methodExists(string|object $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void methodNotExists(string|object $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void keyExists(array $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void keyNotExists(array $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void validArrayKey($value, string $message = '', class-string $exception = '')
 * @method static void count(Countable|array $array, int $number, string $message = '', class-string $exception = '')
 * @method static void minCount(Countable|array $array, int|float $min, string $message = '', class-string $exception = '')
 * @method static void maxCount(Countable|array $array, int|float $max, string $message = '', class-string $exception = '')
 * @method static void countBetween(Countable|array $array, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void isList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void isNonEmptyList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void isMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void isNonEmptyMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void uid(string $value, string $message = '', class-string $exception = '')
 * @method static void throws(Closure $expression, string $class = 'Exception', string $message = '', class-string $exception = '')
 *
 * @method static void nullOrString(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allString(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrStringNotEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allOrStringNotEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrInteger(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allInteger(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIntegerish(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIntegerish(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrPositiveInteger(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allPositiveInteger(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrFloat(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allFloat(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrNumeric(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNumeric(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrNatural(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNatural(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrBoolean(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allBoolean(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrScalar(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allScalar(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrObject(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allObject(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrResource(mixed $value, string|null $type, string $message = '', class-string $exception = '')
 * @method static void allResource(mixed $value, string|null $type, string $message = '', class-string $exception = '')
 * @method static void nullOrIsCallable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsCallable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsArray(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsArray(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsTraversable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsTraversable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsArrayAccessible(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsArrayAccessible(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsCountable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsCountable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsIterable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsIterable(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIsInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void allIsInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void nullOrNotInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void allNotInstanceOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void nullOrIsInstanceOfAny(mixed $value, array<object|string> $classes, string $message = '', class-string $exception = '')
 * @method static void allIsInstanceOfAny(mixed $value, array<object|string> $classes, string $message = '', class-string $exception = '')
 * @method static void nullOrIsAOf(object|string|null $value, string $class, string $message = '', class-string $exception = '')
 * @method static void allIsAOf(object|string|null $value, string $class, string $message = '', class-string $exception = '')
 * @method static void nullOrIsNotA(object|string|null $value, string $class, string $message = '', class-string $exception = '')
 * @method static void allIsNotA(iterable<object|string> $value, string $class, string $message = '', class-string $exception = '')
 * @method static void nullOrIsAnyOf(object|string|null $value, string[] $classes, string $message = '', class-string $exception = '')
 * @method static void allIsAnyOf(iterable<object|string> $value, string[] $classes, string $message = '', class-string $exception = '')
 * @method static void nullOrIsEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIsEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrNotEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNotEmpty(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNull(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNotNull(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrTrue(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allTrue(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrFalse(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allFalse(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrNotFalse(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allNotFalse(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIp(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIp(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIpv4(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIpv4(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrIpv6(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allIpv6(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrEmail(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allEmail(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrUniqueValues(array|null $values, string $message = '', class-string $exception = '')
 * @method static void allUniqueValues(iterable<array> $values, string $message = '', class-string $exception = '')
 * @method static void nullOrEq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void allEq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void nullOrNotEq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void allNotEq(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void nullOrSame(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void allSame(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void nullOrNotSame(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void allNotSame(mixed $value, mixed $expect, string $message = '', class-string $exception = '')
 * @method static void nullOrGreaterThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void allGreaterThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void nullOrGreaterThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void allGreaterThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void nullOrLessThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void allLessThan(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void nullOrLessThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void allLessThanEq(mixed $value, mixed $limit, string $message = '', class-string $exception = '')
 * @method static void nullOrRange(mixed $value, mixed $min, mixed $max, string $message = '', class-string $exception = '')
 * @method static void allRange(mixed $value, mixed $min, mixed $max, string $message = '', class-string $exception = '')
 * @method static void nullOrOneOf(mixed $value, array $values, string $message = '', class-string $exception = '')
 * @method static void allOneOf(mixed $value, array $values, string $message = '', class-string $exception = '')
 * @method static void nullOrInArray(mixed $value, array $values, string $message = '', class-string $exception = '')
 * @method static void allInArray(mixed $value, array $values, string $message = '', class-string $exception = '')
 * @method static void nullOrContains(string|null $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void allContains(iterable<string> $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void nullOrNotContains(string|null $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void allNotContains(iterable<string> $value, string $subString, string $message = '', class-string $exception = '')
 * @method static void nullOrWhitespaceOnly(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allWhitespaceOnly(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrStartsWith(string|null $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void allStartsWith(iterable<string> $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void nullOrNotStartsWith(string|null $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void allNotStartsWith(iterable<string> $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void nullOrStartsWithLetter(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allStartsWithLetter(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrEndsWith(string|null $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void allEndsWith(iterable<string> $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void nullOrNotEndsWith(string|null $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void allNotEndsWith(iterable<string> $value, string $suffix, string $message = '', class-string $exception = '')
 * @method static void nullOrRegex(string|null $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void allRegEx(iterable<string> $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void nullOrNotRegex(string|null $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void allNotRegEx(iterable<string> $value, string $prefix, string $message = '', class-string $exception = '')
 * @method static void nullOrUnicodeLetters(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allUnicodeLetters(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrAlpha(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allAlpha(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrDigits(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allDigits(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrAlnum(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allAlnum(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrLower(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allLower(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrUpper(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allUpper(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrLength(string|null $value, int $length, string $message = '', class-string $exception = '')
 * @method static void allLength(iterable<string> $value, int $length, string $message = '', class-string $exception = '')
 * @method static void nullOrMinLength(string|null $value, int|float $min, string $message = '', class-string $exception = '')
 * @method static void allMinLength(iterable<string> $value, int|float $min, string $message = '', class-string $exception = '')
 * @method static void nullOrMaxLength(string|null $value, int|float $max, string $message = '', class-string $exception = '')
 * @method static void allMaxLength(iterable<string> $value, int|float $max, string $message = '', class-string $exception = '')
 * @method static void nullOrLengthBetween(string|null $value, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void allLengthBetween(iterable<string> $value, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void nullOrFileExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allFileExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrFile(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allFile(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrDirectory(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allDirectory(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrReadable(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allReadable(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrWritable(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allWritable(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrClassExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allClassExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrSubclassOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void allSubclassOf(mixed $value, string|object $class, string $message = '', class-string $exception = '')
 * @method static void nullOrInterfaceExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allInterfaceExists(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrImplementsInterface(mixed $value, mixed $interface, string $message = '', class-string $exception = '')
 * @method static void allImplementsInterface(mixed $value, mixed $interface, string $message = '', class-string $exception = '')
 * @method static void nullOrPropertyExists(string|object|null $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void allPropertyExists(iterable<string|object> $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void nullOrPropertyNotExists(string|object|null $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void allPropertyNotExists(iterable<string|object> $classOrObject, mixed $property, string $message = '', class-string $exception = '')
 * @method static void nullOrMethodExists(string|object|null $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void allMethodExists(iterable<string|object> $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void nullOrMethodNotExists(string|object|null $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void allMethodNotExists(iterable<string|object> $classOrObject, mixed $method, string $message = '', class-string $exception = '')
 * @method static void nullOrKeyExists(array|null $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void allKeyExists(iterable<array> $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void nullOrKeyNotExists(array|null $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void allKeyNotExists(iterable<array> $array, string|int $key, string $message = '', class-string $exception = '')
 * @method static void nullOrValidArrayKey(mixed $value, string $message = '', class-string $exception = '')
 * @method static void allValidArrayKey(mixed $value, string $message = '', class-string $exception = '')
 * @method static void nullOrCount(Countable|array|null $array, int $number, string $message = '', class-string $exception = '')
 * @method static void allCount(iterable<Countable|array> $array, int $number, string $message = '', class-string $exception = '')
 * @method static void nullOrMinCount(Countable|array|null $array, int|float $min, string $message = '', class-string $exception = '')
 * @method static void allMinCount(iterable<Countable|array> $array, int|float $min, string $message = '', class-string $exception = '')
 * @method static void nullOrMaxCount(Countable|array|null $array, int|float $max, string $message = '', class-string $exception = '')
 * @method static void allMaxCount(iterable<Countable|array> $array, int|float $max, string $message = '', class-string $exception = '')
 * @method static void nullOrCountBetween(Countable|array|null $array, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void allCountBetween(iterable<Countable|array> $array, int|float $min, int|float $max, string $message = '', class-string $exception = '')
 * @method static void nullOrIsList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void allIsList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void nullOrIsNonEmptyList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void allIsNonEmptyList(mixed $array, string $message = '', class-string $exception = '')
 * @method static void nullOrIsMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void allIsMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void nullOrIsNonEmptyMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void allIsNonEmptyMap(mixed $array, string $message = '', class-string $exception = '')
 * @method static void nullOrUuid(string|null $value, string $message = '', class-string $exception = '')
 * @method static void allUuid(iterable<string> $value, string $message = '', class-string $exception = '')
 * @method static void nullOrThrows(Closure|null $expression, string $class, string $message = '', class-string $exception = '')
 * @method static void allThrows(iterable<Closure> $expression, string $class, string $message = '', class-string $exception = '')
 */
final class Assert
{
    use CustomAssertionTrait;


    /**
     * @param string $name
     * @param array $arguments
     */
    public static function __callStatic(string $name, array $arguments): void
    {
        // Handle Exception-parameter
        $exception = AssertionFailedException::class;

        $last = end($arguments);
        if (is_string($last) && class_exists($last) && is_subclass_of($last, Throwable::class)) {
            $exception = $last;
            array_pop($arguments);
        }

        try {
            // Putting Webmozart first, since the most calls will be to their native assertions
            if (method_exists(Webmozart::class, $name)) {
                call_user_func_array([Webmozart::class, $name], $arguments);
                return;
            } elseif (method_exists(static::class, $name)) {
                call_user_func_array([static::class, $name], $arguments);
                return;
            } elseif (preg_match('/^nullOr(.*)$/i', $name, $matches)) {
                $method = lcfirst($matches[1]);
                if (method_exists(Webmozart::class, $method)) {
                    call_user_func_array([static::class, 'nullOr'], [[Webmozart::class, $method], $arguments]);
                } elseif (method_exists(static::class, $method)) {
                    call_user_func_array([static::class, 'nullOr'], [[static::class, $method], $arguments]);
                } else {
                    throw new BadMethodCallException(sprintf("Assertion named `%s` does not exists.", $method));
                }
            } elseif (preg_match('/^all(.*)$/i', $name, $matches)) {
                $method = lcfirst($matches[1]);
                if (method_exists(Webmozart::class, $method)) {
                    call_user_func_array([static::class, 'all'], [[Webmozart::class, $method], $arguments]);
                } elseif (method_exists(static::class, $method)) {
                    call_user_func_array([static::class, 'all'], [[static::class, $method], $arguments]);
                } else {
                    throw new BadMethodCallException(sprintf("Assertion named `%s` does not exists.", $method));
                }
            } else {
                throw new BadMethodCallException(sprintf("Assertion named `%s` does not exists.", $name));
            }
        } catch (InvalidArgumentException $e) {
            throw new $exception($e->getMessage());
        }
    }


    /**
     * Handle nullOr* for either Webmozart or for our custom assertions
     *
     * @param callable $method
     * @param array $arguments
     * @return void
     */
    private static function nullOr(callable $method, array $arguments): void
    {
        $value = reset($arguments);
        ($value === null) || call_user_func_array($method, $arguments);
    }


    /**
     * all* for our custom assertions
     *
     * @param callable $method
     * @param array $arguments
     * @return void
     */
    private static function all(callable $method, array $arguments): void
    {
        $values = array_pop($arguments);
        foreach ($values as $value) {
            $tmp = $arguments;
            array_unshift($tmp, $value);
            call_user_func_array($method, $tmp);
        }
    }


    /**
     * @param mixed $value
     *
     * @return string
     */
    protected static function valueToString($value): string
    {
        if (null === $value) {
            return 'null';
        }

        if (true === $value) {
            return 'true';
        }

        if (false === $value) {
            return 'false';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return get_class($value) . ': ' . self::valueToString($value->__toString());
            }

            if ($value instanceof DateTime || $value instanceof DateTimeImmutable) {
                return get_class($value) . ': ' . self::valueToString($value->format('c'));
            }

            return get_class($value);
        }

        if (is_resource($value)) {
            return 'resource';
        }

        if (is_string($value)) {
            return '"' . $value . '"';
        }

        return strval($value);
    }
}
