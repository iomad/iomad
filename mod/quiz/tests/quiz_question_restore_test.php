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

namespace mod_quiz;

use core_question\question_reference_manager;
use mod_quiz\question\display_options;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once(__DIR__ . '/quiz_question_helper_test_trait.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Quiz backup and restore tests.
 *
 * @package    mod_quiz
 * @category   test
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_question_restore_test extends \advanced_testcase {
    use \quiz_question_helper_test_trait;

    /**
     * @var \stdClass test student user.
     */
    protected $student;

    /**
     * Called before every test.
     */
    public function setUp(): void {
        global $USER;
        parent::setUp();
        $this->setAdminUser();
        $this->course = $this->getDataGenerator()->create_course();
        $this->student = $this->getDataGenerator()->create_user();
        $this->user = $USER;
    }

    /**
     * Test a quiz backup and restore in a different course without attempts for course question bank.
     *
     * @covers \mod_quiz\question\bank\qbank_helper::get_question_structure
     */
    public function test_quiz_restore_in_a_different_course_using_course_question_bank() {
        $this->resetAfterTest();

        // Create the test quiz.
        $quiz = $this->create_test_quiz($this->course);
        $oldquizcontext = \context_module::instance($quiz->cmid);
        // Test for questions from a different context.
        $coursecontext = \context_course::instance($this->course->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->add_two_regular_questions($questiongenerator, $quiz, ['contextid' => $coursecontext->id]);
        $this->add_one_random_question($questiongenerator, $quiz, ['contextid' => $coursecontext->id]);

        // Make the backup.
        $backupid = $this->backup_quiz($quiz, $this->user);

        // Delete the current course to make sure there is no data.
        delete_course($this->course, false);

        // Check if the questions and associated data are deleted properly.
        $this->assertEquals(0, count(\mod_quiz\question\bank\qbank_helper::get_question_structure(
                $quiz->id, $oldquizcontext)));

        // Restore the course.
        $newcourse = $this->getDataGenerator()->create_course();
        $this->restore_quiz($backupid, $newcourse, $this->user);

        // Verify.
        $modules = get_fast_modinfo($newcourse->id)->get_instances_of('quiz');
        $module = reset($modules);
        $questions = \mod_quiz\question\bank\qbank_helper::get_question_structure(
                $module->instance, $module->context);
        $this->assertCount(3, $questions);
    }

    /**
     * Test a quiz backup and restore in a different course without attempts for quiz question bank.
     *
     * @covers \mod_quiz\question\bank\qbank_helper::get_question_structure
     */
    public function test_quiz_restore_in_a_different_course_using_quiz_question_bank() {
        $this->resetAfterTest();

        // Create the test quiz.
        $quiz = $this->create_test_quiz($this->course);
        // Test for questions from a different context.
        $quizcontext = \context_module::instance($quiz->cmid);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->add_two_regular_questions($questiongenerator, $quiz, ['contextid' => $quizcontext->id]);
        $this->add_one_random_question($questiongenerator, $quiz, ['contextid' => $quizcontext->id]);

        // Make the backup.
        $backupid = $this->backup_quiz($quiz, $this->user);

        // Delete the current course to make sure there is no data.
        delete_course($this->course, false);

        // Check if the questions and associated datas are deleted properly.
        $this->assertEquals(0, count(\mod_quiz\question\bank\qbank_helper::get_question_structure(
                $quiz->id, $quizcontext)));

        // Restore the course.
        $newcourse = $this->getDataGenerator()->create_course();
        $this->restore_quiz($backupid, $newcourse, $this->user);

        // Verify.
        $modules = get_fast_modinfo($newcourse->id)->get_instances_of('quiz');
        $module = reset($modules);
        $this->assertEquals(3, count(\mod_quiz\question\bank\qbank_helper::get_question_structure(
                $module->instance, $module->context)));
    }

    /**
     * Count the questions for the context.
     *
     * @param int $contextid
     * @param string $extracondition
     * @return int the number of questions.
     */
    protected function question_count(int $contextid, string $extracondition = ''): int {
        global $DB;
        return $DB->count_records_sql(
            "SELECT COUNT(q.id)
               FROM {question} q
               JOIN {question_versions} qv ON qv.questionid = q.id
               JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
               JOIN {question_categories} qc on qc.id = qbe.questioncategoryid
              WHERE qc.contextid = ?
              $extracondition", [$contextid]);
    }

    /**
     * Test if a duplicate does not duplicate questions in course question bank.
     *
     * @covers ::duplicate_module
     */
    public function test_quiz_duplicate_does_not_duplicate_course_question_bank_questions() {
        $this->resetAfterTest();
        $quiz = $this->create_test_quiz($this->course);
        // Test for questions from a different context.
        $context = \context_course::instance($this->course->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->add_two_regular_questions($questiongenerator, $quiz, ['contextid' => $context->id]);
        $this->add_one_random_question($questiongenerator, $quiz, ['contextid' => $context->id]);
        // Count the questions in course context.
        $this->assertEquals(7, $this->question_count($context->id));
        $newquiz = $this->duplicate_quiz($this->course, $quiz);
        $this->assertEquals(7, $this->question_count($context->id));
        $context = \context_module::instance($newquiz->id);
        // Count the questions in the quiz context.
        $this->assertEquals(0, $this->question_count($context->id));
    }

    /**
     * Test quiz duplicate for quiz question bank.
     *
     * @covers ::duplicate_module
     */
    public function test_quiz_duplicate_for_quiz_question_bank_questions() {
        $this->resetAfterTest();
        $quiz = $this->create_test_quiz($this->course);
        // Test for questions from a different context.
        $context = \context_module::instance($quiz->cmid);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->add_two_regular_questions($questiongenerator, $quiz, ['contextid' => $context->id]);
        $this->add_one_random_question($questiongenerator, $quiz, ['contextid' => $context->id]);
        // Count the questions in course context.
        $this->assertEquals(7, $this->question_count($context->id));
        $newquiz = $this->duplicate_quiz($this->course, $quiz);
        $this->assertEquals(7, $this->question_count($context->id));
        $context = \context_module::instance($newquiz->id);
        // Count the questions in the quiz context.
        $this->assertEquals(7, $this->question_count($context->id));
    }

    /**
     * Test quiz restore with attempts.
     *
     * @covers \mod_quiz\question\bank\qbank_helper::get_question_structure
     */
    public function test_quiz_restore_with_attempts() {
        $this->resetAfterTest();

        // Create a quiz.
        $quiz = $this->create_test_quiz($this->course);
        $quizcontext = \context_module::instance($quiz->cmid);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $this->add_two_regular_questions($questiongenerator, $quiz, ['contextid' => $quizcontext->id]);
        $this->add_one_random_question($questiongenerator, $quiz, ['contextid' => $quizcontext->id]);

        // Attempt it as a student, and check.
        /** @var \question_usage_by_activity $quba */
        [, $quba] = $this->attempt_quiz($quiz, $this->student);
        $this->assertEquals(3, $quba->question_count());
        $this->assertCount(1, quiz_get_user_attempts($quiz->id, $this->student->id));

        // Make the backup.
        $backupid = $this->backup_quiz($quiz, $this->user);

        // Delete the current course to make sure there is no data.
        delete_course($this->course, false);

        // Restore the backup.
        $newcourse = $this->getDataGenerator()->create_course();
        $this->restore_quiz($backupid, $newcourse, $this->user);

        // Verify.
        $modules = get_fast_modinfo($newcourse->id)->get_instances_of('quiz');
        $module = reset($modules);
        $this->assertCount(1, quiz_get_user_attempts($module->instance, $this->student->id));
        $this->assertCount(3, \mod_quiz\question\bank\qbank_helper::get_question_structure(
                $module->instance, $module->context));
    }

    /**
     * Test pre 4.0 quiz restore for regular questions.
     *
     * Also, for efficiency, tests restore of the review options.
     *
     * @covers \restore_quiz_activity_structure_step::process_quiz_question_legacy_instance
     */
    public function test_pre_4_quiz_restore_for_regular_questions() {
        global $USER, $DB;
        $this->resetAfterTest();
        $backupid = 'abc';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            __DIR__ . "/fixtures/moodle_28_quiz.mbz", $backuppath);

        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
        $newcourseid = \restore_dbops::create_new_course('Test fullname', 'Test shortname', $categoryid);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
            \backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Get the information about the resulting course and check that it is set up correctly.
        $modinfo = get_fast_modinfo($newcourseid);
        $quiz = array_values($modinfo->get_instances_of('quiz'))[0];
        $quizobj = \mod_quiz\quiz_settings::create($quiz->instance);
        $structure = structure::create_for_quiz($quizobj);

        // Verify the restored review options setting.
        $this->assertEquals(display_options::DURING |
                    display_options::IMMEDIATELY_AFTER |
                    display_options::LATER_WHILE_OPEN |
                    display_options::AFTER_CLOSE, $quizobj->get_quiz()->reviewmaxmarks);

        // Are the correct slots returned?
        $slots = $structure->get_slots();
        $this->assertCount(2, $slots);

        $quizobj->preload_questions();
        $quizobj->load_questions();
        $questions = $quizobj->get_questions();
        $this->assertCount(2, $questions);

        // Count the questions in quiz qbank.
        $this->assertEquals(2, $this->question_count($quizobj->get_context()->id));
    }

    /**
     * Test pre 4.0 quiz restore for random questions.
     *
     * @covers \restore_quiz_activity_structure_step::process_quiz_question_legacy_instance
     */
    public function test_pre_4_quiz_restore_for_random_questions() {
        global $USER, $DB;
        $this->resetAfterTest();

        $backupid = 'abc';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            __DIR__ . "/fixtures/random_by_tag_quiz.mbz", $backuppath);

        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
        $newcourseid = \restore_dbops::create_new_course('Test fullname', 'Test shortname', $categoryid);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
            \backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Get the information about the resulting course and check that it is set up correctly.
        $modinfo = get_fast_modinfo($newcourseid);
        $quiz = array_values($modinfo->get_instances_of('quiz'))[0];
        $quizobj = \mod_quiz\quiz_settings::create($quiz->instance);
        $structure = structure::create_for_quiz($quizobj);

        // Are the correct slots returned?
        $slots = $structure->get_slots();
        $this->assertCount(1, $slots);

        $quizobj->preload_questions();
        $quizobj->load_questions();
        $questions = $quizobj->get_questions();
        $this->assertCount(1, $questions);

        // Count the questions for course question bank.
        $this->assertEquals(6, $this->question_count(\context_course::instance($newcourseid)->id));
        $this->assertEquals(6, $this->question_count(\context_course::instance($newcourseid)->id,
            "AND q.qtype <> 'random'"));

        // Count the questions in quiz qbank.
        $this->assertEquals(0, $this->question_count($quizobj->get_context()->id));
    }

    /**
     * Test pre 4.0 quiz restore for random question tags.
     *
     * @covers \restore_quiz_activity_structure_step::process_quiz_question_legacy_instance
     */
    public function test_pre_4_quiz_restore_for_random_question_tags() {
        global $USER, $DB;
        $this->resetAfterTest();
        $randomtags = [
            '1' => ['first question', 'one', 'number one'],
            '2' => ['first question', 'one', 'number one'],
            '3' => ['one', 'number one', 'second question'],
        ];
        $backupid = 'abc';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
            __DIR__ . "/fixtures/moodle_311_quiz.mbz", $backuppath);

        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
        $newcourseid = \restore_dbops::create_new_course('Test fullname', 'Test shortname', $categoryid);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
            \backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Get the information about the resulting course and check that it is set up correctly.
        $modinfo = get_fast_modinfo($newcourseid);
        $quiz = array_values($modinfo->get_instances_of('quiz'))[0];
        $quizobj = \mod_quiz\quiz_settings::create($quiz->instance);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);

        // Count the questions in quiz qbank.
        $context = \context_module::instance(get_coursemodule_from_instance("quiz", $quizobj->get_quizid(), $newcourseid)->id);
        $this->assertEquals(2, $this->question_count($context->id));

        // Are the correct slots returned?
        $slots = $structure->get_slots();
        $this->assertCount(3, $slots);

        // Check if the tags match with the actual restored data.
        foreach ($slots as $slot) {
            $setreference = $DB->get_record('question_set_references',
                ['itemid' => $slot->id, 'component' => 'mod_quiz', 'questionarea' => 'slot']);
            $filterconditions = json_decode($setreference->filtercondition);
            $tags = [];
            foreach ($filterconditions->tags as $tagstring) {
                $tag = explode(',', $tagstring);
                $tags[] = $tag[1];
            }
            $this->assertEquals([], array_diff($randomtags[$slot->slot], $tags));
        }

    }

    /**
     * Test pre 4.0 quiz restore for random question used on multiple quizzes.
     *
     * @covers \restore_quiz_activity_structure_step::process_quiz_question_legacy_instance
     */
    public function test_pre_4_quiz_restore_shared_random_question() {
        global $USER, $DB;
        $this->resetAfterTest();

        $backupid = 'abc';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
                __DIR__ . "/fixtures/pre-40-shared-random-question.mbz", $backuppath);

        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
        $newcourseid = \restore_dbops::create_new_course('Test fullname', 'Test shortname', $categoryid);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
                \backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Get the information about the resulting course and check that it is set up correctly.
        // Each quiz should contain an instance of the random question.
        $modinfo = get_fast_modinfo($newcourseid);
        $quizzes = $modinfo->get_instances_of('quiz');
        $this->assertCount(2, $quizzes);
        foreach ($quizzes as $quiz) {
            $quizobj = \mod_quiz\quiz_settings::create($quiz->instance);
            $structure = structure::create_for_quiz($quizobj);

            // Are the correct slots returned?
            $slots = $structure->get_slots();
            $this->assertCount(1, $slots);

            $quizobj->preload_questions();
            $quizobj->load_questions();
            $questions = $quizobj->get_questions();
            $this->assertCount(1, $questions);
        }

        // Count the questions for course question bank.
        // We should have a single question, the random question should have been deleted after the restore.
        $this->assertEquals(1, $this->question_count(\context_course::instance($newcourseid)->id));
        $this->assertEquals(1, $this->question_count(\context_course::instance($newcourseid)->id,
                "AND q.qtype <> 'random'"));

        // Count the questions in quiz qbank.
        $this->assertEquals(0, $this->question_count($quizobj->get_context()->id));
    }

    /**
     * Ensure that question slots are correctly backed up and restored with all properties.
     *
     * @covers \backup_quiz_activity_structure_step::define_structure()
     * @return void
     */
    public function test_backup_restore_question_slots(): void {
        $this->resetAfterTest(true);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $user1 = $this->getDataGenerator()->create_and_enrol($course1, 'editingteacher');
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id, 'editingteacher');

        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');

        $quiz = $quizgenerator->create_instance(['course' => $course1->id, 'questionsperpage' => 0, 'grade' => 100.0,
                'sumgrades' => 3]);

        // Create some fixed and random questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $saq = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        $numq = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        $matchq = $questiongenerator->create_question('match', null, ['category' => $cat->id]);
        $randomcat = $questiongenerator->create_question_category();
        $questiongenerator->create_question('shortanswer', null, ['category' => $randomcat->id]);
        $questiongenerator->create_question('numerical', null, ['category' => $randomcat->id]);
        $questiongenerator->create_question('match', null, ['category' => $randomcat->id]);

        // Add them to the quiz.
        quiz_add_quiz_question($saq->id, $quiz, 1, 3);
        quiz_add_quiz_question($numq->id, $quiz, 2, 2);
        quiz_add_quiz_question($matchq->id, $quiz, 3, 1);
        $this->add_random_questions($quiz->id, 3, $randomcat->id, 2);

        $quizobj = quiz_settings::create($quiz->id, $user1->id);
        $originalstructure = \mod_quiz\structure::create_for_quiz($quizobj);

        // Set one slot to a non-default display number.
        $originalslots = $originalstructure->get_slots();
        $firstslot = reset($originalslots);
        $originalstructure->update_slot_display_number($firstslot->id, rand(5, 10));

        // Set one slot to requireprevious.
        $lastslot = end($originalslots);
        $originalstructure->update_question_dependency($lastslot->id, true);

        // Backup and restore the quiz.
        $backupid = $this->backup_quiz($quiz, $user1);
        $this->restore_quiz($backupid, $course2, $user1);

        // Ensure the restored slots match the original slots.
        $modinfo = get_fast_modinfo($course2);
        $quizzes = $modinfo->get_instances_of('quiz');
        $restoredquiz = reset($quizzes);
        $restoredquizobj = quiz_settings::create($restoredquiz->instance, $user1->id);
        $restoredstructure = \mod_quiz\structure::create_for_quiz($restoredquizobj);
        $restoredslots = array_values($restoredstructure->get_slots());
        $originalstructure = \mod_quiz\structure::create_for_quiz($quizobj);
        $originalslots = array_values($originalstructure->get_slots());
        foreach ($restoredslots as $key => $restoredslot) {
            $originalslot = $originalslots[$key];
            $this->assertEquals($originalslot->quizid, $quiz->id);
            $this->assertEquals($restoredslot->quizid, $restoredquiz->instance);
            $this->assertEquals($originalslot->slot, $restoredslot->slot);
            $this->assertEquals($originalslot->page, $restoredslot->page);
            $this->assertEquals($originalslot->displaynumber, $restoredslot->displaynumber);
            $this->assertEquals($originalslot->requireprevious, $restoredslot->requireprevious);
            $this->assertEquals($originalslot->maxmark, $restoredslot->maxmark);
        }
    }

    /**
     * Test pre 4.3 quiz restore for random question filter conditions.
     *
     * @covers \restore_question_set_reference_data_trait::process_question_set_reference
     */
    public function test_pre_43_quiz_restore_for_random_question_filtercondition() {
        global $USER, $DB;
        $this->resetAfterTest();
        $backupid = 'abc';
        $backuppath = make_backup_temp_directory($backupid);
        get_file_packer('application/vnd.moodle.backup')->extract_to_pathname(
                __DIR__ . "/fixtures/moodle_42_random_question.mbz", $backuppath);

        // Do the restore to new course with default settings.
        $categoryid = $DB->get_field_sql("SELECT MIN(id) FROM {course_categories}");
        $newcourseid = \restore_dbops::create_new_course('Test fullname', 'Test shortname', $categoryid);
        $rc = new \restore_controller($backupid, $newcourseid, \backup::INTERACTIVE_NO, \backup::MODE_GENERAL, $USER->id,
                \backup::TARGET_NEW_COURSE);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        // Get the information about the resulting course and check that it is set up correctly.
        $modinfo = get_fast_modinfo($newcourseid);
        $quiz = array_values($modinfo->get_instances_of('quiz'))[0];
        $quizobj = \mod_quiz\quiz_settings::create($quiz->instance);
        $structure = \mod_quiz\structure::create_for_quiz($quizobj);

        // Count the questions in quiz qbank.
        $context = \context_module::instance(get_coursemodule_from_instance("quiz", $quizobj->get_quizid(), $newcourseid)->id);
        $this->assertEquals(2, $this->question_count($context->id));

        // Are the correct slots returned?
        $slots = $structure->get_slots();
        $this->assertCount(1, $slots);

        // Check that the filtercondition now matches the 4.3 structure.
        foreach ($slots as $slot) {
            $setreference = $DB->get_record('question_set_references',
                    ['itemid' => $slot->id, 'component' => 'mod_quiz', 'questionarea' => 'slot']);
            $filterconditions = json_decode($setreference->filtercondition, true);
            $this->assertArrayHasKey('cat', $filterconditions);
            $this->assertArrayHasKey('jointype', $filterconditions);
            $this->assertArrayHasKey('qpage', $filterconditions);
            $this->assertArrayHasKey('qperpage', $filterconditions);
            $this->assertArrayHasKey('filter', $filterconditions);
            $this->assertArrayHasKey('category', $filterconditions['filter']);
            $this->assertArrayHasKey('qtagids', $filterconditions['filter']);
            $this->assertArrayHasKey('filteroptions', $filterconditions['filter']['category']);
            $this->assertArrayHasKey('includesubcategories', $filterconditions['filter']['category']['filteroptions']);

            // MDL-79708: Bad filter conversion check.
            $this->assertArrayNotHasKey('includesubcategories', $filterconditions['filter']['category']);

            $this->assertArrayNotHasKey('questioncategoryid', $filterconditions);
            $this->assertArrayNotHasKey('tags', $filterconditions);
            $expectedtags = \core_tag_tag::get_by_name_bulk(1, ['foo', 'bar']);
            $expectedtagids = array_values(array_map(fn($expectedtag) => $expectedtag->id, $expectedtags));
            $this->assertEquals($expectedtagids, $filterconditions['filter']['qtagids']['values']);
            $expectedcategory = $DB->get_record('question_categories', ['idnumber' => 'RAND']);
            $this->assertEquals($expectedcategory->id, $filterconditions['filter']['category']['values'][0]);
            $expectedcat = implode(',', [$expectedcategory->id, $expectedcategory->contextid]);
            $this->assertEquals($expectedcat, $filterconditions['cat']);

            // MDL-79708: Try to convert already converted filter.
            $filterconditionsold = $filterconditions;
            $filterconditions = question_reference_manager::convert_legacy_set_reference_filter_condition($filterconditions);
            // Check that the filtercondition didn't change.
            $this->assertEquals($filterconditionsold, $filterconditions);

            // MDL-79708: Try to convert a filter with previously bad conversion.
            $filterconditions['filter']['category']['includesubcategories'] = 0;
            unset($filterconditions['filter']['category']['filteroptions']);
            $filterconditions = question_reference_manager::convert_legacy_set_reference_filter_condition($filterconditions);
            $this->assertEquals($filterconditionsold, $filterconditions);
        }
    }
}
