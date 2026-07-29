<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Assert;

use InvalidArgumentException;
use SimpleSAML\Assert\Assert as BaseAssert;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\Exception\RuntimeException;
use SimpleSAML\XML\Utils\XPathFilter;

use function sprintf;

/**
 * @package simplesamlphp/xml-common
 */
trait XPathFilterTrait
{
    /***********************************************************************************
     *  NOTE:  Custom assertions may be added below this line.                         *
     *         They SHOULD be marked as `private` to ensure the call is forced         *
     *          through __callStatic().                                                *
     *         Assertions marked `public` are called directly and will                 *
     *          not handle any custom exception passed to it.                          *
     ***********************************************************************************/

    /**
     * Check an XPath expression for allowed axes and functions
     * The goal is preventing DoS attacks by limiting the complexity of the XPath expression by only allowing
     * a select subset of functions and axes.
     * The check uses a list of allowed functions and axes, and throws an exception when an unknown function
     * or axis is found in the $xpathExpression.
     *
     * Limitations:
     * - The implementation is based on regular expressions, and does not employ an XPath 1.0 parser. It may not
     *   evaluate all possible valid XPath expressions correctly and cause either false positives for valid
     *   expressions or false negatives for invalid expressions.
     * - The check may still allow expressions that are not safe, I.e. expressions that consist of only
     *   functions and axes that are deemed "save", but that are still slow to evaluate. The time it takes to
     *   evaluate an XPath expression depends on the complexity of both the XPath expression and the XML document.
     *   This check, however, does not take the XML document into account, nor is it aware of the internals of the
     *   XPath processor that will evaluate the expression.
     * - The check was written with the XPath 1.0 syntax in mind, but should work equally well for XPath 2.0 and 3.0.
     *
     * @param string $xpathExpression
     * @param array<string> $allowedAxes
     * @param array<string> $allowedFunctions
     * @param string $message
     */
    public static function validAllowedXPathFilter(
        string $xpathExpression,
        array $allowedAxes = C::DEFAULT_ALLOWED_AXES,
        array $allowedFunctions = C::DEFAULT_ALLOWED_FUNCTIONS,
        string $message = '',
    ): void {
        BaseAssert::allString($allowedAxes);
        BaseAssert::allString($allowedFunctions);
        BaseAssert::maxLength(
            $xpathExpression,
            C::XPATH_FILTER_MAX_LENGTH,
            sprintf('XPath Filter exceeds the limit of 100 characters.'),
        );

        try {
            // First remove the contents of any string literals in the $xpath to prevent false positives
            $xpathWithoutStringLiterals = XPathFilter::removeStringContents($xpathExpression);

            // Then check that the xpath expression only contains allowed functions and axes, throws when it doesn't
            XPathFilter::filterXPathFunction($xpathWithoutStringLiterals, $allowedFunctions);
            XPathFilter::filterXPathAxis($xpathWithoutStringLiterals, $allowedAxes);
        } catch (RuntimeException $e) {
            throw new InvalidArgumentException(sprintf(
                $message ?: $e->getMessage(),
                $xpathExpression,
            ));
        }
    }
}
