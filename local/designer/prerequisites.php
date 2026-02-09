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
 * List of the prerequisites courses.
 *
 * @package   local_designer
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot. "/local/designer/lib.php");
$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);
require_login($course);
$PAGE->set_context($context);
$pageurl = new moodle_url('/local/designer/prerequisites.php', ['id' => $course->id]);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-' . $course->format);
$PAGE->set_title(get_string('coursetitle', 'moodle', ['course' => $course->fullname]));

$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();

$course = course_get_format($course)->get_course();
$template = local_designer_import_prerequisites_courses($course);

echo $OUTPUT->render_from_template("local_designer/prerequisites", $template);
echo $OUTPUT->footer();
