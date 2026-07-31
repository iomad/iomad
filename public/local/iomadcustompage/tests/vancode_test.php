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

/**
 * Unit tests for vancode helper class
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage;

use local_iomadcustompage\local\helpers\vancode;

defined('MOODLE_INTERNAL') || die();

/**
 * Test cases for vancode helper class
 *
 * @package    local_iomadcustompage
 * @category   test
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class vancode_test extends \advanced_testcase {
    /**
     * Test int2vancode conversion
     */
    public function test_int2vancode(): void {
        $this->assertEquals('01', vancode::int2vancode(1));
        $this->assertEquals('02', vancode::int2vancode(2));
        $this->assertEquals('0z', vancode::int2vancode(35));
        $this->assertEquals('110', vancode::int2vancode(36));
        $this->assertEquals('111', vancode::int2vancode(37));
    }

    /**
     * Test vancode2int conversion
     */
    public function test_vancode2int(): void {
        $this->assertEquals(1, vancode::vancode2int('01'));
        $this->assertEquals(2, vancode::vancode2int('02'));
        $this->assertEquals(35, vancode::vancode2int('0z'));
        $this->assertEquals(36, vancode::vancode2int('110'));
        $this->assertEquals(37, vancode::vancode2int('111'));
    }

    /**
     * Test increment_vancode
     */
    public function test_increment_vancode(): void {
        $this->assertEquals('02', vancode::increment_vancode('01'));
        $this->assertEquals('03', vancode::increment_vancode('01', 2));
        $this->assertEquals('110', vancode::increment_vancode('0z'));
        $this->assertEquals('112', vancode::increment_vancode('110', 2));
    }

    /**
     * Test increment_sortthread
     */
    public function test_increment_sortthread(): void {
        $this->assertEquals('02', vancode::increment_sortthread('01'));
        $this->assertEquals('01.02', vancode::increment_sortthread('01.01'));
        $this->assertEquals('01.02.03', vancode::increment_sortthread('01.02.02'));
        $this->assertEquals('110', vancode::increment_sortthread('0z'));
    }

    /**
     * Test get_next_child_sortthread
     */
    public function test_get_next_child_sortthread(): void {
        // First root level item.
        $this->assertEquals('01', vancode::get_next_child_sortthread(null, []));

        // Second root level item.
        $this->assertEquals('02', vancode::get_next_child_sortthread(null, ['01']));

        // First child of root item.
        $this->assertEquals('01.01', vancode::get_next_child_sortthread('01', []));

        // Second child of root item.
        $this->assertEquals('01.02', vancode::get_next_child_sortthread('01', ['01.01']));

        // Mixed existing sortthreads.
        $existing = ['01', '01.01', '01.02', '02', '02.01'];
        $this->assertEquals('03', vancode::get_next_child_sortthread(null, $existing));
        $this->assertEquals('01.03', vancode::get_next_child_sortthread('01', $existing));
        $this->assertEquals('02.02', vancode::get_next_child_sortthread('02', $existing));
    }

    /**
     * Test is_valid_sortthread
     */
    public function test_is_valid_sortthread(): void {
        $this->assertTrue(vancode::is_valid_sortthread('01'));
        $this->assertTrue(vancode::is_valid_sortthread('01.01'));
        $this->assertTrue(vancode::is_valid_sortthread('01.02.03'));
        $this->assertTrue(vancode::is_valid_sortthread('110'));

        $this->assertFalse(vancode::is_valid_sortthread(''));
        $this->assertFalse(vancode::is_valid_sortthread('1'));
        $this->assertFalse(vancode::is_valid_sortthread('01.1'));
        $this->assertFalse(vancode::is_valid_sortthread('invalid'));
    }

    /**
     * Test convert_numeric_to_vancode
     */
    public function test_convert_numeric_to_vancode(): void {
        $this->assertEquals('01', vancode::convert_numeric_to_vancode('01'));
        $this->assertEquals('01.01', vancode::convert_numeric_to_vancode('01.01'));
        $this->assertEquals('01.02.03', vancode::convert_numeric_to_vancode('01.02.03'));
        $this->assertEquals('0a', vancode::convert_numeric_to_vancode('10'));
        $this->assertEquals('01.0a', vancode::convert_numeric_to_vancode('01.10'));
    }

    /**
     * Test compare_sortthreads
     */
    public function test_compare_sortthreads(): void {
        $this->assertEquals(-1, vancode::compare_sortthreads('01', '02'));
        $this->assertEquals(1, vancode::compare_sortthreads('02', '01'));
        $this->assertEquals(0, vancode::compare_sortthreads('01', '01'));
        $this->assertEquals(-1, vancode::compare_sortthreads('01.01', '01.02'));
        $this->assertEquals(-1, vancode::compare_sortthreads('01', '01.01'));
    }

    /**
     * Test get_sortthread_depth
     */
    public function test_get_sortthread_depth(): void {
        $this->assertEquals(1, vancode::get_sortthread_depth('01'));
        $this->assertEquals(2, vancode::get_sortthread_depth('01.01'));
        $this->assertEquals(3, vancode::get_sortthread_depth('01.01.01'));
        $this->assertEquals(0, vancode::get_sortthread_depth(''));
    }

    /**
     * Test get_parent_sortthread
     */
    public function test_get_parent_sortthread(): void {
        $this->assertNull(vancode::get_parent_sortthread('01'));
        $this->assertEquals('01', vancode::get_parent_sortthread('01.01'));
        $this->assertEquals('01.01', vancode::get_parent_sortthread('01.01.01'));
    }

    /**
     * Test is_ancestor
     */
    public function test_is_ancestor(): void {
        $this->assertTrue(vancode::is_ancestor('01', '01.01'));
        $this->assertTrue(vancode::is_ancestor('01', '01.01.01'));
        $this->assertTrue(vancode::is_ancestor('01.01', '01.01.01'));

        $this->assertFalse(vancode::is_ancestor('01.01', '01'));
        $this->assertFalse(vancode::is_ancestor('01', '02'));
        $this->assertFalse(vancode::is_ancestor('01.01', '01.02'));
    }

    /**
     * Test sortthread ordering
     */
    public function test_sortthread_ordering(): void {
        $sortthreads = [
            '02',
            '01.02',
            '01',
            '01.01.02',
            '01.01',
            '01.01.01',
            '03',
        ];

        sort($sortthreads);

        $expected = [
            '01',
            '01.01',
            '01.01.01',
            '01.01.02',
            '01.02',
            '02',
            '03',
        ];

        $this->assertEquals($expected, $sortthreads);
    }

    /**
     * Test large numbers
     */
    public function test_large_numbers(): void {
        $large = 1000;
        $vancode = vancode::int2vancode($large);
        $this->assertEquals($large, vancode::vancode2int($vancode));

        // Test that vancodes sort correctly even with large numbers.
        $vancodes = [];
        for ($i = 1; $i <= 100; $i++) {
            $vancodes[] = vancode::int2vancode($i);
        }

        $sorted = $vancodes;
        sort($sorted);

        $this->assertEquals($vancodes, $sorted);
    }
}
