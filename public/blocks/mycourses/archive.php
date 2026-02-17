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
 * IOMAD My Courses block
 *
 * @package   block_mycourses
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

// Log in and create $PAGE.
require_login();

// Set the context.
$context = context_system::instance();

// Set the page URL.
$url = '/blocks/mycourses/archive.php';

// Finish setting up PAGE.
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('archivetitle', 'block_mycourses'));
$PAGE->set_url($url);
$PAGE->set_heading($SITE->fullname);

// Get the output renderer.
$output = $PAGE->get_renderer('block_mycourses');

// Get the cut off date.
$cutoffdate = time() - ($CFG->mycourses_archivecutoff * 24 * 60 * 60);

// Get my list of archived courses.
$myarchive = mycourses_get_my_archive($cutoffdate);

// Display the page.
echo $output->header();

// Display my archive courses.
echo $output->display_archive($myarchive);

// Display the footer.
echo $output->footer();
