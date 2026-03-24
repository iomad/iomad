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
 * IOMAD Dashboard upload tenants via CSV main page
 *
 * @package   block_iomad_company_admin
 * @copyright 2025 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_iomad_company_admin\event\{company_created, dashboard_page_viewed};
use block_iomad_company_admin\forms\company_import_form;
use local_iomad\{company, company_user, iomad};
use local_iomad\custom_context\context_company;

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir.'/csvlib.class.php');

$confirm = optional_param('confirm', null, PARAM_ALPHANUM);
$submit = optional_param('submitbutton', '', PARAM_ALPHANUM);
$fileimport = optional_param('fileimport', 0, PARAM_BOOL);
$iid = optional_param('iid', '', PARAM_INT);
$previewrows = optional_param('previewrows', 10, PARAM_INT);
$readcount = optional_param('readcount', 0, PARAM_INT);

// Login and set up $PAGE.
require_login();

// Set the companyid.
$systemcontext = context_system::instance();
$companyid = iomad::get_my_companyid($systemcontext);
$companycontext = context_company::instance($companyid);
$company = new company($companyid);
$useparentid = false;

// Can we even do anything on this page?
if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext) &&
    !iomad::has_capability('block/iomad_company_admin:company_add_child', $companycontext)) {
        throw new moodle_exception(
            get_string('nopermissions'),
            'error',
            new moodle_url($CFG->wwwroot .'/my'));
}

// Can I only add to my company?
if (!iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
    $useparentid = true;
}

// Set the name for the page.
$linktext = get_string('importcompanies', 'block_iomad_company_admin');

// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_upload.php');
$dashboardurl = new moodle_url('/blocks/iomad_company_admin/index.php');

// Finish setting up PAGE.
$PAGE->set_context($companycontext);
$PAGE->set_url($linkurl);
$PAGE->set_title($linktext);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('companyimportfromfile', 'block_iomad_company_admin'));

// Log this page view.
dashboard_page_viewed::create_from_url($PAGE->url->out())->trigger();

// Array of all valid fields for CSV validation.
$stdfields = ['name',
              'shortname',
              'code',
              'address',
              'city',
              'region',
              'postcode',
              'country',
              'parent',
              'theme',
              'hostname',
              'maxusers',
              'validto',
              'suspendafter',
              'custom1',
              'custom2',
              'custom3',
              'customcss',
              'maincolor',
              'headingcolor',
              'linkcolor'];

