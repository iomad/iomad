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
 * Events tests.
 *
 * @package core_tag
 * @category test
 * @copyright 2014 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;


// Used to create a wiki page to tag.
require_once($CFG->dirroot . '/mod/wiki/locallib.php');

class core_tag_events_testcase extends advanced_testcase {

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test the tag updated event.
     */
    public function test_tag_updated() {
        $this->setAdminUser();

        // Save the system context.
        $systemcontext = context_system::instance();

        // Create a tag we are going to update.
        $tag = $this->getDataGenerator()->create_tag();

        // Store the name before we change it.
        $oldname = $tag->name;

        // Trigger and capture the event when renaming a tag.
        $sink = $this->redirectEvents();
        tag_rename($tag->id, 'newname');
        $this->assertDebuggingCalled();
        // Update the tag's name since we have renamed it.
        $tag->name = 'newname';
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\tag_updated', $event);
        $this->assertEquals($systemcontext, $event->get_context());
        $expected = array(SITEID, 'tag', 'update', 'index.php?id=' . $tag->id, $oldname . '->'. $tag->name);
        $this->assertEventLegacyLogData($expected, $event);

        // Trigger and capture the event when setting the type of a tag.
        $sink = $this->redirectEvents();
        tag_type_set($tag->id, 'official');
        $this->assertDebuggingCalled();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\tag_updated', $event);
        $this->assertEquals($systemcontext, $event->get_context());
        $expected = array(0, 'tag', 'update', 'index.php?id=' . $tag->id, $tag->name);
        $this->assertEventLegacyLogData($expected, $event);

        // Trigger and capture the event for setting the description of a tag.
        $sink = $this->redirectEvents();
        tag_description_set($tag->id, 'description', FORMAT_MOODLE);
        $this->assertDebuggingCalled();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\tag_updated', $event);
        $this->assertEquals($systemcontext, $event->get_context());
        $expected = array(0, 'tag', 'update', 'index.php?id=' . $tag->id, $tag->name);
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test the tag added event.
     */
    public function test_tag_added() {
        global $DB;

        // Create a course to tag.
        $course = $this->getDataGenerator()->create_course();

        // Trigger and capture the event for tagging a course.
        $sink = $this->redirectEvents();
        core_tag_tag::set_item_tags('core', 'course', $course->id, context_course::instance($course->id), array('A tag'));
        $events = $sink->get_events();
        $event = $events[1];

        // Check that the tag was added to the course and that the event data is valid.
        $this->assertEquals(1, $DB->count_records('tag_instance', array('component' => 'core')));
        $this->assertInstanceOf('\core\event\tag_added', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());
        $expected = array($course->id, 'coursetags', 'add', 'tag/search.php?query=A+tag', 'Course tagged');
        $this->assertEventLegacyLogData($expected, $event);

        // Create a question to tag.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, array('category' => $cat->id));

        // Trigger and capture the event for tagging a question.
        $this->assertEquals(1, $DB->count_records('tag_instance'));
        $sink = $this->redirectEvents();
        core_tag_tag::set_item_tags('core_question', 'question', $question->id,
            context::instance_by_id($cat->contextid), array('A tag'));
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the tag was added to the question and the event data is valid.
        $this->assertEquals(1, $DB->count_records('tag_instance', array('component' => 'core')));
        $this->assertInstanceOf('\core\event\tag_added', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
        $expected = null;
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test the tag removed event.
     */
    public function test_tag_removed() {
        global $DB;

        $this->setAdminUser();

        // Create a course to tag.
        $course = $this->getDataGenerator()->create_course();

        // Create a wiki page to tag.
        $wikigenerator = $this->getDataGenerator()->get_plugin_generator('mod_wiki');
        $wiki = $wikigenerator->create_instance(array('course' => $course->id));
        $subwikiid = wiki_add_subwiki($wiki->id, 0);
        $wikipageid = wiki_create_page($subwikiid, 'Title', FORMAT_HTML, '2');

        // Create the tag.
        $tag = $this->getDataGenerator()->create_tag();

        // Assign a tag to a course.
        core_tag_tag::add_item_tag('core', 'course', $course->id, context_course::instance($course->id), $tag->rawname);

        // Trigger and capture the event for untagging a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_item_tag('core', 'course', $course->id, $tag->rawname);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the tag was removed from the course and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag_instance'));
        $this->assertInstanceOf('\core\event\tag_removed', $event);
        $this->assertEquals(context_course::instance($course->id), $event->get_context());

        // Create the tag.
        $tag = $this->getDataGenerator()->create_tag();

        // Assign a tag to a wiki this time.
        core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $wikipageid, context_module::instance($wiki->cmid), $tag->rawname);

        // Trigger and capture the event for deleting this tag instance.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_item_tag('mod_wiki', 'wiki_pages', $wikipageid, $tag->rawname);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that tag was removed from the wiki page and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag_instance'));
        $this->assertInstanceOf('\core\event\tag_removed', $event);
        $this->assertEquals(context_module::instance($wiki->cmid), $event->get_context());

        // Create a tag again - the other would have been deleted since there were no more instances associated with it.
        $tag = $this->getDataGenerator()->create_tag();

        // Assign a tag to the wiki again.
        core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $wikipageid, context_module::instance($wiki->cmid), $tag->rawname);

        // Now we want to delete this tag, and because there is only one tag instance
        // associated with it, it should get deleted as well.
        $sink = $this->redirectEvents();
        core_tag_tag::delete_tags($tag->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that tag was removed from the wiki page and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag_instance'));
        $this->assertInstanceOf('\core\event\tag_removed', $event);
        $this->assertEquals(context_module::instance($wiki->cmid), $event->get_context());

        // Create a tag again - the other would have been deleted since there were no more instances associated with it.
        $tag = $this->getDataGenerator()->create_tag();

        // Assign a tag to the wiki again.
        core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $wikipageid, context_module::instance($wiki->cmid), $tag->rawname);

        // Delete all tag instances for this wiki instance.
        $sink = $this->redirectEvents();
        core_tag_tag::delete_instances('mod_wiki', 'wiki_pages', context_module::instance($wiki->cmid)->id);
        $events = $sink->get_events();
        $event = reset($events);

        // Check that tag was removed from the wiki page and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag_instance'));
        $this->assertInstanceOf('\core\event\tag_removed', $event);
        $this->assertEquals(context_module::instance($wiki->cmid), $event->get_context());

        // Create another wiki.
        $wiki2 = $wikigenerator->create_instance(array('course' => $course->id));
        $subwikiid2 = wiki_add_subwiki($wiki2->id, 0);
        $wikipageid2 = wiki_create_page($subwikiid2, 'Title', FORMAT_HTML, '2');

        // Assign a tag to both wiki pages.
        core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $wikipageid, context_module::instance($wiki->cmid), $tag->rawname);
        core_tag_tag::add_item_tag('mod_wiki', 'wiki_pages', $wikipageid2, context_module::instance($wiki2->cmid), $tag->rawname);

        // Now remove all tag_instances associated with all wikis.
        $sink = $this->redirectEvents();
        core_tag_tag::delete_instances('mod_wiki');
        $events = $sink->get_events();

        // There will be two events - one for each wiki instance removed.
        $this->assertCount(2, $events);
        $contexts = [context_module::instance($wiki->cmid), context_module::instance($wiki2->cmid)];
        $this->assertNotEquals($events[0]->contextid, $events[1]->contextid);

        // Check that the tags were removed from the wiki pages.
        $this->assertEquals(0, $DB->count_records('tag_instance'));

        // Check the first event data is valid.
        $this->assertInstanceOf('\core\event\tag_removed', $events[0]);
        $this->assertContains($events[0]->get_context(), $contexts);

        // Check that the second event data is valid.
        $this->assertInstanceOf('\core\event\tag_removed', $events[1]);
        $this->assertContains($events[1]->get_context(), $contexts);
    }

