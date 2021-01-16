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
 * Privacy provider tests.
 *
 * @package     mod_glossary
 * @copyright   2018 Simey Lameze <simey@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use mod_glossary\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/comment/lib.php');

/**
 * Privacy provider tests class.
 *
 * @package    mod_glossary
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_glossary_privacy_provider_testcase extends \core_privacy\tests\provider_testcase {
    /** @var stdClass The student object. */
    protected $student;

    /** @var stdClass The teacher object. */
    protected $teacher;

    /** @var stdClass The glossary object. */
    protected $glossary;

    /** @var stdClass The course object. */
    protected $course;

    /** @var stdClass The plugin generator object. */
    protected $plugingenerator;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
        $this->resetAfterTest();

        global $DB;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $this->course = $course;

        $this->plugingenerator = $generator->get_plugin_generator('mod_glossary');

        // The glossary activity the user will answer.
        $glossary = $this->plugingenerator->create_instance(['course' => $course->id]);
        $this->glossary = $glossary;

        $cm = get_coursemodule_from_instance('glossary', $glossary->id);
        $context = context_module::instance($cm->id);

        // Create a student which will add an entry to a glossary.
        $student = $generator->create_user();
        $generator->enrol_user($student->id,  $course->id, 'student');
        $this->student = $student;

        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id,  $course->id, 'editingteacher');
        $this->teacher = $teacher;

        $this->setUser($student->id);
        $ge1 = $this->plugingenerator->create_content($glossary, ['concept' => 'first', 'approved' => 1]);

        // Student create a comment on a glossary entry.
        $this->setUser($student);
        $comment = $this->get_comment_object($context, $ge1->id);
        $comment->add('Hello, it\'s me!');

        // Attach tags.
        core_tag_tag::set_item_tags('mod_glossary', 'glossary_entries', $ge1->id, $context, ['Beer', 'Golf']);
    }

    /**
     * Test for provider::get_metadata().
     */
    public function test_get_metadata() {
        $collection = new collection('mod_glossary');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();
        $this->assertCount(5, $itemcollection);

        $table = reset($itemcollection);
        $this->assertEquals('glossary_entries', $table->get_name());

        $privacyfields = $table->get_privacy_fields();
        $this->assertArrayHasKey('glossaryid', $privacyfields);
        $this->assertArrayHasKey('concept', $privacyfields);
        $this->assertArrayHasKey('definition', $privacyfields);
        $this->assertArrayHasKey('attachment', $privacyfields);
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('timemodified', $privacyfields);

        $this->assertEquals('privacy:metadata:glossary_entries', $table->get_summary());
    }

    /**
     * Test for provider::get_contexts_for_userid().
     */
    public function test_get_contexts_for_userid() {
        $cm = get_coursemodule_from_instance('glossary', $this->glossary->id);

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);
        $contextforuser = $contextlist->current();
        $cmcontext = context_module::instance($cm->id);
        $this->assertEquals($cmcontext->id, $contextforuser->id);
    }

    /**
     * Test for provider::export_user_data().
     */
    public function test_export_for_context() {
        $cm = get_coursemodule_from_instance('glossary', $this->glossary->id);
        $cmcontext = context_module::instance($cm->id);

        // Export all of the data for the context.
        $writer = \core_privacy\local\request\writer::with_context($cmcontext);
        $contextlist = new \core_privacy\local\request\approved_contextlist($this->student, 'mod_glossary' , [$cmcontext->id]);

        \mod_glossary\privacy\provider::export_user_data($contextlist);
        $this->assertTrue($writer->has_any_data());
        $data = $writer->get_data([]);

        $this->assertEquals('Glossary 1', $data->name);
        $this->assertEquals('first', $data->entries[0]['concept']);
    }

    /**
     * Test for provider::delete_data_for_all_users_in_context().
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB;

        $generator = $this->getDataGenerator();
        $cm = get_coursemodule_from_instance('glossary', $this->glossary->id);
        $context = context_module::instance($cm->id);
        // Create another student who will add an entry the glossary activity.
        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $this->course->id, 'student');

        $this->setUser($student2);
        $ge3 = $this->plugingenerator->create_content($this->glossary, ['concept' => 'first', 'approved' => 1]);
        $comment = $this->get_comment_object($context, $ge3->id);
        $comment->add('User 2 comment');

        core_tag_tag::set_item_tags('mod_glossary', 'glossary_entries', $ge3->id, $context, ['Pizza', 'Noodles']);

        // As a teacher, rate student 2 entry.
        $this->setUser($this->teacher);
        $rating = $this->get_rating_object($context, $ge3->id);
        $rating->update_rating(2);

        // Before deletion, we should have 2 entries.
        $count = $DB->count_records('glossary_entries', ['glossaryid' => $this->glossary->id]);
        $this->assertEquals(2, $count);

        // Delete data based on context.
        provider::delete_data_for_all_users_in_context($context);

        // After deletion, the glossary entries for that glossary activity should have been deleted.
        $count = $DB->count_records('glossary_entries', ['glossaryid' => $this->glossary->id]);
        $this->assertEquals(0, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_glossary', 'itemtype' => 'glossary_entries',
            'itemid' => $ge3->id]);
        $this->assertEquals(0, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_glossary', 'commentarea' => 'glossary_entry',
            'itemid' => $ge3->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $commentcount);

        $ratingcount = $DB->count_records('rating', ['component' => 'mod_glossary', 'ratingarea' => 'entry',
            'itemid' => $ge3->id]);
        $this->assertEquals(0, $ratingcount);
    }

    /**
     * Test for provider::delete_data_for_user().
     */
    public function test_delete_data_for_user() {
        global $DB;
        $generator = $this->getDataGenerator();

        $student2 = $generator->create_user();
        $generator->enrol_user($student2->id, $this->course->id, 'student');

        $cm1 = get_coursemodule_from_instance('glossary', $this->glossary->id);
        $glossary2 = $this->plugingenerator->create_instance(['course' => $this->course->id]);
        $cm2 = get_coursemodule_from_instance('glossary', $glossary2->id);

        $ge1 = $this->plugingenerator->create_content($this->glossary, ['concept' => 'first user glossary entry', 'approved' => 1]);
        $this->plugingenerator->create_content($glossary2, ['concept' => 'first user second glossary entry', 'approved' => 1]);

        $context1 = context_module::instance($cm1->id);
        $context2 = context_module::instance($cm2->id);
        core_tag_tag::set_item_tags('mod_glossary', 'glossary_entries', $ge1->id, $context1, ['Parmi', 'Sushi']);

        $this->setUser($student2);
        $ge3 = $this->plugingenerator->create_content($this->glossary, ['concept' => 'second user glossary entry',
                'approved' => 1]);

        $comment = $this->get_comment_object($context1, $ge3->id);
        $comment->add('User 2 comment');

        core_tag_tag::set_item_tags('mod_glossary', 'glossary_entries', $ge3->id, $context1, ['Pizza', 'Noodles']);

        // Before deletion, we should have 3 entries, one rating and 2 tag instances.
        $count = $DB->count_records('glossary_entries', ['glossaryid' => $this->glossary->id]);
        $this->assertEquals(3, $count);
        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_glossary', 'itemtype' => 'glossary_entries',
            'itemid' => $ge3->id]);
        $this->assertEquals(2, $tagcount);

        // Create another student who will add an entry to the first glossary.
        $contextlist = new \core_privacy\local\request\approved_contextlist($student2, 'glossary',
            [$context1->id, $context2->id]);
        provider::delete_data_for_user($contextlist);

        // After deletion, the glossary entry and tags for the second student should have been deleted.
        $count = $DB->count_records('glossary_entries', ['glossaryid' => $this->glossary->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_glossary', 'itemtype' => 'glossary_entries',
                'itemid' => $ge3->id]);
        $this->assertEquals(0, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_glossary', 'commentarea' => 'glossary_entry',
                'itemid' => $ge3->id, 'userid' => $student2->id]);
        $this->assertEquals(0, $commentcount);

        // Student's 1 entries, comments and tags should not be removed.
        $count = $DB->count_records('glossary_entries', ['glossaryid' => $this->glossary->id,
                'userid' => $this->student->id]);
        $this->assertEquals(2, $count);

        $tagcount = $DB->count_records('tag_instance', ['component' => 'mod_glossary', 'itemtype' => 'glossary_entries',
            'itemid' => $ge1->id]);
        $this->assertEquals(2, $tagcount);

        $commentcount = $DB->count_records('comments', ['component' => 'mod_glossary', 'commentarea' => 'glossary_entry',
             'userid' => $this->student->id]);
        $this->assertEquals(1, $commentcount);
    }

    /**
     * Get the comment area for glossary module.
     *
     * @param context $context The context.
     * @param int $itemid The item ID.
     * @return comment
     */
    protected function get_comment_object(context $context, $itemid) {
        $args = new stdClass();

        $args->context = $context;
        $args->course = get_course(SITEID);
        $args->area = 'glossary_entry';
        $args->itemid = $itemid;
        $args->component = 'mod_glossary';
        $comment = new comment($args);
        $comment->set_post_permission(true);

        return $comment;
    }

    /**
     * Get the rating area for glossary module.
     *
     * @param context $context The context.
     * @param int $itemid The item ID.
     * @return rating object
     */
    protected function get_rating_object(context $context, $itemid) {
        global $USER;

        $ratingoptions = new stdClass;
        $ratingoptions->context = $context;
        $ratingoptions->ratingarea = 'entry';
        $ratingoptions->component = 'mod_glossary';
        $ratingoptions->itemid  = $itemid;
        $ratingoptions->scaleid = 2;
        $ratingoptions->userid  = $USER->id;
        return new rating($ratingoptions);
    }
}
