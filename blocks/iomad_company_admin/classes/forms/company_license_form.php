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
 * IOMAD Dashboard company license split form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use block_iomad_company_admin\event\company_license_created;
use block_iomad_company_admin\event\company_license_updated;
use context;
use core\exception\moodle_exception;
use core\notification;
use core\output\html_writer;
use core_form\dynamic_form;
use local_iomad\{company, iomad};
use local_iomad\custom_context\context_company;
use moodle_url;

/**
 * IOMAD Dashboard company license edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_license_form extends dynamic_form {

    /**
     * Default form definition
     *
     * @return void
     */
    public function definition() {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $licenseid = $this->optional_param('licenseid', 0, PARAM_INT);
        $parentid = $this->optional_param('parentid', 0, PARAM_INT);
        $company = new company($companyid);

        // Get the license record.
        if (!empty($licenseid)) {
            $license = $DB->get_record(
                'local_iomad_company_licenses',
                ['id' => $licenseid],
                '*',
                MUST_EXIST
            );
            $parentid = $license->parentid;
        }

        // Get the potential courses.
        if (empty($parentid)) {
            $courses = $company->get_menu_courses(true, false, false, false, true);
        } else {
            $courses = $DB->get_records_sql_menu(
                "SELECT c.id, c.fullname
                   FROM {course} c
                   JOIN {local_iomad_company_license_courses} lic
                     ON (c.id = lic.courseid)
                  WHERE lic.licenseid = :licenseid",
                ['licenseid' => $parentid]);
        }

        // Set up the form.
        $mform =& $this->_form;

        // Add the hidden elements.
        $mform->addElement('hidden', 'companyid');
        $mform->addElement('hidden', 'licenseid');
        $mform->addElement('hidden', 'parentid');
        $mform->setType('companyid', PARAM_INT);
        $mform->setType('licenseid', PARAM_INT);
        $mform->setType('parentid', PARAM_INT);

        if (empty($parentid)) {
            $mform->addElement('hidden', 'designatedcompany', 0);
            $mform->setType('designatedcompany', PARAM_INT);
        } else {
            $parentlicense = $DB->get_record('local_iomad_company_licenses', ['id' => $parentid]);

            // If this is a program, sort out the displayed used and allocated.
            if (!empty($parentlicense->program)) {
                $used = $parentlicense->used / count($courses);
                $free = ($parentlicense->allocation - $parentlicense->used) / count($courses);
            } else {
                $used = $parentlicense->used;
                $free = $parentlicense->allocation - $parentlicense->used;
            }

            $company = new company($parentlicense->companyid);
            $companylist = $company->get_child_companies_select(false);
            $mform->addElement(
                'static',
                'parentlicensename',
                get_string('parentlicensename', 'block_iomad_company_admin'),
                format_string($parentlicense->name));
            $mform->addElement(
                'static',
                'parentlicenseused',
                get_string('parentlicenseused', 'block_iomad_company_admin'),
                $used);
            $mform->addElement(
                'static',
                'parentlicenseavailable',
                get_string('parentlicenseavailable', 'block_iomad_company_admin'),
                $free);

            // Add in the selector for the company the license will be for.
            $designatedcompanyselect = $mform->addElement(
                'select',
                'designatedcompany',
                get_string('designatedcompany', 'block_iomad_company_admin'),
                $companylist);
            if (!empty($license->companyid)) {
                $designatedcompanyselect->setSelected($license->companyid);
            }
        }

        $mform->addElement(
            'text',
            'name',
            get_string('licensename', 'block_iomad_company_admin'),
            'maxlength="254" size="50"');
        $mform->addHelpButton('name', 'licensename', 'block_iomad_company_admin');
        $mform->addRule(
            'name',
            get_string('missinglicensename', 'block_iomad_company_admin'),
            'required',
            null,
            'client'
        );
        $mform->setType('name', PARAM_ALPHANUMEXT);

        $mform->addElement('text',  'reference', get_string('licensereference', 'block_iomad_company_admin'),
                           'maxlength="100" size="50"');
        $mform->addHelpButton('reference', 'licensereference', 'block_iomad_company_admin');
        $mform->setType('reference', PARAM_ALPHANUMEXT);

        if (empty($parentid)) {
            $licensetypes = [
                0 => get_string('standard', 'block_iomad_company_admin'),
                1 => get_string('reusable', 'block_iomad_company_admin'),
                2 => get_string('educator', 'block_iomad_company_admin'),
                3 => get_string('educatorreusable', 'block_iomad_company_admin'),
                4 => get_string('blanket', 'block_iomad_company_admin'),
            ];
            if (get_config('local_iomad', 'autoenrol_managers')) {
                // Strip out educator licenses.
                unset($licensetypes[2]);
                unset($licensetypes[3]);
            }

            $mform->addElement('select', 'type', get_string('licensetype', 'block_iomad_company_admin'), $licensetypes);
            $mform->addHelpButton('type', 'licensetype', 'block_iomad_company_admin');
            $mform->addElement('selectyesno', 'program', get_string('licenseprogram', 'block_iomad_company_admin'));
            $mform->addHelpButton('program', 'licenseprogram', 'block_iomad_company_admin');
            $mform->addElement('selectyesno', 'instant', get_string('licenseinstant', 'block_iomad_company_admin'));
            $mform->addHelpButton('instant', 'licenseinstant', 'block_iomad_company_admin');
            $mform->addElement('date_selector', 'startdate', get_string('licensestartdate', 'block_iomad_company_admin'));

            // Disable things depending on license type.
            $mform->disabledIf('program', 'type', 'eq', 4);
            $mform->disabledIf('instant', 'type', 'eq', 4);

            $mform->addHelpButton('startdate', 'licensestartdate', 'block_iomad_company_admin');
            $mform->addRule('startdate', get_string('missingstartdate', 'block_iomad_company_admin'),
                            'required', null, 'client');

            $mform->addElement('date_selector', 'expirydate', get_string('licenseexpires', 'block_iomad_company_admin'));
            $mform->addHelpButton('expirydate', 'licenseexpires', 'block_iomad_company_admin');
            $mform->addRule('expirydate', get_string('missinglicenseexpires', 'block_iomad_company_admin'),
                            'required', null, 'client');

            $mform->addElement(
                'date_selector',
                'cutoffdate',
                get_string('licensecutoffdate', 'block_iomad_company_admin'),
                ['optional' => true]);
            $mform->addHelpButton('cutoffdate', 'licensecutoffdate', 'block_iomad_company_admin');
            $mform->disabledIf('cutoffdate', 'type', 'eq', 1);
            $mform->disabledIf('cutoffdate', 'type', 'eq', 3);

            $mform->addElement('advcheckbox', 'clearonexpire', get_string('clearonexpire', 'block_iomad_company_admin'));

            $mform->addHelpButton('clearonexpire', 'clearonexpire', 'block_iomad_company_admin');
            $mform->disabledIf('clearonexpire', 'type', 'eq', 1);
            $mform->disabledIf('clearonexpire', 'type', 'eq', 3);
            $mform->disabledIf('clearonexpire', 'cutoffdate[enabled]');

            $mform->addElement('text', 'validlength', get_string('licenseduration', 'block_iomad_company_admin'),
                               'maxlength="254" size="50"');
            $mform->addHelpButton('validlength', 'licenseduration', 'block_iomad_company_admin');
            $mform->setType('validlength', PARAM_INTEGER);
        } else {
            $mform->addElement('hidden', 'type', $parentlicense->type);
            $mform->setType('type', PARAM_INT);
            $mform->addElement('hidden', 'startdate', $parentlicense->startdate);
            $mform->setType('startdate', PARAM_INT);
            $mform->addElement('hidden', 'expirydate', $parentlicense->expirydate);
            $mform->setType('expirydate', PARAM_INT);
            $mform->addElement('hidden', 'validlength', $parentlicense->validlength);
            $mform->setType('validlength', PARAM_INTEGER);
            $mform->addElement('hidden', 'program', $parentlicense->program);
            $mform->setType('program', PARAM_INTEGER);
            $mform->addElement('hidden', 'parentid', $parentlicense->id);
            $mform->setType('parentid', PARAM_INTEGER);
        }

        $mform->addElement(
            'text',
            'allocation',
            get_string('licenseallocation', 'block_iomad_company_admin'),
            'maxlength="254" size="50"');
        $mform->addHelpButton('allocation', 'licenseallocation', 'block_iomad_company_admin');
        $mform->addRule(
            'allocation',
            get_string('missinglicenseallocation', 'block_iomad_company_admin'),
            'required',
            null,
            'client');
        $mform->setType('allocation', PARAM_MULTILANG);

        $mform->addElement('hidden', 'courseselector', 0);
        $mform->setType('courseselector', PARAM_INT);

        if (!empty($parentlicense->program)) {
            $mform->addElement('html', html_writer::start_div(['style' => 'display:none']));
        }
        $autooptions = ['multiple' => true];
        $mform->addElement(
            'autocomplete',
            'licensecourses',
            get_string('courses'),
            $courses, $autooptions);
        $mform->addRule('licensecourses', get_string('missinglicensecourses', 'block_iomad_company_admin'),
                        'required', null, 'client');

        // If we are not a child of a program license then show all of the courses.
        if (!empty($parentlicense->program)) {
            $mform->addElement('html', html_writer::end_div());
        }
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = [];

        // Check the name is valid.
        $name = clean_param(trim($data['name']), PARAM_ALPHANUMEXT);
        if (empty($name)) {
            $errors['name'] = get_string('invalidlicensename', 'block_iomad_company_admin');
        }

        // Check that the amount of free licenses slots is more than the amount being allocated.
        if (!empty($data['licenseid'])) {
            $currentlicense = $DB->get_record('local_iomad_company_licenses', ['id' => $data['licenseid']]);
            if (!empty($currentlicense->program)) {
                // Used count comes from the number of currently allocated courses.  Not those being passed.
                $coursecount = $DB->count_records(
                    'local_iomad_company_license_courses',
                    ['licenseid' => $currentlicense->id]
                );
                $used = $currentlicense->used / $coursecount;
            } else {
                $used = $currentlicense->used;
            }
            if ($used > $data['allocation']) {
                $errors['allocation'] = get_string('licensenotenough', 'block_iomad_company_admin');
            }
        }

        // Check the dates are sensible.
        if ($data['startdate'] > $data['expirydate']) {
            $errors['startdate'] = get_string('invalidstartdate', 'block_iomad_company_admin');
        }

        // Check that the amount of free parent licenses slots is more than the amount being allocated.
        if (!empty($data['parentid'])) {
            $parentlicense = $DB->get_record('local_iomad_company_licenses', ['id' => $data['parentid']]);

            // Check if this is a new license or we are updating it.
            if (!empty($data['licenseid'])) {
                $currparentlicense = $DB->get_record('local_iomad_company_licenses', ['id' => $data['licenseid']]);
                $weighting = $currparentlicense->allocation;
            } else {
                $weighting = 0;
            }
            $free = $parentlicense->allocation - $parentlicense->used + $weighting;

            // How manay license do we actually need?
            if (!empty($data['program'])) {
                $required = $data['allocation'] * count($data['licensecourses']);
            } else {
                $required = $data['allocation'];
            }

            // Check if we have enough.
            if ($required > $free) {
                $errors['allocation'] = get_string('licensenotenough', 'block_iomad_company_admin');
            }

            // Check if we have a designated company.
            if (empty($data['designatedcompany'])) {
                $errors['designatedcompany'] = get_string('invalid_company', 'block_iomad_company_admin');
            }
        }

        // Allocation needs to be an integer.
        if (!preg_match('/^\d+$/', $data['allocation'])) {
            $errors['allocation'] = get_string('notawholenumber', 'block_iomad_company_admin');
        }

        // Did we get passed any courses?
        if (empty($data['licensecourses'])) {
            $errors['licensecourses'] = get_string('select_license_courses', 'block_iomad_company_admin');
        }

        // Non blanket or reusable licenses need to have a duration set.
        if (($data['type'] == 1 || $data['type'] == 3) &&
            empty($data['validlength']) &&
            empty($data['cutoffdate'])) {
            $errors['validlength'] = get_string('missinglicenseduration', 'block_iomad_company_admin');
        }

        // Is the value for length appropriate?
        if (empty($data['type']) && $data['validlength'] < 1 ) {
            if (empty($data['validlength'])) {
                $errors['validlength'] = get_string('missingvalidlength', 'block_iomad_company_admin');
            } else {
                $errors['validlength'] = get_string('invalidnumber', 'block_iomad_company_admin');
            }
        }

        // Did we get passed an allocation?
        if ($data['allocation'] < 1 ) {
            $errors['allocation'] = get_string('invalidnumber', 'block_iomad_company_admin');
        }

        // Is expiry date valid?
        if ($data['expirydate'] < time()) {
            $errors['expirydate'] = get_string('errorinvaliddate', 'calendar');
        }

        // Check the type hasn't been forced somewhere when it's not available.
        if (get_config('local_iomad', 'autoenrol_managers') &&
            $data['type'] > 1 &&
            $data['type'] < 4) {
            $errors['type'] = get_string('invalid');
        }

        return $errors;
    }

    /**
     * Process the form submission, used if form was submitted via AJAX.
     *
     * @return array
     */
    public function process_dynamic_submission(): array {
        global $DB, $USER;

        // Get the info from the form.
        $data = $this->get_data();
        $returnmessage = "";

        // Deal with default data.
        $companycontext = context_company::instance($data->companyid);
        $licenseid = $data->licenseid;

        // Set some defaults.
        if (empty($data->instant)) {
            $data->instant = 0;
        }
        $new = false;
        $licensedata = [];

        // Sanitise the data.
        $licensedata['name'] = trim($data->name);
        $licensedata['reference'] = trim($data->reference);
        if (empty($data->program)) {
            $licensedata['program'] = 0;
            $licensedata['allocation'] = $data->allocation;
        } else {
            $licensedata['program'] = $data->program;
            $licensedata['allocation'] = $data->allocation * count($data->licensecourses);
        }
        $licensedata['humanallocation'] = $data->allocation;
        $licensedata['instant'] = $data->instant;
        $licensedata['expirydate'] = $data->expirydate;
        $licensedata['startdate'] = $data->startdate;

        if (empty($data->languages)) {
            $data->languages = [];
        }

        if (empty($data->parentid)) {
            $licensedata['companyid'] = $data->companyid;
        } else {
            $licensedata['companyid'] = $data->designatedcompany;
            $licensedata['parentid'] = $data->parentid;
        }
        $licensedata['validlength'] = $data->validlength;
        $licensedata['type'] = $data->type;

        if (empty($data->cutoffdate)) {
            $licensedata['cutoffdate'] = 0;
        } else {
            $licensedata['cutoffdate'] = $data->cutoffdate;
        }

        if (empty($data->clearonexpire)) {
            $licensedata['clearonexpire'] = 0;
        } else {
            $licensedata['clearonexpire'] = $data->clearonexpire;
        }

        // Update/create the license.
        if (!empty($licenseid) &&
            $currlicensedata = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // Already in the table update it.
            $new = false;
            $licensedata['id'] = $currlicensedata->id;
            $licensedata['used'] = $currlicensedata->used;
            $DB->update_record('local_iomad_company_licenses', $licensedata);
        } else {
            // New license being created.
            $new = true;
            $licensedata['used'] = 0;
            $licenseid = $DB->insert_record('local_iomad_company_licenses', $licensedata);
        }

        // Deal with course allocations if there are any.
        // Capture them for checking.
        $oldcourses = $DB->get_records('local_iomad_company_license_courses', ['licenseid' => $licenseid], null, 'courseid');

        // Clear down all of them initially.
        $DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $licenseid]);
        if (!empty($data->licensecourses)) {
            // Add the course license allocations.
            foreach ($data->licensecourses as $selectedcourse) {
                $DB->insert_record(
                    'local_iomad_company_license_courses',
                    ['licenseid' => $licenseid, 'courseid' => $selectedcourse]
                );
            }
        }

        // Create an event to deal with an parent license allocations.
        $eventother = ['licenseid' => $licenseid,
                       'parentid' => $data->parentid];

        if ($new) {
            $event = company_license_created::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $licenseid,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('licensecreatedok', 'block_iomad_company_admin');
        } else {
            $eventother['oldcourses'] = json_encode($oldcourses);
            if ($currlicensedata->program != $data->program) {
                $eventother['programchange'] = true;
            }
            if ($currlicensedata->startdate != $data->startdate) {
                $eventother['oldstartdate'] = $currlicensedata->startdate;
            }
            if ($currlicensedata->type != $data->type) {
                $eventother['educatorchange'] = true;
            }
            $event = company_license_updated::create([
                'context' => $companycontext,
                'userid' => $USER->id,
                'objectid' => $licenseid,
                'other' => $eventother,
            ]);
            $returnmessage = get_string('licenseupdatedok', 'block_iomad_company_admin');
        }

        // Fire the event and redirect.
        $event->trigger();
        notification::success($returnmessage);

        // Return stuff to the JS.
        return [
            'result' => true,
            'returnmessage' => '',
        ];
    }

    /**
     * Load in existing data as form defaults (not applicable).
     *
     * @return void
     */
    public function set_data_for_dynamic_submission(): void {
        global $DB;

        // Set some defaults.
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $licenseid = $this->optional_param('licenseid', 0, PARAM_INT);
        $parentid = $this->optional_param('parentid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);
        $company = new company($companyid);

        // Do we have an existing record?
        if (empty($licenseid)) {
            $data = (object) [
                'expirydate' => strtotime('+ 1 year'),
                'cutoffdate' => strtotime('+ 1 year'),
                ];

            // Are we splitting a current license?
            if (!empty($parentid)) {
                // Get the courses from that.
                if ($currentcourses = $DB->get_records(
                    'local_iomad_company_license_courses',
                    ['licenseid' => $parentid],
                    null,
                    'courseid')) {
                    $data->licensecourses = array_keys($currentcourses);
                }
            }
        } else {
            // Get the license record and populate everything we need for the form.
            $data = $DB->get_record(
                'local_iomad_company_licenses',
                ['id' => $licenseid],
                '*',
                MUST_EXIST
            );
            $parentid = $data->parentid;

            // Get any allocated courses.
            $currentcourses = $DB->get_records(
                'local_iomad_company_license_courses',
                ['licenseid' => $licenseid],
                null,
                'courseid'
            );
            $data->licensecourses = array_keys($currentcourses);

            // Deal with the amount for program courses.
            if (!empty($data->program)) {
                $data->allocation = $data->allocation / count($currentcourses);
            }
        }

        // Can we even do this?
        if (empty($parentid)) {
            if (!empty($licenseid) && $company->is_child_license($licenseid)) {
                iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
            } else {
                iomad::require_capability('block/iomad_company_admin:edit_licenses', $companycontext);
            }
        } else {
            iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext);
        }

        // Send it.
        $data->companyid = $companyid;
        $data->licenseid = $licenseid;
        $data->parentid = $parentid;
        $this->set_data($data);
    }

    /**
     * Check if current user has access to this form, otherwise throw exception.
     *
     * @return void
     * @throws moodle_exception
     */
    protected function check_access_for_dynamic_submission(): void {
        global $CFG, $DB;

        // Set some defaults.
        $licenseid = $this->optional_param('licenseid', 0, PARAM_INT);
        $parentid = $this->optional_param('parentid', 0, PARAM_INT);
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = $this->get_context_for_dynamic_submission();
        $returnurl = new moodle_url($CFG->wwwroot . '/blocks/iomad_company_admin/company_license_list.php');
        $company = new company($companyid);

        // Is there a current license?
        if (!empty($licenseid)) {
            $license = $DB->get_record(
                'local_iomad_company_licenses',
                ['id' => $licenseid],
                '*',
                MUST_EXIST
            );
            $parentid = $license->parentid;
        }

        // Check the capabilities.
        if (empty($parentid)) {
            if (!empty($licenseid) && $company->is_child_license($licenseid)) {
                if (!iomad::has_capability('block/iomad_company_admin:edit_my_licenses', $companycontext)) {
                    throw new moodle_exception(
                        'nopermissions',
                        '',
                        $returnurl->out(),
                        get_string(
                            'block/iomad_company_admin:edit_my_licenses',
                            'block_iomad_company_admin'
                        )
                    );

                }
            } else {
                if (!iomad::has_capability('block/iomad_company_admin:edit_licenses', $companycontext)) {
                    throw new moodle_exception(
                        'nopermissions',
                        '',
                        $returnurl->out(),
                        get_string(
                            'block/iomad_company_admin:edit_licenses',
                            'block_iomad_company_admin'
                        )
                    );
                }
            }
        } else {
            if (iomad::require_capability('block/iomad_company_admin:edit_my_licenses', $companycontext)) {
                throw new moodle_exception(
                    'nopermissions',
                    '',
                    $returnurl->out(),
                    get_string(
                        'block/iomad_company_admin:edit_my_licenses',
                        'block_iomad_company_admin'
                    )
                );
            }
        }
    }

    /**
     * Return form context
     *
     * @return context
     */
    protected function get_context_for_dynamic_submission(): context {
        $companyid = $this->optional_param('companyid', 0, PARAM_INT);
        $companycontext = context_company::instance($companyid);

        return $companycontext;
    }

    /**
     * Returns url to set in $PAGE->set_url() when form is being rendered or submitted via AJAX.
     *
     * @return moodle_url
     */
    protected function get_page_url_for_dynamic_submission(): moodle_url {

        return new moodle_url('/blocks/iomad_company_admin/company_license_list.php');
    }
}
