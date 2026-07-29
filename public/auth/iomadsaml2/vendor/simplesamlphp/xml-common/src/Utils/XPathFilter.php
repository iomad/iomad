<?php

declare(strict_types=1);

namespace SimpleSAML\XML\Utils;

use SimpleSAML\XML\Exception\RuntimeException;

use function in_array;
use function preg_match_all;
use function preg_replace;

/**
 * XPathFilter helper functions for the XML library.
 *
 * @package simplesamlphp/xml-common
 */
class XPathFilter
{
    /**
     * Remove the content from all single or double-quoted strings in $input, leaving only quotes.
     *
     * @param string $input
     * @return string
     * @throws \SimpleSAML\XML\Exception\RuntimeException
     */
    public static function removeStringContents(string $input): string
    {
        /**
         * This regex should not be vulnerable to a ReDOS, because it uses possessive quantifiers
         * that prevent backtracking.
         *
         * @see https://www.owasp.org/index.php/Regular_expression_Denial_of_Service_-_ReDoS
         *
         * Use possessive quantifiers (i.e. *+ and ++ instead of * and + respectively) to prevent backtracking.
         *
         * '/(["\'])(?:(?!\1).)*+\1/'
         *  (["\'])  # Match a single or double quote and capture it in group 1
         *  (?:      # Start a non-capturing group
         *    (?!    # Negative lookahead
         *      \1   # Match the same quote as in group 1
         *    )      # End of negative lookahead
         *    .      # Match any character (that is not a quote, because of the negative lookahead)
         *  )*+      # Repeat the non-capturing group zero or more times, possessively
         *  \1       # Match the same quote as in group 1
         */
        $res = preg_replace(
            '/(["\'])(?:(?!\\1).)*+\\1/',
            "\\1\\1",   // Replace the content with two of the quotes that were matched
            $input,
        );

        if (null === $res) {
            throw new RuntimeException("Error in preg_replace");
        }

        return $res;
    }


    /**
     * Check if the $xpath_expression uses an XPath function that is not in the list of allowed functions
     *
     * @param string $xpathExpression the expression to check. Should be a valid xpath expression
     * @param string[] $allowedFunctions array of string with a list of allowed function names
     * @throws \SimpleSAML\XML\Exception\RuntimeException
     */
    public static function filterXPathFunction(string $xpathExpression, array $allowedFunctions): void
    {
        /**
         * Look for the function specifier '(' and look for a function name before it.
         * Ignoring whitespace before the '(' and the function name.
         * All functions must match a string on a list of allowed function names
         */
        $matches = [];
        preg_match_all(
            /**
             * Function names are lower-case alpha (i.e. [a-z]) and can contain one or more hyphens,
             * but cannot start or end with a hyphen. To match this, we start with matching one or more
             * lower-case alpha characters, followed by zero or more atomic groups that start with a hyphen
             * and then match one or more lower-case alpha characters. This ensures that the function name
             * cannot start or end with a hyphen, but can contain one or more hyphens.
             * More than one consecutive hyphen does not match.
             *
             * Use possessive quantifiers (i.e. *+ and ++ instead of * and + respectively) to prevent backtracking
             * and thus prevent a ReDOS.

             * '/([a-z]++(?>-[a-z]++)*+)\s*+\(/'
             * (           # Start a capturing group
             *   [a-z]++   # Match one or more lower-case alpha characters
             *   (?>       # Start an atomic group (no capturing)
             *     -       # Match a hyphen
             *     [a-z]++ # Match one or more lower-case alpha characters, possessively
             *   )*+       # Repeat the atomic group zero or more times,
             * )           # End of the capturing group
             * \s*+        # Match zero or more whitespace characters, possessively
             * \(          # Match an opening parenthesis
            */

            '/([a-z]++(?>-[a-z]++)*+)\\s*+\\(/',
            $xpathExpression,
            $matches,
        );

        // Check that all the function names we found are in the list of allowed function names
        foreach ($matches[1] as $match) {
            if (!in_array($match, $allowedFunctions)) {
                throw new RuntimeException("Invalid function: '" . $match . "'");
            }
        }
    }


    /**
     * Check if the $xpath_expression uses an XPath axis that is not in the list of allowed axes
     *
     * @param string $xpathExpression the expression to check. Should be a valid xpath expression
     * @param string[] $allowedAxes array of string with a list of allowed axes names
     * @throws \SimpleSAML\XML\Exception\RuntimeException
     */
    public static function filterXPathAxis(string $xpathExpression, array $allowedAxes): void
    {
        /**
         * Look for the axis specifier '::' and look for a function name before it.
         * Ignoring whitespace before the '::' and the axis name.
         * All axes must match a string on a list of allowed axis names
         */
        $matches = [];
        preg_match_all(
            /**
             * We use the same rules for matching Axis names as we do for function names.
             * The only difference is that we match the '::' instead of the '('
             * so everything that was said about the regular expression for function names
             * applies here as well.
             *
             * Use possessive quantifiers (i.e. *+ and ++ instead of * and + respectively) to prevent backtracking
             * and thus prevent a ReDOS.
             *
             * '/([a-z]++(?>-[a-z]++)*+)\s*+::'
             * (           # Start a capturing group
             *   [a-z]++   # Match one or more lower-case alpha characters
             *   (?>       # Start an atomic group (no capturing)
             *     -       # Match a hyphen
             *     [a-z]++ # Match one or more lower-case alpha characters, possessively
             *   )*+       # Repeat the atomic group zero or more times,
             * )           # End of the capturing group
             * \s*+        # Match zero or more whitespace characters, possessively
             * \(          # Match an opening parenthesis
            */

            '/([a-z]++(?>-[a-z]++)*+)\\s*+::/',
            $xpathExpression,
            $matches,
        );

        // Check that all the axes names we found are in the list of allowed axes names
        foreach ($matches[1] as $match) {
            if (!in_array($match, $allowedAxes)) {
                throw new RuntimeException("Invalid axis: '" . $match . "'");
            }
        }
    }
}
