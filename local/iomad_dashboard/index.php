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

// display iomad_dashboard

require_once( '../../config.php');
require_once( '../iomad/lib/blockpage.php');

// we always require users to be logged in for this page
require_login();

// get parameters
$edit = optional_param( 'edit', null, PARAM_BOOL );
$company = optional_param('company', '', PARAM_TEXT);

// check we are allowed to view this page
$systemcontext = get_context_instance(CONTEXT_SYSTEM);
require_capability( 'local/iomad_dashboard:view', $systemcontext );

// Set the session to a user if they are editing a company other than their own
if (!empty($company)) {
    $SESSION->currenteditingcompany=$company;
}

// check if there are any companies
if (!$companycount=$DB->count_records('company')) {
    // if not redirect to create form
    redirect(new moodle_url('/blocks/iomad_company_admin/company_edit_form.php',
                             array('createnew'=>1)));
}

// page setup stuff
// the page layout for my moodle does the job here
// as it allows blocks in the centre column.
$PAGE->requires->js_init_call( 'M.local_iomad_dashboard.init');
$PAGE->blocks->add_region('content');
$blockpage = new blockpage($PAGE, $OUTPUT, 'iomad_dashboard', 'local', 'name');
$blockpage->setup();
// set tye pagetype correctly
$PAGE->set_pagetype('local-iomad-dashboard-index');

// Now we can display the page
//echo "<pre>"; print_r( $PAGE ); die;

$blockpage->display_header();
echo $OUTPUT->blocks_for_region('content');
echo $OUTPUT->footer();
