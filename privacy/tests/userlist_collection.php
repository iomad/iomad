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
 * Unit Tests for a the collection of userlists class
 *
 * @package     core_privacy
 * @category    test
 * @copyright   2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

use \core_privacy\local\request\userlist_collection;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\approved_userlist;

/**
 * Tests for the \core_privacy API's userlist collection functionality.
 *
 * @copyright   2018 Andrew Nicols <andrew@nicols.co.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userlist_collection_test extends advanced_testcase {

    /**
     * A userlist_collection should support the userlist type.
     */
    public function test_supports_userlist() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $userlist = new userlist($cut, 'core_privacy');
        $uut->add_userlist($userlist);

        $this->assertCount(1, $uut->get_userlists());
    }

    /**
     * A userlist_collection should support the approved_userlist type.
     */
    public function test_supports_approved_userlist() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $userlist = new approved_userlist($cut, 'core_privacy', [1, 2, 3]);
        $uut->add_userlist($userlist);

        $this->assertCount(1, $uut->get_userlists());
    }

    /**
     * Ensure that get_userlist_for_component returns the correct userlist.
     */
    public function test_get_userlist_for_component() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $privacy = new userlist($cut, 'core_privacy');
        $uut->add_userlist($privacy);

        $test = new userlist($cut, 'core_tests');
        $uut->add_userlist($test);

        // Note: This uses assertSame rather than assertEquals.
        // The former checks the actual object, whilst assertEquals only checks that they look the same.
        $this->assertSame($privacy, $uut->get_userlist_for_component('core_privacy'));
        $this->assertSame($test, $uut->get_userlist_for_component('core_tests'));
    }

    /**
     * Ensure that get_userlist_for_component does not die horribly when querying a non-existent component.
     */
    public function test_get_userlist_for_component_not_found() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $this->assertNull($uut->get_userlist_for_component('core_tests'));
    }

    /**
     * Ensure that a duplicate userlist in the collection throws an Exception.
     */
    public function test_duplicate_addition_throws() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $userlist = new userlist($cut, 'core_privacy');
        $uut->add_userlist($userlist);

        $this->expectException('moodle_exception');
        $uut->add_userlist($userlist);
    }

    /**
     * Ensure that the userlist_collection is countable.
     */
    public function test_countable() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $uut->add_userlist(new userlist($cut, 'core_privacy'));
        $uut->add_userlist(new userlist($cut, 'core_tests'));

        $this->assertCount(2, $uut);
    }

    /**
     * Ensure that the userlist_collection iterates over the set of userlists.
     */
    public function test_iteration() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $testdata = [];

        $privacy = new userlist($cut, 'core_privacy');
        $uut->add_userlist($privacy);
        $testdata['core_privacy'] = $privacy;

        $test = new userlist($cut, 'core_tests');
        $uut->add_userlist($test);
        $testdata['core_tests'] = $test;

        $another = new userlist($cut, 'privacy_another');
        $uut->add_userlist($another);
        $testdata['privacy_another'] = $another;

        foreach ($uut as $component => $list) {
            $this->assertEquals($testdata[$component], $list);
        }

        $this->assertCount(3, $uut);
    }

    /**
     * Test that the context is correctly returned.
     */
    public function test_get_context() {
        $cut = \context_system::instance();
        $uut = new userlist_collection($cut);

        $this->assertSame($cut, $uut->get_context());
    }
}