// Process the uploaded file.
if (empty($iid)) {
    $mform = new company_import_form();
    if ($mform->is_cancelled()) {
        redirect($dashboardurl);
    }

    // Process the form.
    if ($importdata = $mform->get_data()) {
        // Verification moved to two places: after upload and into form2.
        $companyerrors = 0;
        $erroredcompanies = [];
        $errorstr = get_string('error');

        // Get the data from the CSV.
        $iid = csv_import_reader::get_new_iid('uploadcompanies');
        $cir = new csv_import_reader($iid, 'uploadcompanies');

        $content = $mform->get_file_content('importfile');
        $readcount = $cir->load_csv_content($content,
                                            $importdata->encoding,
                                            $importdata->delimiter_name,
                                            'validate_uploadcompany_columns');

        // Check we got something.
        if ($readcount === false) {
            throw new \moodle_exception('csvfileerror', 'tool_uploadcourse', $linkurl, $cir->get_error());
        } else if ($readcount == 0) {
            throw new \moodle_exception('csvemptyfile', 'error', $linkurl, $cir->get_error());
        }

        // Clear down the raw file content as we no longer need it.
        $columns = $cir->get_columns();
        unset($content);

        // Display the page.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('uploadcompaniesresult', 'block_iomad_company_admin'));

        // Process CSV lines.
        $cir->init();
        $runtime = time();
        $linenum = 1; // Column header is first line.

        // Init upload progress tracker.
        $upt = new upload_progress_tracker();
        $upt->init(); // Start table.
        while ($line = $cir->next()) {
            $upt->flush();
            $linenum++;
            $errornum = 1;
            $companyrec = (object) [];
            $upt->track('line', $linenum);
            foreach ($line as $key => $value) {
                if ($value !== '') {
                    $key = $columns[$key];
                    $value = trim($value);

                    if (strpos($key, 'name') !== false) {
                        $value = clean_param($value, PARAM_NOTAGS);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('name', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($DB->get_record('local_iomad_companies', ['name' => $value])) {
                            $upt->track(
                                'status',
                                get_string('duplicatecompany', 'block_iomad_company_admin', 'name'),
                                'error');
                            $upt->track('name', $errorstr, 'error');
                            $line[] = get_string('duplicatecompany', 'block_iomad_company_admin', 'name');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    } else if (strpos($key, 'shortname') !== false) {
                        $value = clean_param($value, PARAM_NOTAGS);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('shortname', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($DB->get_record('local_iomad_companies', ['shortname' => $value])) {
                            $upt->track(
                                'status',
                                get_string('duplicatecompany', 'block_iomad_company_admin', 'shortname'),
                                'error');
                            $upt->track('shortname', $errorstr, 'error');
                            $line[] = get_string('duplicatecompany', 'block_iomad_company_admin', 'shortname');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if (!preg_match('/^[A-Za-z0-9_]+$/', $data['shortname'])) {
                            // Check allowed pattern (numbers, letters and underscore).
                            $upt->track('status', get_string('invalidshortnameerror', 'core_customfield'), 'error');
                            $upt->track('shortname', get_string('invalidshortnameerror', 'core_customfield'), 'error');
                            $line[] = get_string('invalidshortnameerror', 'core_customfield');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    } else if (strpos($key, 'code') !== false) {
                        $value = clean_param($value, PARAM_NOTAGS);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('code', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($DB->get_record('local_iomad_companies', ['code' => $value])) {
                            $upt->track(
                                'status',
                                get_string('companycodetaken', 'block_iomad_company_admin', $value),
                                'error');
                            $upt->track('code', $errorstr, 'error');
                            $line[] = get_string('companycodetaken', 'block_iomad_company_admin', $value);
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    } else if (strpos($key, 'hostname') !== false) {
                        $value = clean_param($value, PARAM_NOTAGS);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('hostname', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($DB->get_record('local_iomad_companies', ['hostname' => $value])) {
                            $upt->track(
                                'status',
                                get_string('companyhostnametaken', 'block_iomad_company_admin', $value),
                                'error');
                            $upt->track('hostname', $errorstr, 'error');
                            $line[] = get_string('duplicatecompany', 'block_iomad_company_admin', $value);
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    } else if (strpos($key, 'maxusers') !== false) {
                        $value = clean_param($value, PARAM_INT);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('maxusers', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($value < 1) {
                            $upt->track('status', get_string('invalidnum', 'error'));
                            $upt->track('maxusers', $errorstr, 'error');
                            $line[] = get_string('invalidnum', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    } else if (strpos($key, 'parent') !== false) {
                        $value = clean_param($value, PARAM_NOTAGS);
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track('parent', $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if (! $parentrec = $DB->get_record('local_iomad_companies', ['shortname' => $value])) {
                            $upt->track(
                                'status',
                                get_string('missingparent', 'block_iomad_company_admin', 'parent'),
                                'error');
                            $upt->track('parent', $errorstr, 'error');
                            $line[] = get_string('missingparent', 'block_iomad_company_admin', 'parent');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else if ($useparentid &&
                                   !company_user::can_see_company($parentrec)) {
                            $upt->track(
                                'status',
                                get_string('invalidparent', 'block_iomad_company_admin', 'parent'),
                                'error');
                            $upt->track('parent', $errorstr, 'error');
                            $line[] = get_string('invalidparent', 'block_iomad_company_admin', 'parent');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->parentid = $parentrec->id;
                            $upt->track($key, $value);
                        }
                    } else {
                        if (strpos($key, 'country') !== false) {
                            $value = clean_param($value, PARAM_ALPHA);
                        } else if (strpos($key, 'hostname') !== false) {
                            $value = clean_param($value, PARAM_HOST);
                        } else if (strpos($key, 'validto') !== false ||
                                   strpos($key, 'suspendafter') !== false) {
                            $value = clean_param($value, PARAM_INT);
                        } else if (strpos($key, 'maincolor') !== false ||
                                   strpos($key, 'headingcolor') !== false ||
                                   strpos($key, 'linkcolor') !== false) {
                            $value = clean_param($value, PARAM_CLEAN);
                        } else if (!strpos($key, 'hostname') !== false) {
                            $value = clean_param($value, PARAM_NOTAGS);
                        }
                        if (empty($value)) {
                            $upt->track('status', get_string('profileinvaliddata', 'error'), 'error');
                            $upt->track($key, $errorstr, 'error');
                            $line[] = get_string('profileinvaliddata', 'error');
                            $companyerrors++;
                            $errornum++;
                            $erroredcompanies[] = $line;
                            continue 2;
                        } else {
                            $companyrec->$key = $value;
                            if (in_array($key, $upt->columns)) {
                                $upt->track($key, $value);
                            }
                        }
                    }
                }
            }

            // We should by now have a company record!
            if (empty($companyrec)) {
                $companyerrors++;
                $errornum++;
                $erroredcompanies[] = $line;
                continue;
            }

            // Do we have everything?
            if (empty($companyrec->name)) {
                $upt->track('status', get_string('missingfield', 'error', 'name'), 'error');
                $upt->track('name', $errorstr, 'error');
                $line[] = get_string('missingfield', 'error', 'name');
                $companyerrors++;
                $errornum++;
                $erroredcompanies[] = $line;
                continue;
            }
            if (empty($companyrec->shortname)) {
                $upt->track('status', get_string('missingfield', 'error', 'shortname'), 'error');
                $upt->track('shortname', $errorstr, 'error');
                $line[] = get_string('missingfield', 'error', 'shortname');
                $companyerrors++;
                $errornum++;
                $erroredcompanies[] = $line;
                continue;
            }
            if (empty($companyrec->city)) {
                $upt->track('status', get_string('missingfield', 'error', 'city'), 'error');
                $upt->track('city', $errorstr, 'error');
                $line[] = get_string('missingfield', 'error', 'city');
                $companyerrors++;
                $errornum++;
                $erroredcompanies[] = $line;
                continue;
            }
            if (empty($companyrec->country)) {
                $upt->track('status', get_string('missingfield', 'error', 'country'), 'error');
                $upt->track('country', $errorstr, 'error');
                $line[] = get_string('missingfield', 'error', 'country');
                $companyerrors++;
                $errornum++;
                $erroredcompanies[] = $line;
                continue;
            }
            if ($useparentid &&
                empty($companyrec->parent)) {
                $companyrec->parentid = $companyid;
            }

            // We hit create.
            $newcompanyid = $DB->insert_record('local_iomad_companies', $companyrec);
            $newcompany = new company($newcompanyid);

            $eventother = ['companyid' => $newcompanyid];

            $event = company_created::create([
                'context' => $systemcontext,
                'userid' => $USER->id,
                'objectid' => $newcompanyid,
                'other' => $eventother,
            ]);
            $event->trigger();

            // Set up default department.
            company::initialise_departments($newcompanyid);

            // Set up course category for company.
            $coursecat = new stdclass();
            $coursecat->name = $companyrec->name;
            $coursecat->sortorder = 999;
            $coursecat->id = $DB->insert_record('course_categories', $coursecat);
            $coursecat->context = context_coursecat::instance($coursecat->id);
            $categorycontext = $coursecat->context;
            $categorycontext->mark_dirty();
            $DB->update_record('course_categories', $coursecat);
            fix_course_sortorder();
            $companydetails = $DB->get_record('local_iomad_companies', ['id' => $newcompanyid]);
            $companydetails->category = $coursecat->id;
            $DB->update_record('local_iomad_companies', $companydetails);

            // Deal with any parent company assignments.
            if (!empty($companydetails->parentid)) {
                $company = new company($companydetails->id);
                $company->assign_parent_managers($companydetails->parentid);
            }

            $upt->track('id', $newcompanyid);
            $upt->track('status', get_string('ok'));

        }

        $upt->flush();
        $upt->close(); // Close table.

        $cir->close();
        $cir->cleanup(true);

        // Deal with any erroring companies.
        if (!empty($erroredcompanies)) {
            echo get_string('erroredcompanies', 'block_iomad_company_admin');
            $erroredtable = new html_table();
            foreach ($erroredcompanies as $erroredcompany) {
                $erroredtable->data[] = $erroredcompany;
            }
            echo html_writer::table($erroredtable);

        }
        echo html_writer::tag(
            'a',
            get_string('continue'),
            [
                'class' => 'btn btn-primary',
                'href' => $linkurl,
            ]
        );

        echo $OUTPUT->footer();
        die;
    }
}

// Display the page.
echo $OUTPUT->header();

// Display the form.
$mform->set_data(['fileimport' => $fileimport]);
$mform->display();

// Display the footer.
echo $OUTPUT->footer();

/**
 * Utility class to display the output table
 */
class upload_progress_tracker {

    /** @var array current row */
    public $_row;

    /** @var list of possible column names */
    public $columns = ['status',
                       'line',
                       'id',
                       'name',
                       'shortname',
                       'code',
                       'address',
                       'city',
                       'region',
                       'postcode',
                       'country',
                       'parent',
                       'theme',
                       'hostname',
                       'maxusers',
                       'validto',
                       'suspendafter',
                       'custom1',
                       'custom2',
                       'custom3',
                       'customcss',
                       'maincolor',
                       'headingcolor',
                       'linkcolor'];

    /**
     * Class initialisation
     *
     * @return void
     */
    public function init() {
        // Set the column number to 0.
        $ci = 0;

        // Display the output table.
        echo html_writer::start_tag(
            'table',
            [
                'id' => "uploadresults",
                'class' => "generaltable boxaligncenter flexible-wrap",
                'summary' => get_string('uploadcompaniesresult', 'block_iomad_company_admin'),
            ]
        );

        // Display the table headers.
        echo html_writer::start_tag('tr', ['class' => "heading r0"]);
        echo html_writer::tag(
            'th',
            get_string('status'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('uucsvline', 'tool_uploaduser'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companyname', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companyshortname', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companycode', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('address'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companycity', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('region', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('postcode', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('country'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('parentcompany', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('theme'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companyhostname', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companymaxusers', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companyvalidto', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('companyterminateafter', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('custom1', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('custom2', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('custom3', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('customcss', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('maincolor', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('headingcolor', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::tag(
            'th',
            get_string('linkcolor', 'block_iomad_company_admin'),
            [
                'class' => 'header c' . $ci++,
                'scope' => "col",
            ]
        );
        echo html_writer::end_tag('tr');

        // Unset the current row.
        $this->_row = null;
    }

    /**
     * Display the row output
     *
     * @return void
     */
    public function flush() {

        // Do we have errors or warning to display?
        if (empty($this->_row) || empty($this->_row['line']['normal'])) {
            $this->_row = [];
            foreach ($this->columns as $col) {
                $this->_row[$col] = ['normal' => '', 'info' => '', 'warning' => '', 'error' => ''];
            }
            return;
        }

        // Set some defaults.
        $ci = 0;
        $ri = 1;

        // Display the table row.
        echo html_writer::start_tag('tr', ['class' => 'r'.$ri]);
        foreach ($this->_row as $key => $field) {
            foreach ($field as $type => $content) {
                if ($field[$type] !== '') {
                    $field[$type] = html_writer::tag('span', $field[$type], ['class' => 'uu'.$type]);
                } else {
                    unset($field[$type]);
                }
            }
            echo html_writer::start_tag('td', ['class' => 'cell c'.$ci++]);
            if (!empty($field)) {
                echo implode(html_writer::empty_tag('br'), $field);
            } else {
                echo '&nbsp;';
            }
            echo html_writer::end_tag('td');
        }
        echo html_writer::end_tag('tr');
        foreach ($this->columns as $col) {
            $this->_row[$col] = ['normal' => '', 'info' => '', 'warning' => '', 'error' => ''];
        }
    }

    /**
     * Track the results
     *
     * @param string $col
     * @param string $msg
     * @param string $level
     * @param boolean $merge
     * @return void
     */
    public function track($col, $msg, $level= 'normal', $merge=true) {
        if (empty($this->_row)) {
            $this->flush(); // Init arrays.
        }
        if (!in_array($col, $this->columns)) {
            debugging('Incorrect column:'.$col);
            return;
        }
        if ($merge) {
            if ($this->_row[$col][$level] != '') {
                $this->_row[$col][$level] .= html_writer::empty_tag('br');
            }
            $this->_row[$col][$level] .= s($msg);
        } else {
            $this->_row[$col][$level] = s($msg);
        }
    }

    /**
     * End the tracking table display
     *
     * @return void
     */
    public function close() {
        echo html_writer::end_tag('table');
    }
}

/**
 * Validation callback function - verified the column line of csv file.
 * Converts column names to lowercase too.
 */
function validate_uploadcompany_columns(&$columns) {
    global $stdfields;

    if (count($columns) < 4) {
        return get_string('csvfewcolumns', 'error');
    }

    // Test columns.
    $processed = [];
    foreach ($columns as $key => $unused) {
        $field = $columns[$key];
        if (!in_array($field, $stdfields)) {
            // If not a standard field and not an enrolment field, then we have an error!
            return get_string('invalidfieldname', 'error', $field);
        }
        if (in_array($field, $processed)) {
            return get_string('csvcolumnduplicates', 'error');
        }
        $processed[] = $field;
    }
    if (!in_array('name', $processed)) {
        return get_string('missingcompanyname', 'block_iomad_company_admin');
    }
    if (!in_array('shortname', $processed)) {
        return get_string('missingcompanyshortname', 'block_iomad_company_admin');
    }
    if (!in_array('city', $processed)) {
        return get_string('missingcompanycity', 'block_iomad_company_admin');
    }
    if (!in_array('country', $processed)) {
        return get_string('missingcompanycoutry', 'block_iomad_company_admin');
    }

    return true;
}
