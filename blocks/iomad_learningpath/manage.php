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
 * Management page for Iomad Learning Paths
 *
 * @package    block_iomad_learningpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_learningpath\companypaths;
use block_iomad_learningpath\output\manage_page;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

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
$url = new moodle_url('/blocks/iomad_learningpath/manage.php');
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managetitle', 'block_iomad_learningpath'));
$PAGE->set_heading(get_string('learningpathmanage', 'block_iomad_learningpath'));
$PAGE->requires->js_call_amd('block_iomad_learningpath/manage', 'init');
$PAGE->requires->js_call_amd('block_iomad_learningpath/path_edit', 'init');
$output = $PAGE->get_renderer('block_iomad_learningpath');

// Add the add new path button.
$buttons = html_writer::tag(
    'a',
    get_string('addpath', 'block_iomad_learningpath'),
    [
        'href' => '#',
        'role' => 'button',
        'class' => 'btn btn-secondary',
        'data-action' => 'show-editpathform',
        'data-companyid' => $companyid,
        'data-pathid' => 0,
    ]
);
$PAGE->set_button($buttons);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// IOMAD stuff.
$companypaths = new companypaths($companyid, $systemcontext);
$paths = $companypaths->get_paths();

// Get renderer for page (and pass data).
$managepage = new manage_page($companycontext, $paths);

echo $OUTPUT->header();

echo $output->render($managepage);

echo $OUTPUT->footer();
