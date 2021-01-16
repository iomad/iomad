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
 * This page lets users to manage site wide competencies.
 *
 * @package    tool_lp
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$id = required_param('courseid', PARAM_INT);

$params = array('id' => $id);
$course = $DB->get_record('course', $params, '*', MUST_EXIST);

require_login($course);
\core_competency\api::require_enabled();

$context = context_course::instance($course->id);
$urlparams = array('courseid' => $id);

$url = new moodle_url('/admin/tool/lp/coursecompetencies.php', $urlparams);

list($title, $subtitle) = \tool_lp\page_helper::setup_for_course($url, $course);

$output = $PAGE->get_renderer('tool_lp');
$page = new \tool_lp\output\course_competencies_page($course->id);

echo $output->header();
echo $output->heading($title);

echo $output->render($page);

echo $output->footer();
