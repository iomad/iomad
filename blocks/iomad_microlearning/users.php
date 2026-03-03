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
 * IOMAD microlearning user management main page
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\dashboard_page_viewed;
use block_iomad_microlearning\forms\{microlearning_threads_form, microlearning_thread_users_form};
use block_iomad_microlearning\microlearning;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;

require_once(dirname(__FILE__) . '/../../config.php'); // Creates $PAGE.
require_once($CFG->dirroot . '/blocks/iomad_company_admin/lib.php');
require_once($CFG->libdir . '/formslib.php');

$threadid = optional_param('threadid', 0, PARAM_INTEGER);
$groupid = optional_param('groupid', "-1", PARAM_INTEGER);
$departmentid = optional_param('deptid', 0, PARAM_INTEGER);
$selectedthread = optional_param('selectedthread', 0, PARAM_INTEGER);
$groupid = optional_param('groupid', 0, PARAM_INTEGER);

$params = [
    'threadid' => $threadid,
    'groupid' => $groupid,
    'deptid' => $departmentid,
    'selectedthread' => $selectedthread,
];

// Log in and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);
$parentlevel = company::get_company_parentnode($companyid);
$companydepartment = $parentlevel->id;

// Can we even do anything?
iomad::require_capability('block/iomad_microlearning:assign_threads', $companycontext);

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_microlearning/users.php');
$threadlink = new moodle_url('/blocks/iomad_microlearning/threads.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_pagelayout('base');

// Get output renderer.
$output = $PAGE->get_renderer('block_iomad_company_admin');

// Javascript for fancy select.
$PAGE->requires->js_call_amd('block_iomad_company_admin/department_select',
                             'init',
                             ['deptid', 1, optional_param('deptid', 0, PARAM_INT)]);

// Set the name for the page.
$linktext = get_string('company_threads_for', 'block_iomad_microlearning', $company->get_name());

// Set the page heading.
$PAGE->set_title($linktext);
$PAGE->set_heading($linktext);

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Deal with the link back to the main microlearning page.
$buttoncaption = get_string('threads', 'block_iomad_microlearning');
$buttonlink = new moodle_url('/blocks/iomad_microlearning/threads.php');
$buttons = $OUTPUT->single_button($buttonlink, $buttoncaption, 'get');
$PAGE->set_button($buttons);

if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
    $userhierarchylevel = $parentlevel->id;
} else {
    $userlevel = $company->get_userlevel($USER);
    $userhierarchylevel = key($userlevel);
}

// Set up the forms.
$threadsform = new microlearning_threads_form($PAGE->url,
                                              $companycontext,
                                              $companyid,
                                              $departmentid,
                                              $selectedthread,
                                              $parentlevel);
$usersform = new microlearning_thread_users_form($PAGE->url, $companycontext, $companyid, $departmentid, $threadid, $groupid);

if ($threadsform->is_cancelled() || $usersform->is_cancelled() ||
    optional_param('cancel', false, PARAM_BOOL) ) {
    redirect(new moodle_url($CFG->wwwroot .'/blocks/iomad_company_admin/index.php'));
}

// Display the page.
echo $output->header();

// Check the department is valid.
if (!empty($departmentid) && !company::check_valid_department($companyid, $departmentid)) {
    throw new moodle_exception('invaliddepartment', 'block_iomad_company_admin');
}

// Display the department picket.
echo $output->display_tree_selector($company, $parentlevel, $linkurl, $params, $departmentid);

// Display the forms.
echo html_writer::start_tag('div', ['class' => 'iomadclear']);
if ($companyid > 0) {
    $threadsform->set_data($params);

    // Display the threads form.
    echo $threadsform->display();
    if ($threadid != 0) {
        if ($data = $threadsform->get_data() || !empty($selectedthread)) {
            if ($threadid > 0) {
                if (!$thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
                    throw new moodle_exception('invalidthreadid', 'block_iomad_microlearning');
                }
                if (!$DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $thread->id])) {
                    // We don't have anything to assign.
                    echo $output->notification(get_string('nonuggets', 'block_iomad_microlearning'), 'info', false);

                    // Add the button to manage nuggets.
                    echo $output->single_button(
                        new moodle_url(
                            $CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php',
                            ['threadid' => $thread->id]
                        ),
                        get_string('learningnuggets', 'block_iomad_microlearning')
                    );

                    echo $output->footer();
                    die;
                }
                $usersform->process();
                $usersform = new microlearning_thread_users_form($PAGE->url,
                                                                 $companycontext,
                                                                 $companyid,
                                                                 $departmentid,
                                                                 $threadid,
                                                                 $groupid);
            } else if (!empty($selectedthread)) {
                $usersform->set_thread($selectedthread);
            }
            echo $usersform->display();
        } else if ($threadid > 0) {
            $thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid]);
            if (!$thread = $DB->get_record('block_iomad_microlearning_threads', ['id' => $threadid])) {
                throw new moodle_exception('invalidthreadid', 'block_iomad_microlearning');
            }
            if (!$DB->get_records('block_iomad_microlearning_nuggets', ['threadid' => $thread->id])) {
                // We don't have anything to assign.
                echo $output->notification(get_string('nonuggets', 'block_iomad_microlearning'), 'info', false);

                // Add the button to manage nuggets.
                echo $output->single_button(
                    new moodle_url(
                        $CFG->wwwroot . '/blocks/iomad_microlearning/nuggets.php',
                        ['threadid' => $thread->id]
                    ),
                    get_string('learningnuggets', 'block_iomad_microlearning')
                );

                echo $output->footer();
                die;
            }
            $usersform->process();
            $usersform = new microlearning_thread_users_form($PAGE->url,
                                                             $companycontext,
                                                             $companyid,
                                                             $departmentid,
                                                             $threadid,
                                                             $groupid);
            echo $usersform->display();
        }
    }
}

echo html_writer::end_tag('div');

// Display the footer.
echo $output->footer();
