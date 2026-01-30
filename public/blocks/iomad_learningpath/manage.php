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
 * @package    local_iomadlearninpath
 * @copyright  2018 Howard Miller (howardsmiller@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/lib.php');

// Security
require_login();

$systemcontext = context_system::instance();

// Set the companyid
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);

iomad::require_capability('block/iomad_learningpath:manage', $companycontext);

// Page boilerplate stuff.
$url = new moodle_url('/blocks/iomad_learningpath/manage.php');
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('managetitle', 'block_iomad_learningpath'));
$PAGE->set_heading(get_string('learningpathmanage', 'block_iomad_learningpath'));
$PAGE->requires->js_call_amd('block_iomad_learningpath/manage', 'init');
$output = $PAGE->get_renderer('block_iomad_learningpath');

// Log this page view.
block_iomad_company_admin\event\dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// IOMAD stuff
$companypaths = new block_iomad_learningpath\companypaths($companyid, $systemcontext);
$paths = $companypaths->get_paths();

// Get renderer for page (and pass data).
$manage_page = new block_iomad_learningpath\output\manage_page($systemcontext, $paths);

echo $OUTPUT->header();

echo $output->render($manage_page);

echo $OUTPUT->footer();
