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
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\output\courselist_page;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Parameters.
$id = required_param('id', PARAM_INT);

// Security.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

// Can we even do anything?
iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

// Page boilerplate stuff.
$url = new moodle_url('/blocks/iomad_learningpath/courselist.php', ['id' => $id]);
$manageurl = new moodle_url('/blocks/iomad_learningpath/manage.php');
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');

// IOMAD stuff.
$companypaths = new companypaths($companyid, $systemcontext);
$path = $companypaths->get_path($id);
$courses = $companypaths->get_courselist($id);
$categories = $companypaths->get_categories($id);
$companypaths->check_group($id);
$groups = $companypaths->get_display_courselist($id);
$programlicenses = $companypaths->get_programlicenses($id);

// Finish setting up PAGE.
$PAGE->set_title(get_string('managetitle', 'block_iomad_learningpath'));
$PAGE->set_heading(get_string('managecourses', 'block_iomad_learningpath', $path->name));
$output = $PAGE->get_renderer('block_iomad_learningpath');

// Add the management button
$buttons = $OUTPUT->single_button($manageurl, get_string('managetitle', 'block_iomad_learningpath'), 'get');
$PAGE->set_button($buttons);

// Javascript initialise.
$PAGE->requires->js_call_amd('block_iomad_learningpath/courselist', 'init', [$companyid, $id]);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get renderer for page (and pass data).
$coursellistpage = new courselist_page($companycontext, $path, $groups, $categories, $programlicenses);

// Display the page.
echo $OUTPUT->header();

// Display the
echo $output->render($coursellistpage);

echo $OUTPUT->footer();
