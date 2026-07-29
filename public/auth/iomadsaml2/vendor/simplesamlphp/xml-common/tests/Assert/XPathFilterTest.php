<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Test\Assert;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\Assert\Assert as XMLAssert;
use SimpleSAML\XML\Constants as C;

/**
 * Class \SimpleSAML\XML\Assert\XPathFilterTest
 *
 * @package simplesamlphp/xml-common
 */
#[CoversClass(XMLAssert::class)]
final class XPathFilterTest extends TestCase
{
    /**
     * @param string $filter
     * @param boolean $shouldPass
     * @param array<string> $axes
     * @param array<string> $functions
     */
    #[DataProvider('provideXPathFilter')]
    public function testDefaultAllowedXPathFilter(
        string $filter,
        bool $shouldPass,
        array $axes = C::DEFAULT_ALLOWED_AXES,
        array $functions = C::DEFAULT_ALLOWED_FUNCTIONS,
    ): void {
        try {
            XMLAssert::validAllowedXPathFilter($filter, $axes, $functions);
            $this->assertTrue($shouldPass);
        } catch (InvalidArgumentException $e) {
            $this->assertFalse($shouldPass);
        }
    }


    /**
     * @return array<array{0: string, 1: bool}>
     */
    public static function provideXPathFilter(): array
    {
        return [
            // [ 'xpath_expression', allowed ]

            // Evil
            ['count(//. | //@* | //namespace::*)', false],

            // Perfectly normal
            ["//ElementToEncrypt[@attribute='value']", true],
            ["/RootElement/ChildElement[@id='123']", true],
            ["not(self::UnwantedNode)", true ],
            ["//ElementToEncrypt[not(@attribute='value')]", true],

            // From https://www.w3.org/TR/xmlenc-core1/
            ['self::text()[parent::enc:CipherValue[@Id="example1"]]', false ],
            ['self::xenc:EncryptedData[@Id="example1"]', true],

            // Nonsense, but allowed by the filter as it doesn't understand XPath.
            ['self::not()[parent::enc:CipherValue[@Id="example1"]]', true ],

            // namespace in element name
            ["not(self::namespace)", true],

            // using "namespace" as a Namespace prefix
            ["//namespace:ElementName", true],

            // namespace in attribute value
            ["//ElementToEncrypt[@attribute='namespace::x']", true],

            // function in attribute value
            ["//ElementToEncrypt[@attribute='ns1::count()']", true],
        ];
    }
}
