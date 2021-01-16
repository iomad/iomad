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
 * Data provider tests.
 *
 * @package    logstore_database
 * @category   test
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use logstore_database\privacy\provider;

require_once(__DIR__ . '/fixtures/event.php');

/**
 * Data provider testcase class.
 *
 * This testcase is almost identical to the logstore_standard testcase, aside from the
 * initialisation of the relevant logstore obviously.
 *
 * @package    logstore_database
 * @category   test
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class logstore_database_privacy_testcase extends provider_testcase {

    public function setUp() {
        global $CFG;
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.

        // Fake the settings, we will abuse the standard plugin table here...
        set_config('dbdriver', $CFG->dblibrary . '/' . $CFG->dbtype, 'logstore_database');
        set_config('dbhost', $CFG->dbhost, 'logstore_database');
        set_config('dbuser', $CFG->dbuser, 'logstore_database');
        set_config('dbpass', $CFG->dbpass, 'logstore_database');
        set_config('dbname', $CFG->dbname, 'logstore_database');
        set_config('dbtable', $CFG->prefix . 'logstore_standard_log', 'logstore_database');
        if (!empty($CFG->dboptions['dbpersist'])) {
            set_config('dbpersist', 1, 'logstore_database');
        } else {
            set_config('dbpersist', 0, 'logstore_database');
        }
        if (!empty($CFG->dboptions['dbsocket'])) {
            set_config('dbsocket', $CFG->dboptions['dbsocket'], 'logstore_database');
        } else {
            set_config('dbsocket', '', 'logstore_database');
        }
        if (!empty($CFG->dboptions['dbport'])) {
            set_config('dbport', $CFG->dboptions['dbport'], 'logstore_database');
        } else {
            set_config('dbport', '', 'logstore_database');
        }
        if (!empty($CFG->dboptions['dbschema'])) {
            set_config('dbschema', $CFG->dboptions['dbschema'], 'logstore_database');
        } else {
            set_config('dbschema', '', 'logstore_database');
        }
        if (!empty($CFG->dboptions['dbcollation'])) {
            set_config('dbcollation', $CFG->dboptions['dbcollation'], 'logstore_database');
        } else {
            set_config('dbcollation', '', 'logstore_database');
        }
        if (!empty($CFG->dboptions['dbhandlesoptions'])) {
            set_config('dbhandlesoptions', $CFG->dboptions['dbhandlesoptions'], 'logstore_database');
        } else {
            set_config('dbhandlesoptions', false, 'logstore_database');
        }
    }

    public function test_get_contexts_for_userid() {
        $admin = \core_user::get_user(2);
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();

        $c1 = $this->getDataGenerator()->create_course();
        $cm1 = $this->getDataGenerator()->create_module('url', ['course' => $c1]);
        $c2 = $this->getDataGenerator()->create_course();
        $cm2 = $this->getDataGenerator()->create_module('url', ['course' => $c2]);

        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $cm1ctx = context_module::instance($cm1->cmid);
        $cm2ctx = context_module::instance($cm2->cmid);

        $this->enable_logging();
        $manager = get_log_manager(true);

        // User 1 is the author.
        $this->setUser($u1);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u1), []);
        $e = \logstore_database\event\unittest_executed::create(['context' => $cm1ctx]);
        $e->trigger();
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u1), [$cm1ctx]);

        // User 2 is the related user.
        $this->setUser(0);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u2), []);
        $e = \logstore_database\event\unittest_executed::create(['context' => $cm2ctx, 'relateduserid' => $u2->id]);
        $e->trigger();
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u2), [$cm2ctx]);

        // Admin user is the real user.
        $this->assert_contextlist_equals($this->get_contextlist_for_user($admin), []);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u3), []);
        $this->setAdminUser();
        \core\session\manager::loginas($u3->id, $sysctx);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $this->assert_contextlist_equals($this->get_contextlist_for_user($admin), [$sysctx, $c1ctx]);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u3), [$sysctx, $c1ctx]);

        // By admin user masquerading u1 related to u3.
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u1), [$cm1ctx]);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u3), [$sysctx, $c1ctx]);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($admin), [$sysctx, $c1ctx]);
        $this->setAdminUser();
        \core\session\manager::loginas($u1->id, context_system::instance());
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx, 'relateduserid' => $u3->id]);
        $e->trigger();
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u1), [$sysctx, $cm1ctx, $c2ctx]);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($u3), [$sysctx, $c1ctx, $c2ctx]);
        $this->assert_contextlist_equals($this->get_contextlist_for_user($admin), [$sysctx, $c1ctx, $c2ctx]);
    }

    public function test_delete_data_for_user() {
        global $DB;
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $this->enable_logging();
        $manager = get_log_manager(true);

        // User 1 is the author.
        $this->setUser($u1);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx]);
        $e->trigger();

        // User 2 is the author.
        $this->setUser($u2);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx]);
        $e->trigger();

        // Confirm data present.
        $this->assertTrue($DB->record_exists('logstore_standard_log', ['userid' => $u1->id, 'contextid' => $c1ctx->id]));
        $this->assertEquals(3, $DB->count_records('logstore_standard_log', ['userid' => $u1->id]));
        $this->assertEquals(2, $DB->count_records('logstore_standard_log', ['userid' => $u2->id]));

        // Delete all the things!
        provider::delete_data_for_user(new approved_contextlist($u1, 'logstore_database', [$c1ctx->id]));
        $this->assertFalse($DB->record_exists('logstore_standard_log', ['userid' => $u1->id, 'contextid' => $c1ctx->id]));
        $this->assertEquals(1, $DB->count_records('logstore_standard_log', ['userid' => $u1->id]));
        $this->assertEquals(2, $DB->count_records('logstore_standard_log', ['userid' => $u2->id]));
    }

    public function test_delete_data_for_all_users_in_context() {
        global $DB;
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $c1 = $this->getDataGenerator()->create_course();
        $c2 = $this->getDataGenerator()->create_course();
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);

        $this->enable_logging();
        $manager = get_log_manager(true);

        // User 1 is the author.
        $this->setUser($u1);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx]);
        $e->trigger();

        // User 2 is the author.
        $this->setUser($u2);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx]);
        $e->trigger();
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx]);
        $e->trigger();

        // Confirm data present.
        $this->assertTrue($DB->record_exists('logstore_standard_log', ['contextid' => $c1ctx->id]));
        $this->assertEquals(3, $DB->count_records('logstore_standard_log', ['userid' => $u1->id]));
        $this->assertEquals(2, $DB->count_records('logstore_standard_log', ['userid' => $u2->id]));

        // Delete all the things!
        provider::delete_data_for_all_users_in_context($c1ctx);
        $this->assertFalse($DB->record_exists('logstore_standard_log', ['contextid' => $c1ctx->id]));
        $this->assertEquals(1, $DB->count_records('logstore_standard_log', ['userid' => $u1->id]));
        $this->assertEquals(1, $DB->count_records('logstore_standard_log', ['userid' => $u2->id]));
    }

    public function test_export_data_for_user() {
        $admin = \core_user::get_user(2);
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $u4 = $this->getDataGenerator()->create_user();
        $c1 = $this->getDataGenerator()->create_course();
        $cm1 = $this->getDataGenerator()->create_module('url', ['course' => $c1]);
        $c2 = $this->getDataGenerator()->create_course();
        $cm2 = $this->getDataGenerator()->create_module('url', ['course' => $c2]);
        $sysctx = context_system::instance();
        $c1ctx = context_course::instance($c1->id);
        $c2ctx = context_course::instance($c2->id);
        $cm1ctx = context_module::instance($cm1->cmid);
        $cm2ctx = context_module::instance($cm2->cmid);

        $path = [get_string('privacy:path:logs', 'tool_log'), get_string('pluginname', 'logstore_database')];
        $this->enable_logging();
        $manager = get_log_manager(true);

        // User 1 is the author.
        $this->setUser($u1);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx, 'other' => ['i' => 0]]);
        $e->trigger();

        // User 2 is related.
        $this->setUser(0);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx, 'relateduserid' => $u2->id,
            'other' => ['i' => 1]]);
        $e->trigger();

        // Admin user masquerades u3, which is related to u4.
        $this->setAdminUser();
        \core\session\manager::loginas($u3->id, $sysctx);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c1ctx, 'relateduserid' => $u4->id,
            'other' => ['i' => 2]]);
        $e->trigger();

        // Confirm data present for u1.
        provider::export_user_data(new approved_contextlist($u1, 'logstore_database', [$c2ctx->id, $c1ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertEmpty($data);
        $data = writer::with_context($c1ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_you']);
        $this->assertSame(0, $data->logs[0]['other']['i']);

        // Confirm data present for u2.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u2, 'logstore_database', [$c2ctx->id, $c1ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertEmpty($data);
        $data = writer::with_context($c1ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(false), $data->logs[0]['author_of_the_action_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['related_user_was_you']);
        $this->assertSame(1, $data->logs[0]['other']['i']);

        // Confirm data present for u3.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u3, 'logstore_database', [$c2ctx->id, $c1ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertEmpty($data);
        $data = writer::with_context($c1ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_you']);
        $this->assertEquals(transform::yesno(false), $data->logs[0]['related_user_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_masqueraded']);
        $this->assertEquals(transform::yesno(false), $data->logs[0]['masquerading_user_was_you']);
        $this->assertSame(2, $data->logs[0]['other']['i']);

        // Confirm data present for u4.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u4, 'logstore_database', [$c2ctx->id, $c1ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertEmpty($data);
        $data = writer::with_context($c1ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(false), $data->logs[0]['author_of_the_action_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['related_user_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_masqueraded']);
        $this->assertEquals(transform::yesno(false), $data->logs[0]['masquerading_user_was_you']);
        $this->assertSame(2, $data->logs[0]['other']['i']);

        // Add anonymous events.
        $this->setUser($u1);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx, 'relateduserid' => $u2->id,
            'anonymous' => true]);
        $e->trigger();
        $this->setAdminUser();
        \core\session\manager::loginas($u3->id, $sysctx);
        $e = \logstore_database\event\unittest_executed::create(['context' => $c2ctx, 'relateduserid' => $u4->id,
            'anonymous' => true]);
        $e->trigger();

        // Confirm data present for u1.
        provider::export_user_data(new approved_contextlist($u1, 'logstore_database', [$c2ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['action_was_done_anonymously']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_you']);

        // Confirm data present for u2.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u2, 'logstore_database', [$c2ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['action_was_done_anonymously']);
        $this->assertArrayNotHasKey('author_of_the_action_was_you', $data->logs[0]);
        $this->assertArrayNotHasKey('authorid', $data->logs[0]);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['related_user_was_you']);

        // Confirm data present for u3.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u3, 'logstore_database', [$c2ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['action_was_done_anonymously']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_masqueraded']);
        $this->assertArrayNotHasKey('masquerading_user_was_you', $data->logs[0]);
        $this->assertArrayNotHasKey('masqueradinguserid', $data->logs[0]);

        // Confirm data present for u4.
        writer::reset();
        provider::export_user_data(new approved_contextlist($u4, 'logstore_database', [$c2ctx->id]));
        $data = writer::with_context($c2ctx)->get_data($path);
        $this->assertCount(1, $data->logs);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['action_was_done_anonymously']);
        $this->assertArrayNotHasKey('author_of_the_action_was_you', $data->logs[0]);
        $this->assertArrayNotHasKey('authorid', $data->logs[0]);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['related_user_was_you']);
        $this->assertEquals(transform::yesno(true), $data->logs[0]['author_of_the_action_was_masqueraded']);
        $this->assertArrayNotHasKey('masquerading_user_was_you', $data->logs[0]);
        $this->assertArrayNotHasKey('masqueradinguserid', $data->logs[0]);
    }

    /**
     * Assert the content of a context list.
     *
     * @param contextlist $contextlist The collection.
     * @param array $expected List of expected contexts or IDs.
     * @return void
     */
    protected function assert_contextlist_equals($contextlist, array $expected) {
        $expectedids = array_map(function($context) {
            if (is_object($context)) {
                return $context->id;
            }
            return $context;
        }, $expected);
        $contextids = array_map('intval', $contextlist->get_contextids());
        sort($contextids);
        sort($expectedids);
        $this->assertEquals($expectedids, $contextids);
    }

    /**
     * Enable logging.
     *
     * @return void
     */
    protected function enable_logging() {
        set_config('enabled_stores', 'logstore_database', 'tool_log');
        set_config('buffersize', 0, 'logstore_database');
        set_config('logguests', 1, 'logstore_database');
        get_log_manager(true);
    }

    /**
     * Get the contextlist for a user.
     *
     * @param object $user The user.
     * @return contextlist
     */
    protected function get_contextlist_for_user($user) {
        $contextlist = new contextlist();
        provider::add_contexts_for_userid($contextlist, $user->id);
        return $contextlist;
    }
}
