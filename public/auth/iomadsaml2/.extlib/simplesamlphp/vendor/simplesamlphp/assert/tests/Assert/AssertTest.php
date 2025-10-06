<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Assert;

use BadMethodCallException;
use DateTimeImmutable;
use ArrayIterator;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\Test\Utils\TestClass;
use StdClass;

use function getcwd;
use function opendir;

/**
 * Class \SimpleSAML\Assert\Assert
 *
 * @package simplesamlphp/saml2
 */
final class AssertTest extends TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testAssertionPassing(): void
    {
        /** @psalm-suppress TooFewArguments */
        Assert::integer(1);
    }


    /**
     */
    public function testAssertionFailingThrowsException(): void
    {
        $this->expectException(AssertionFailedException::class);
        Assert::nullOrSame('a', 'b');
    }


    /**
     */
    public function testAssertionFailingWithCustomExceptionThrowsCustomException(): void
    {
        $this->expectException(LogicException::class);
        Assert::allSame(['a', 'b', 'c'], 'b', LogicException::class);
    }


    /**
     */
    public function testUnknownAssertionRaisesBadMethodCallException(): void
    {
        $this->expectException(BadMethodCallException::class);
        Assert::thisAssertionDoesNotExist('a', 'b', LogicException::class);
    }


    /**
     */
    public function testUnknownNullOrAssertionRaisesBadMethodCallException(): void
    {
        $this->expectException(BadMethodCallException::class);
        Assert::nullOrThisAssertionDoesNotExist('a', 'b', LogicException::class);
    }


    /**
     */
    public function testUnknownAllAssertionRaisesBadMethodCallException(): void
    {
        $this->expectException(BadMethodCallException::class);
        Assert::allThisAssertionDoesNotExist('a', 'b', LogicException::class);
    }


    /**
     */
    public function testNullOrCustomAssertionWorks(): void
    {
        Assert::nullOrStringPlausibleBase64('U2ltcGxlU0FNTHBocA==');
        Assert::nullOrStringPlausibleBase64(null);

        // Also make sure it keeps working for Webmozart's native assertions
        Assert::nullOrString(null);
        Assert::nullOrString('test');

        // Test a failure for coverage
        $this->expectException(AssertionFailedException::class);
        Assert::nullOrStringPlausibleBase64('U2ltcGxlU0FNTHocA==');
    }


    /**
     */
    public function testAllCustomAssertionWorks(): void
    {
        Assert::allStringPlausibleBase64(['U2ltcGxlU0FNTHBocA==', 'dGVzdA==']);

        // Also make sure it keeps working for Webmozart's native assertions
        Assert::allString(['test', 'phpunit']);

        // Test a failure for coverage
        $this->expectException(AssertionFailedException::class);
        Assert::allStringPlausibleBase64(['U2ltcGxlU0FNTHocA==', null]);
    }


    /**
     * @dataProvider provideValue
     * @param mixed $value
     * @param string $expected
     */
    public function testValueToString($value, string $expected): void
    {
        $assert = new Assert();
        $method = new ReflectionMethod(Assert::class, 'valueToString');
        $method->setAccessible(true);

        $this->assertEquals($expected, $method->invoke($assert, $value));
    }


    /**
     * @return array
     */
    public function provideValue(): array
    {
        $stringable = new TestClass('phpunit');

        $dateTime = new DateTimeImmutable('2000-01-01T00:00:00+00:00');

        $otherObject = new StdClass();

        $resource = opendir(getcwd());

        return [
            'null' => [null, 'null'],
            'true' => [true, 'true'],
            'false' => [false, 'false'],
            'array' => [[], 'array'],
            'Stringable' => [$stringable, 'SimpleSAML\Test\Utils\TestClass: "phpunit"'],
            'DateTime' => [$dateTime, 'DateTimeImmutable: "2000-01-01T00:00:00+00:00"'],
            'object' => [$otherObject, 'stdClass'],
            'resource' => [$resource, 'resource'],
            'string' => ['string', '"string"'],
            'other' => [1, '1'],
        ];
    }
}
