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
 * Manage list of courses in learning path
 *
 * @package    local_iomadlearninpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Security
$context = context_system::instance();
require_login();
iomad::require_capability('local/iomad_learningpath:manage', $context);

// Parameters
$id = required_param('id', PARAM_INT);

// Page boilerplate stuff.
$url = new moodle_url('/local/iomad_learningpath/students.php', ['id' => $id]);
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('managetitle', 'local_iomad_learningpath'));
$PAGE->set_heading(get_string('managestudents', 'local_iomad_learningpath'));
$output = $PAGE->get_renderer('local_iomad_learningpath');

// IOMAD stuff
$companyid = iomad::get_my_companyid($context);
$companypaths = new local_iomad_learningpath\companypaths($companyid, $context);
$path = $companypaths->get_path($id);
$companypaths->breadcrumb(get_string('managestudents', 'local_iomad_learningpath'), $url);

// Javascript initialise
$PAGE->requires->js_call_amd('local_iomad_learningpath/students', 'init', [$companyid, $id]);

// Get renderer for page (and pass data).
$students_page = new local_iomad_learningpath\output\students_page($context, $path);

echo $OUTPUT->header();

echo $output->render($students_page);

echo $OUTPUT->footer();