    /**
     * Test the tag flagged event.
     */
    public function test_tag_flagged() {
        global $DB;

        $this->setAdminUser();

        // Create tags we are going to flag.
        $tag = $this->getDataGenerator()->create_tag();
        $tag2 = $this->getDataGenerator()->create_tag();

        // Trigger and capture the event for setting the flag of a tag.
        $sink = $this->redirectEvents();
        tag_set_flag($tag->id);
        $this->assertDebuggingCalled();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the flag was updated.
        $tag = $DB->get_record('tag', array('id' => $tag->id));
        $this->assertEquals(1, $tag->flag);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\tag_flagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
        $expected = array(SITEID, 'tag', 'flag', 'index.php?id=' . $tag->id, $tag->id, '', '2');
        $this->assertEventLegacyLogData($expected, $event);

        // Unset the flag for both (though by default tag2 should have been created with 0 already).
        tag_unset_flag(array($tag->id, $tag2->id));
        $this->assertDebuggingCalled();

        // Trigger and capture the event for setting the flag for multiple tags.
        $sink = $this->redirectEvents();
        tag_set_flag(array($tag->id, $tag2->id));
        $this->assertDebuggingCalled();
        $events = $sink->get_events();

        // Check that the flags were updated.
        $tag = $DB->get_record('tag', array('id' => $tag->id));
        $this->assertEquals(1, $tag->flag);
        $tag2 = $DB->get_record('tag', array('id' => $tag2->id));
        $this->assertEquals(1, $tag2->flag);

        // Confirm the events.
        $event = $events[0];
        $this->assertInstanceOf('\core\event\tag_flagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
        $expected = array(SITEID, 'tag', 'flag', 'index.php?id=' . $tag->id, $tag->id, '', '2');
        $this->assertEventLegacyLogData($expected, $event);

        $event = $events[1];
        $this->assertInstanceOf('\core\event\tag_flagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
        $expected = array(SITEID, 'tag', 'flag', 'index.php?id=' . $tag2->id, $tag2->id, '', '2');
        $this->assertEventLegacyLogData($expected, $event);
    }

    /**
     * Test the tag unflagged event.
     */
    public function test_tag_unflagged() {
        global $DB;

        $this->setAdminUser();

        // Create tags we are going to unflag.
        $tag = $this->getDataGenerator()->create_tag();
        $tag2 = $this->getDataGenerator()->create_tag();

        // Flag it.
        tag_set_flag($tag->id);
        $this->assertDebuggingCalled();

        // Trigger and capture the event for unsetting the flag of a tag.
        $sink = $this->redirectEvents();
        tag_unset_flag($tag->id);
        $this->assertDebuggingCalled();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the flag was updated.
        $tag = $DB->get_record('tag', array('id' => $tag->id));
        $this->assertEquals(0, $tag->flag);

        // Check that the event data is valid.
        $this->assertInstanceOf('\core\event\tag_unflagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Set the flag back for both.
        tag_set_flag(array($tag->id, $tag2->id));
        $this->assertDebuggingCalled();

        // Trigger and capture the event for unsetting the flag for multiple tags.
        $sink = $this->redirectEvents();
        tag_unset_flag(array($tag->id, $tag2->id));
        $this->assertDebuggingCalled();
        $events = $sink->get_events();

        // Check that the flags were updated.
        $tag = $DB->get_record('tag', array('id' => $tag->id));
        $this->assertEquals(0, $tag->flag);
        $tag2 = $DB->get_record('tag', array('id' => $tag2->id));
        $this->assertEquals(0, $tag2->flag);

        // Confirm the events.
        $event = $events[0];
        $this->assertInstanceOf('\core\event\tag_unflagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        $event = $events[1];
        $this->assertInstanceOf('\core\event\tag_unflagged', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
    }

    /**
     * Test the tag deleted event
     */
    public function test_tag_deleted() {
        global $DB;

        $this->setAdminUser();

        // Create a course and a user.
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();

        // Create tag we are going to delete.
        $tag = $this->getDataGenerator()->create_tag();

        // Trigger and capture the event for deleting a tag.
        $sink = $this->redirectEvents();
        tag_delete($tag->id);
        $this->assertDebuggingCalled();
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the tag was deleted and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_deleted', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Create two tags we are going to delete to ensure passing multiple tags work.
        $tag = $this->getDataGenerator()->create_tag();
        $tag2 = $this->getDataGenerator()->create_tag();

        // Trigger and capture the events for deleting multiple tags.
        $sink = $this->redirectEvents();
        tag_delete(array($tag->id, $tag2->id));
        $this->assertDebuggingCalled();
        $events = $sink->get_events();

        // Check that the tags were deleted and the events data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        foreach ($events as $event) {
            $this->assertInstanceOf('\core\event\tag_deleted', $event);
            $this->assertEquals(context_system::instance(), $event->get_context());
        }

        // Add a tag instance to a course.
        core_tag_tag::add_item_tag('core', 'course', $course->id, context_course::instance($course->id), 'cat', $user->id);

        // Trigger and capture the event for deleting a personal tag for a user for a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_item_tag('core', 'course', $course->id, 'cat', $user->id);
        $events = $sink->get_events();
        $event = $events[1];

        // Check that the tag was deleted and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_deleted', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Add the tag instance to the course again as it was deleted.
        core_tag_tag::add_item_tag('core', 'course', $course->id, context_course::instance($course->id), 'dog', $user->id);

        // Trigger and capture the event for deleting all tags in a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_all_item_tags('core', 'course', $course->id);
        $events = $sink->get_events();
        $event = $events[1];

        // Check that the tag was deleted and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_deleted', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Add multiple tag instances now and check that it still works.
        core_tag_tag::set_item_tags('core', 'course', $course->id, context_course::instance($course->id),
            array('fish', 'hamster'), $user->id);

        // Trigger and capture the event for deleting all tags in a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_all_item_tags('core', 'course', $course->id);
        $events = $sink->get_events();
        $events = array($events[1], $events[3]);

        // Check that the tags were deleted and the events data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        foreach ($events as $event) {
            $this->assertInstanceOf('\core\event\tag_deleted', $event);
            $this->assertEquals(context_system::instance(), $event->get_context());
        }
    }

    /**
     * Test the tag deleted event while calling deprecated functions.
     * Remove the test when the functions are removed.
     */
    public function test_tag_deleted_deprecated() {
        global $DB;

        $this->setAdminUser();

        // Create a course.
        $course = $this->getDataGenerator()->create_course();

        // Create another tag to delete.
        $tag = $this->getDataGenerator()->create_tag();

        // Add a tag instance to a course.
        tag_assign('course', $course->id, $tag->id, 0, 2, 'core', context_course::instance($course->id)->id);
        $this->assertDebuggingCalled();

        // Trigger and capture the event for deleting a personal tag for a user for a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_item_tag('core', 'course', $course->id, $tag->rawname);
        $events = $sink->get_events();
        $event = $events[1];

        // Check that the tag was deleted and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_deleted', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Create a new tag we are going to delete.
        $tag = $this->getDataGenerator()->create_tag();

        // Add the tag instance to the course again as it was deleted.
        tag_assign('course', $course->id, $tag->id, 0, 2, 'core', context_course::instance($course->id)->id);
        $this->assertDebuggingCalled();

        // Trigger and capture the event for deleting all tags in a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_all_item_tags('core', 'course', $course->id);
        $events = $sink->get_events();
        $event = $events[1];

        // Check that the tag was deleted and the event data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_deleted', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());

        // Create two tags we are going to delete to ensure passing multiple tags work.
        $tag = $this->getDataGenerator()->create_tag();
        $tag2 = $this->getDataGenerator()->create_tag();

        // Add multiple tag instances now and check that it still works.
        tag_assign('course', $course->id, $tag->id, 0, 2, 'core', context_course::instance($course->id)->id);
        $this->assertDebuggingCalled();
        tag_assign('course', $course->id, $tag2->id, 0, 2, 'core', context_course::instance($course->id)->id);
        $this->assertDebuggingCalled();

        // Trigger and capture the event for deleting all tags in a course.
        $sink = $this->redirectEvents();
        core_tag_tag::remove_all_item_tags('core', 'course', $course->id);
        $events = $sink->get_events();
        $events = array($events[1], $events[3]);

        // Check that the tags were deleted and the events data is valid.
        $this->assertEquals(0, $DB->count_records('tag'));
        foreach ($events as $event) {
            $this->assertInstanceOf('\core\event\tag_deleted', $event);
            $this->assertEquals(context_system::instance(), $event->get_context());
        }
    }

    /**
     * Test the tag created event.
     */
    public function test_tag_created() {
        global $DB;

        // Trigger and capture the event for creating a tag.
        $sink = $this->redirectEvents();
        core_tag_tag::create_if_missing(core_tag_area::get_collection('core', 'course'),
                array('A really awesome tag!'));
        $events = $sink->get_events();
        $event = reset($events);

        // Check that the tag was created and the event data is valid.
        $this->assertEquals(1, $DB->count_records('tag'));
        $this->assertInstanceOf('\core\event\tag_created', $event);
        $this->assertEquals(context_system::instance(), $event->get_context());
    }
}
