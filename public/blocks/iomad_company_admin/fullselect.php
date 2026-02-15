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
 * IOMAD Dashboard choose tenant from list of available main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_company_admin\output\full_companies_select;
use local_iomad\custom_context\context_company;
use local_iomad\forms\company_search_form;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$search = optional_param('search', '', PARAM_ALPHANUM);

// Log in and set up $PAGE.
require_login();

// Do we have a company set up or.....?
$systemcontext = context_system::instance();
$companycontext = $systemcontext;
$company = $SESSION->currenteditingcompany;
if (!empty($company)) {
    $companycontext = context_company::instance($company);
}

// Set the URL.
$url = new moodle_url('/blocks/iomad_company_admin/fullselect.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($url);
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('dashboard', 'block_iomad_company_admin'));
$PAGE->requires->js_call_amd('block_iomad_company_admin/admin', 'init');

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Set the page heading.
$PAGE->set_heading(get_string('selectacompany', 'block_iomad_company_admin'));

$fullcompaniesselect = new full_companies_select(['search' => $search]);
$companysearchform = new company_search_form($url, []);

// Display the page.
echo $output->header();

// Display the form.
echo html_writer::start_tag('p');
$companysearchform->display();
echo html_writer::end_tag('p');

// Display the selector.
echo $output->render($fullcompaniesselect);

// Display the footer.
echo $output->footer();
