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

namespace core\task;

use core\context;

/**
 * Unit tests for the delete_block_instances_task ad-hoc task.
 *
 * @package   core
 * @category  test
 * @copyright 2026 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(delete_block_instances_task::class)]
final class delete_block_instances_task_test extends \advanced_testcase {
    /**
     * Create some block instances with related data for testing.
     *
     * @param string $blockname The block to create instances of.
     * @param int $count Number of instances to create.
     * @return array The created block instance records.
     */
    protected function create_instances_with_data(string $blockname, int $count): array {
        global $DB;

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $coursecontext = context\course::instance($course->id);
        $user = $generator->create_user();

        $instances = [];
        for ($i = 0; $i < $count; $i++) {
            $page = new \moodle_page();
            $page->set_context($coursecontext);
            $page->set_pagetype('course-view-topics');
            $page->set_url(new \moodle_url('/course/view.php', ['id' => $course->id]));
            $page->blocks->add_region('side-pre', false);
            $page->blocks->add_block($blockname, 'side-pre', 0, false, 'course-view-*');
        }
        $instances = $DB->get_records('block_instances', ['blockname' => $blockname]);

        foreach ($instances as $instance) {
            // Ensure the block context exists.
            context\block::instance($instance->id);
            // Related data: a position and the user preferences.
            $DB->insert_record('block_positions', (object) [
                'blockinstanceid' => $instance->id,
                'contextid' => $coursecontext->id,
                'pagetype' => 'course-view-topics',
                'subpage' => '',
                'visible' => 1,
                'region' => 'side-pre',
                'weight' => 0,
            ]);
            set_user_preference('block' . $instance->id . 'hidden', 1, $user);
            set_user_preference('docked_block_instance_' . $instance->id, 1, $user);
        }

        return $instances;
    }

    /**
     * The task must delete all instances, contexts, positions and preferences of an uninstalled block.
     */
    public function test_execute_deletes_instances(): void {
        global $DB;
        $this->resetAfterTest();

        $instances = $this->create_instances_with_data('online_users', 3);
        $this->assertCount(3, $instances);
        $instanceids = array_keys($instances);

        // Simulate the block plugin having been uninstalled.
        $DB->delete_records('block', ['name' => 'online_users']);

        $task = delete_block_instances_task::instance('online_users');
        $this->expectOutputRegex('/Completed deletion of 3 instances/');
        $task->execute();

        $this->assertEquals(0, $DB->count_records('block_instances', ['blockname' => 'online_users']));
        [$insql, $inparams] = $DB->get_in_or_equal($instanceids);
        $this->assertEquals(0, $DB->count_records_select(
            'context',
            "contextlevel = " . CONTEXT_BLOCK . " AND instanceid {$insql}",
            $inparams,
        ));
        $this->assertEquals(0, $DB->count_records_select('block_positions', "blockinstanceid {$insql}", $inparams));
        foreach ($instanceids as $instanceid) {
            $this->assertEquals(0, $DB->count_records('user_preferences', ['name' => 'block' . $instanceid . 'hidden']));
            $this->assertEquals(0, $DB->count_records('user_preferences', ['name' => 'docked_block_instance_' . $instanceid]));
        }
    }

    /**
     * The task must not delete anything when the block plugin is (still or again) installed.
     */
    public function test_execute_skips_installed_block(): void {
        global $DB;
        $this->resetAfterTest();

        $instances = $this->create_instances_with_data('online_users', 2);

        $task = delete_block_instances_task::instance('online_users');
        $this->expectOutputRegex('/is installed\. Skipping/');
        $task->execute();

        $this->assertEquals(count($instances), $DB->count_records('block_instances', ['blockname' => 'online_users']));
    }

    /**
     * Block contexts holding content must be deleted through the full context deletion API.
     */
    public function test_execute_deletes_content_bearing_contexts(): void {
        global $DB;
        $this->resetAfterTest();

        $instances = $this->create_instances_with_data('online_users', 2);
        $first = reset($instances);
        $blockcontext = context\block::instance($first->id);

        // Attach a file to one of the block contexts.
        $fs = get_file_storage();
        $file = $fs->create_file_from_string([
            'contextid' => $blockcontext->id,
            'component' => 'block_online_users',
            'filearea' => 'content',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'test.txt',
        ], 'test content');
        $this->assertNotEmpty($file);

        $DB->delete_records('block', ['name' => 'online_users']);

        $task = delete_block_instances_task::instance('online_users');
        $this->expectOutputRegex('/Completed deletion of 2 instances/');
        $task->execute();

        $this->assertEquals(0, $DB->count_records('block_instances', ['blockname' => 'online_users']));
        $this->assertEquals(0, $DB->count_records('files', ['contextid' => $blockcontext->id]));
        $this->assertFalse($DB->record_exists('context', ['id' => $blockcontext->id]));
    }

    /**
     * Uninstalling a block plugin must queue the ad-hoc task instead of deleting instances inline.
     */
    public function test_uninstall_cleanup_queues_task(): void {
        global $CFG, $DB;
        $this->resetAfterTest();
        require_once($CFG->libdir . '/adminlib.php');

        $instances = $this->create_instances_with_data('online_users', 2);

        uninstall_plugin('block', 'online_users');

        // The block record is gone, but the instances are deleted by the queued task.
        $this->assertFalse($DB->record_exists('block', ['name' => 'online_users']));
        $this->assertEquals(count($instances), $DB->count_records('block_instances', ['blockname' => 'online_users']));

        $tasks = manager::get_adhoc_tasks(delete_block_instances_task::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertEquals('online_users', $task->get_custom_data()->blockname);

        $this->expectOutputRegex('/Completed deletion of 2 instances/');
        $task->execute();
        $this->assertEquals(0, $DB->count_records('block_instances', ['blockname' => 'online_users']));
    }

    /**
     * Uninstalling a block that overrides instance_delete() must run that hook for every instance.
     *
     * block_html is the only core block overriding instance_delete(): it deletes the files
     * belonging to its own block context. The hook must run synchronously during uninstall,
     * while the block code is guaranteed to still exist on disk, not later in the ad-hoc task.
     *
     * uninstall_cleanup() is called directly here rather than through uninstall_plugin(),
     * because the latter unconditionally purges all component files afterwards, which would
     * hide a missing instance_delete() call.
     */
    public function test_uninstall_cleanup_calls_instance_delete(): void {
        global $DB;
        $this->resetAfterTest();

        $instances = $this->create_instances_with_data('html', 2);

        // Attach a block_html file to each block context, as block_html does for its content.
        $fs = get_file_storage();
        $contextids = [];
        foreach ($instances as $instance) {
            $blockcontext = context\block::instance($instance->id);
            $contextids[] = $blockcontext->id;
            $fs->create_file_from_string([
                'contextid' => $blockcontext->id,
                'component' => 'block_html',
                'filearea' => 'content',
                'itemid' => 0,
                'filepath' => '/',
                'filename' => 'test.txt',
            ], 'test content');
        }
        [$ctxinsql, $ctxinparams] = $DB->get_in_or_equal($contextids);
        $filesselect = "contextid {$ctxinsql} AND component = ? AND filename <> '.'";
        $filesparams = array_merge($ctxinparams, ['block_html']);
        $this->assertEquals(2, $DB->count_records_select('files', $filesselect, $filesparams));

        \core_plugin_manager::instance()->get_plugin_info('block_html')->uninstall_cleanup();

        // The instance_delete() hook ran during uninstall: the block_html files are already
        // gone, even though the instances themselves are deleted later by the queued task.
        $this->assertEquals(0, $DB->count_records_select('files', $filesselect, $filesparams));
        $this->assertEquals(2, $DB->count_records('block_instances', ['blockname' => 'html']));

        $tasks = manager::get_adhoc_tasks(delete_block_instances_task::class);
        $this->assertCount(1, $tasks);
        $this->expectOutputRegex('/Completed deletion of 2 instances/');
        reset($tasks)->execute();
        $this->assertEquals(0, $DB->count_records('block_instances', ['blockname' => 'html']));
        [$ctxinsql2, $ctxinparams2] = $DB->get_in_or_equal($contextids);
        $this->assertEquals(0, $DB->count_records_select('context', "id {$ctxinsql2}", $ctxinparams2));
    }
}
