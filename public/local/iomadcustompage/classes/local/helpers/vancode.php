<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace local_iomadcustompage\local\helpers;

/**
 * Vancode helper class for hierarchical sortthread management
 *
 * Vancodes are a system used by Drupal for sorting hierarchical comments they provide a way of efficiently sorting
 * hierarchical structures.
 *
 * A vancode is a base 36 representation of an integer, prefixed by a base 36 digit: (length(base 36 string) - 1)
 *
 * The advantages of this format are:
 *  - It automatically sorts in the correct order without natural order sorting
 *  - It is relatively compact, meaning the db field doesn't need to be too big
 *  - It supports unlimited hierarchical depth and items per level
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class vancode {
    /**
     * Convert an integer to a 'vancode'
     *
     * @param int $int Integer to convert to a vancode. Must be < pow(36, 10)
     * @return string Vancode for the specified integer
     */
    public static function int2vancode(int $int = 0): string {
        $num = base_convert((string)$int, 10, 36);
        $length = strlen($num);
        return chr($length + ord('0') - 1) . $num;
    }

    /**
     * Convert a vancode to an integer
     *
     * @param string $char Vancode to convert. Must be <= '9zzzzzzzzzz'
     * @return int The integer representation of the specified vancode
     */
    public static function vancode2int(string $char = '00'): int {
        return (int) base_convert(substr($char, 1), 36, 10);
    }

    /**
     * Increment a vancode by N (or decrement if negative)
     *
     * Returns the vancode, incremented by the specified amount
     *
     * @param string $char Vancode to increment
     * @param int $inc Number to increment by (optional, defaults to 1)
     * @return string Vancode of $char + increment
     */
    public static function increment_vancode(string $char, int $inc = 1): string {
        return self::int2vancode(self::vancode2int($char) + $inc);
    }

    /**
     * Increment the last section of a sortthread vancode
     *
     * Examples:
     * 01 -> 02
     * 01.01 -> 01.02
     * 04.03 -> 04.04
     * 01.02.03 -> 01.02.04
     *
     * @param string $sortthread The sort thread to increment
     * @param int $inc Amount to increment by (default 1)
     * @return string Incremented sortthread
     */
    public static function increment_sortthread(string $sortthread, int $inc = 1): string {
        if (!$lastdot = strrpos($sortthread, '.')) {
            // Root level, just increment the whole thing.
            return self::increment_vancode($sortthread, $inc);
        }
        $start = substr($sortthread, 0, $lastdot + 1);
        $last = substr($sortthread, $lastdot + 1);

        // Increment the last vancode in the sequence.
        return $start . self::increment_vancode($last, $inc);
    }

    /**
     * Get the next available sortthread for a new child
     *
     * @param string|null $parentsortthread Parent's sortthread (null for root level)
     * @param array $existingsortthreads Array of existing sortthreads at the same level
     * @return string New sortthread for the child
     */
    public static function get_next_child_sortthread(?string $parentsortthread, array $existingsortthreads = []): string {
        if (empty($existingsortthreads)) {
            if ($parentsortthread === null) {
                // First top level item.
                return self::int2vancode(1);
            } else {
                // Parent has no children yet.
                return $parentsortthread . '.' . self::int2vancode(1);
            }
        }

        // Find the maximum sortthread at this level.
        $maxthread = '';
        $maxvalue = 0;
        $prefix = $parentsortthread ? $parentsortthread . '.' : '';
        $prefixlen = strlen($prefix);

        foreach ($existingsortthreads as $sortthread) {
            if (strpos($sortthread, $prefix) === 0) {
                $remainder = substr($sortthread, $prefixlen);
                // Only consider direct children (no further dots).
                if (strpos($remainder, '.') === false) {
                    $value = self::vancode2int($remainder);
                    if ($value > $maxvalue) {
                        $maxvalue = $value;
                        $maxthread = $remainder;
                    }
                }
            }
        }

        if ($maxthread === '') {
            // No children found, start with first.
            return $prefix . self::int2vancode(1);
        }

        return $prefix . self::increment_vancode($maxthread);
    }

    /**
     * Validate a sortthread format
     *
     * @param string $sortthread Sortthread to validate
     * @return bool True if valid vancode format
     */
    public static function is_valid_sortthread(string $sortthread): bool {
        if (empty($sortthread)) {
            return false;
        }

        $parts = explode('.', $sortthread);
        foreach ($parts as $part) {
            if (strlen($part) < 2) {
                return false;
            }
            // Check if it's a valid vancode format.
            $lengthchar = $part[0];
            $expectedlength = ord($lengthchar) - ord('0') + 1;
            if (strlen($part) !== $expectedlength + 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert old numeric sortthread to vancode format
     *
     * @param string $oldsortthread Old format like "01" or "01.02"
     * @return string New vancode format
     */
    public static function convert_numeric_to_vancode(string $oldsortthread): string {
        if (empty($oldsortthread)) {
            return self::int2vancode(1);
        }

        $parts = explode('.', $oldsortthread);
        $vancodeparts = [];

        foreach ($parts as $part) {
            $intval = (int) $part;
            $vancodeparts[] = self::int2vancode($intval);
        }

        return implode('.', $vancodeparts);
    }

    /**
     * Compare two sortthreads for ordering
     *
     * @param string $a First sortthread
     * @param string $b Second sortthread
     * @return int -1 if $a < $b, 0 if equal, 1 if $a > $b
     */
    public static function compare_sortthreads(string $a, string $b): int {
        $cmp = strcmp($a, $b);
        if ($cmp < 0) {
            return -1;
        }
        if ($cmp > 0) {
            return 1;
        }
        return 0;
    }

    /**
     * Get the depth level of a sortthread
     *
     * @param string $sortthread Sortthread to analyze
     * @return int Depth level (1 for root, 2 for first child, etc.)
     */
    public static function get_sortthread_depth(string $sortthread): int {
        if (empty($sortthread)) {
            return 0;
        }
        return substr_count($sortthread, '.') + 1;
    }

    /**
     * Get the parent sortthread from a child sortthread
     *
     * @param string $sortthread Child sortthread
     * @return string|null Parent sortthread or null if root level
     */
    public static function get_parent_sortthread(string $sortthread): ?string {
        $lastdot = strrpos($sortthread, '.');
        if ($lastdot === false) {
            return null; // Root level.
        }
        return substr($sortthread, 0, $lastdot);
    }

    /**
     * Check if one sortthread is an ancestor of another
     *
     * @param string $ancestor Potential ancestor sortthread
     * @param string $descendant Potential descendant sortthread
     * @return bool True if ancestor is an ancestor of descendant
     */
    public static function is_ancestor(string $ancestor, string $descendant): bool {
        return strpos($descendant, $ancestor . '.') === 0;
    }
}
