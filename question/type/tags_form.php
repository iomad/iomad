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
 * The mform to manage question tags.
 *
 * @package   core_question
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');
require_once($CFG->dirroot . '/lib/questionlib.php');
/**
 * The mform class for  manage question tags.
 *
 * @copyright 2018 Simey Lameze <simey@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tags extends \moodleform {

    /**
     * The form definition
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->disable_form_change_checker();

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'categoryid');
        $mform->setType('categoryid', PARAM_INT);

        $mform->addElement('hidden', 'contextid');
        $mform->setType('contextid', PARAM_INT);

        $mform->addElement('static', 'questionname', get_string('questionname', 'question'));
        $mform->addElement('static', 'questioncategory', get_string('categorycurrent', 'question'));
        $mform->addElement('static', 'context', '');

        if (\core_tag_tag::is_enabled('core_question', 'question')) {
            $tags = \core_tag_tag::get_tags_by_area_in_contexts('core_question', 'question', $customdata['contexts']);
            $tagstrings = [];
            foreach ($tags as $tag) {
                $tagstrings[$tag->name] = $tag->name;
            }

            $options = [
                'tags' => true,
                'multiple' => true,
                'noselectionstring' => get_string('anytags', 'quiz'),
            ];
            $mform->addElement('autocomplete', 'tags',  get_string('tags'), $tagstrings, $options);

            // Is the question category in a course context?
            $qcontext = $customdata['questioncontext'];
            $qcoursecontext = $qcontext->get_course_context(false);
            $iscourseoractivityquestion = !empty($qcoursecontext);
            // Is the current context we're editing in a course context?
            $editingcontext = $customdata['editingcontext'];
            $editingcoursecontext = $editingcontext->get_course_context(false);
            $iseditingcontextcourseoractivity = !empty($editingcoursecontext);

            if ($iseditingcontextcourseoractivity && !$iscourseoractivityquestion) {
                // If the question is being edited in a course or activity context
                // and the question isn't a course or activity level question then
                // allow course tags to be added to the course.
                $coursetagheader = get_string('questionformtagheader', 'core_question',
                    $editingcoursecontext->get_context_name(true));
                $mform->addElement('autocomplete', 'coursetags',  $coursetagheader, $tagstrings, $options);

            }
        }
    }
}
