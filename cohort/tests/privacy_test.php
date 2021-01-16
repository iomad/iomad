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
 * Base class for unit tests for core_cohort.
 *
 * @package    core_cohort
 * @category   test
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use core_cohort\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;

/**
 * Unit tests for cohort\classes\privacy\provider.php
 *
 * @copyright  2018 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_cohort_testcase extends provider_testcase {

    /**
     * Basic setup for these tests.
     */
    public function setUp() {
        $this->resetAfterTest(true);
    }

    /**
     * Test getting the context for the user ID related to this plugin.
     */
    public function test_get_contexts_for_userid() {
        // Create system cohort and category cohort.
        $coursecategory = $this->getDataGenerator()->create_category();
        $coursecategoryctx = \context_coursecat::instance($coursecategory->id);
        $systemctx = \context_system::instance();
        $categorycohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $coursecategoryctx->id,
                'name' => 'Category cohort 1',
            ]);
        $systemcohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $systemctx->id,
                'name' => 'System cohort 1'
            ]);

        // Create user and add to the system and category cohorts.
        $user = $this->getDataGenerator()->create_user();
        cohort_add_member($categorycohort->id, $user->id);
        cohort_add_member($systemcohort->id, $user->id);

        // User is member of 2 cohorts.
        $contextlist = provider::get_contexts_for_userid($user->id);
        $this->assertCount(2, (array) $contextlist->get_contextids());
        $this->assertContains($coursecategoryctx->id, $contextlist->get_contextids());
        $this->assertContains($systemctx->id, $contextlist->get_contextids());
    }

    /**
     * Test that data is exported correctly for this plugin.
     */
    public function test_export_user_data() {
        // Create system cohort and category cohort.
        $coursecategory = $this->getDataGenerator()->create_category();
        $coursecategoryctx = \context_coursecat::instance($coursecategory->id);
        $systemctx = \context_system::instance();
        $categorycohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $coursecategoryctx->id,
                'name' => 'Category cohort 1',
            ]);
        $systemcohort1 = $this->getDataGenerator()->create_cohort([
                'contextid' => $systemctx->id,
                'name' => 'System cohort 1'
            ]);
        $systemcohort2 = $this->getDataGenerator()->create_cohort([
                'contextid' => $systemctx->id,
                'name' => 'System cohort 2'
            ]);

        // Create user and add to the system and category cohorts.
        $user = $this->getDataGenerator()->create_user();
        cohort_add_member($categorycohort->id, $user->id);
        cohort_add_member($systemcohort1->id, $user->id);
        cohort_add_member($systemcohort2->id, $user->id);

        // Validate system cohort exported data.
        $writer = writer::with_context($systemctx);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($user->id, $systemctx, 'core_cohort');
        $data = $writer->get_related_data([], 'cohort');
        $this->assertCount(2, $data);

        // Validate category cohort exported data.
        $writer = writer::with_context($coursecategoryctx);
        $this->assertFalse($writer->has_any_data());
        $this->export_context_data_for_user($user->id, $coursecategoryctx, 'core_cohort');
        $data = $writer->get_related_data([], 'cohort');
        $this->assertCount(1, $data);
        $this->assertEquals($categorycohort->name, reset($data)->name);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        // Create system cohort and category cohort.
        $coursecategory = $this->getDataGenerator()->create_category();
        $coursecategoryctx = \context_coursecat::instance($coursecategory->id);
        $systemctx = \context_system::instance();
        $categorycohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $coursecategoryctx->id,
                'name' => 'Category cohort 1',
                'idnumber' => '',
                'description' => ''
            ]);
        $systemcohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $systemctx->id,
                'name' => 'System cohort 1'
            ]);

        // Create user and add to the system and category cohorts.
        $user = $this->getDataGenerator()->create_user();
        cohort_add_member($categorycohort->id, $user->id);
        cohort_add_member($systemcohort->id, $user->id);

        // Before deletion, we should have 2 entries in the cohort_members table.
        $count = $DB->count_records('cohort_members');
        $this->assertEquals(2, $count);

        // Delete data based on system context.
        provider::delete_data_for_all_users_in_context($systemctx);

        // After deletion, the cohort_members entries should have been deleted.
        $count = $DB->count_records('cohort_members');
        $this->assertEquals(1, $count);

        // Delete data based on category context.
        provider::delete_data_for_all_users_in_context($coursecategoryctx);

        // After deletion, the cohort_members entries should have been deleted.
        $count = $DB->count_records('cohort_members');
        $this->assertEquals(0, $count);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;

        // Create system cohort and category cohort.
        $coursecategory = $this->getDataGenerator()->create_category();
        $coursecategoryctx = \context_coursecat::instance($coursecategory->id);
        $systemctx = \context_system::instance();
        $categorycohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $coursecategoryctx->id,
                'name' => 'Category cohort 1',
                'idnumber' => '',
                'description' => ''
            ]);
        $systemcohort = $this->getDataGenerator()->create_cohort([
                'contextid' => $systemctx->id,
                'name' => 'System cohort 1'
            ]);

        // Create user and add to the system and category cohorts.
        $user1 = $this->getDataGenerator()->create_user();
        cohort_add_member($categorycohort->id, $user1->id);
        cohort_add_member($systemcohort->id, $user1->id);

        // Create another user and add to the system and category cohorts.
        $user2 = $this->getDataGenerator()->create_user();
        cohort_add_member($categorycohort->id, $user2->id);
        cohort_add_member($systemcohort->id, $user2->id);

        // Create another user and add to the system cohort.
        $user3 = $this->getDataGenerator()->create_user();
        cohort_add_member($systemcohort->id, $user3->id);

        // Before deletion, we should have 5 entries in the cohort_members table.
        $count = $DB->count_records('cohort_members');
        $this->assertEquals(5, $count);

        $contextlist = provider::get_contexts_for_userid($user1->id);
        $contexts = [];
        $contexts[] = \context_user::instance($user1->id)->id;
        $contexts = array_merge($contexts, $contextlist->get_contextids());
        $approvedcontextlist = new approved_contextlist($user1, 'cohort', $contexts);
        provider::delete_data_for_user($approvedcontextlist);

        // After deletion, the cohort_members entries for the first student should have been deleted.
        $count = $DB->count_records('cohort_members', ['userid' => $user1->id]);
        $this->assertEquals(0, $count);
        $count = $DB->count_records('cohort_members');
        $this->assertEquals(3, $count);

        // Confirm that the cohorts hasn't been removed.
        $cohortscount = $DB->get_records('cohort');
        $this->assertCount(2, (array) $cohortscount);
    }
}
