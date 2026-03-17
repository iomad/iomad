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
 * IOMAD Dashboard user edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use context_coursecat;
use core_text;
use core_user;
use local_iomad\{company, company_user, iomad};
use moodleform;
use html_writer;

/**
 * IOMAD Dashboard user edit form class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_edit_form extends moodleform {

    /** @var int company ID */
    protected $context = null;

    /** @var object user course selector */
    protected $courseselector = null;

    /** @var int company ID */
    protected $company = null;

    /** @var int company ID */
    protected $departmentid = 0;

    /** @var int company ID */
    protected $companyname = '';

    /** @var int company ID */
    protected $licenseid = 0;

    /** @var int company ID */
    protected $subhierarchieslist = [];

    /** @var int company ID */
    protected $selectedcompany;

    /** @var int company ID */
    protected $companycontext;

    /** @var int company ID */
    protected $companydepartment;

    /** @var int company ID */
    protected $treehtml;

    /** @var int company ID */
    protected $userdepartment;

    /** @var int company ID */
    protected $companycourses;

    /**
     * Constructor function
     *
     * @param moodle_url $actionurl
     * @param int $companyid
     * @param int $departmentid
     * @param int $licenseid
     */
    public function __construct($actionurl, $companyid, $departmentid, $licenseid=0) {
        global $CFG, $USER, $output, $companycontext;

        $this->selectedcompany = $companyid;
        $this->departmentid = $departmentid;
        $this->licenseid = $licenseid;
        $company = new company($this->selectedcompany);
        $this->company = $company;
        $this->companyname = $company->get_name();
        $this->companycontext = $companycontext;
        $parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $parentlevel->id;
        $departmenttree = [];

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $this->companycontext)) {
            $userhierarchylevel = $parentlevel->id;
            $userlevels = [$parentlevel->id => $parentlevel->id];
        } else {
            $userlevels = $company->get_userlevel($USER);
            $userhierarchylevel = key($userlevels);
        }
        foreach ($userlevels as $userlevelid => $userlevel) {
            $this->subhierarchieslist = $this->subhierarchieslist + company::get_all_subdepartments($userlevelid);
            $departmenttree[] = company::get_all_subdepartments_raw($userlevelid);
        }
        $this->treehtml = $output->department_tree($departmenttree, optional_param('deptid', 0, PARAM_INT));

        if ($this->departmentid == 0) {
            $departmentid = $userhierarchylevel;
        } else {
            $departmentid = $this->departmentid;
        }
        $this->userdepartment = $userhierarchylevel;
        $this->companycourses = $this->company->get_menu_courses(true, true);
        unset($this->companycourses[0]);
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);

        parent::__construct($actionurl);
    }

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $CFG, $DB;

        // Set up the form.
        $mform =& $this->_form;

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);
        $strrequired = get_string('required');

        // Deal with the name order sorting and required fields.
        $necessarynames = useredit_get_required_name_fields();
        foreach ($necessarynames as $necessaryname) {
            $mform->addElement('text', $necessaryname, get_string($necessaryname), 'maxlength="100" size="30"');
            $mform->addRule($necessaryname, $strrequired, 'required', null, 'client');
            $mform->setType($necessaryname, PARAM_NOTAGS);
        }
        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"');
        $mform->addRule('email', $strrequired, 'required', null, 'client');
        $mform->setType('email', PARAM_EMAIL);
        if (!empty(get_config('local_iomad', 'allow_username'))) {
            $mform->addElement('text', 'username', get_string('username'), 'size="20"');
            $mform->addHelpButton('username', 'username', 'auth');
            $mform->setType('username', PARAM_RAW);
            $mform->disabledif('username', 'use_email_as_username', 'eq', 1);
        }
        $mform->addElement(
            'advcheckbox',
            'use_email_as_username',
            get_string('iomad_use_email_as_username', 'local_iomad'));
        if (!empty(get_config('local_iomad', 'use_email_as_username'))) {
            $mform->setDefault('use_email_as_username', 1);
        } else {
            $mform->setDefault('use_email_as_username', 0);
        }

        // Copied from /user/editlib.php.

        $mform->addElement('static', 'blankline', '', '');
        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'), 'size="20"');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', PARAM_RAW);
        $mform->addElement('static', 'generatepassword', '',
                            get_string('leavepasswordemptytogenerate', 'block_iomad_company_admin'));

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');
        $mform->setDefault('preference_auth_forcepasswordchange', 1);

        $mform->addElement('selectyesno', 'sendnewpasswordemails',
                            get_string('sendnewpasswordemails', 'block_iomad_company_admin'));
        $mform->setDefault('sendnewpasswordemails', 1);
        $mform->disabledIf('sendnewpasswordemails', 'newpassword', 'eq', '');

        $mform->addElement('date_time_selector', 'due', get_string('senddate', 'block_iomad_company_admin'));
        $mform->disabledIf('due', 'sendnewpasswordemails', 'eq', '0');
        $mform->addHelpButton('due', 'senddate', 'block_iomad_company_admin');

        // Deal with company optional fields.
        $mform->addElement('header', 'category_id', get_string('advanced'));
        $mform->addElement('static', 'departmenttext', get_string('department', 'block_iomad_company_admin'));
        $mform->addElement('html', $this->treehtml);
        $mform->addElement(
            'select',
            'deptid',
            get_string('department', 'block_iomad_company_admin'),
            $this->subhierarchieslist,
            0);
        $mform->addElement('html', html_writer::empty_tag('br'));

        // Add in company/department manager checkboxes.
        // Deal with role selector.
        $usertypeselect = ['0' => get_string('user', 'block_iomad_company_admin')];
        if (iomad::has_capability('block/iomad_company_admin:assign_company_manager', $this->companycontext)) {
            $usertypeselect[10] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $this->companycontext)) {
            $usertypeselect[20] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        if (iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $this->companycontext)) {
            $usertypeselect[40] = get_string('companyreporter', 'block_iomad_company_admin');
        }
        if (!get_config('local_iomad', 'autoenrol_managers')) {
            $usertypeselect[1] = get_string('educator', 'block_iomad_company_admin');
            if (iomad::has_capability('block/iomad_company_admin:assign_company_manager', $this->companycontext)) {
                $usertypeselect[11] = format_string(
                    get_string('companymanager', 'block_iomad_company_admin') .
                    ' + ' .
                    get_string('educator', 'block_iomad_company_admin')
                );
            }
            if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $this->companycontext)) {
                $usertypeselect[21] = format_string(
                    get_string('departmentmanager', 'block_iomad_company_admin') .
                    ' + ' .
                    get_string('educator', 'block_iomad_company_admin')
                );
            }
            if (iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $this->companycontext)) {
                $usertypeselect[41] = format_string(
                    get_string('companyreporter', 'block_iomad_company_admin') .
                    ' + ' .
                    get_string('educator', 'block_iomad_company_admin')
                );
            }
        }
        ksort($usertypeselect);
        if (!empty($usertypeselect)) {
            $mform->addElement(
                'select',
                'managertype',
                get_string('managertype', 'block_iomad_company_admin'),
                $usertypeselect,
                0);
        } else {
            $mform->addElement('hidden', 'managertype', 0);
        }

        // Optional profile fields.
        $mform->addElement('header', 'profile_id', get_string('profilefields', 'mnet'));
        // Get global fields.
        if ($fields = $DB->get_records_sql("SELECT * FROM {user_info_field}
                                            WHERE categoryid NOT IN (
                                             SELECT profilecategoryid FROM {local_iomad_companies})")) {
            // Display the header and the fields.
            foreach ($fields as $field) {
                require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                $newfield = 'profile_field_'.$field->datatype;
                $formfield = new $newfield($field->id);
                $formfield->edit_field($mform);
                $mform->setDefault($formfield->inputname, $formfield->field->defaultdata);
            }
        }
        // Get company category.
        if ($companyinfo = $DB->get_record('local_iomad_companies', ['id' => $this->selectedcompany])) {

            // Get fields from company category.
            if ($fields = $DB->get_records('user_info_field', ['categoryid' => $companyinfo->profilecategoryid])) {
                // Display the header and the fields.
                foreach ($fields as $field) {
                    require_once($CFG->dirroot.'/user/profile/field/'.$field->datatype.'/field.class.php');
                    $newfield = 'profile_field_'.$field->datatype;
                    $formfield = new $newfield($field->id);
                    $formfield->edit_field($mform);
                    $mform->setDefault($formfield->inputname, $formfield->field->defaultdata);
                }
            }
        }

        // Deal with licenses.
        if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $this->companycontext)) {
            $mform->addElement('header', 'licenses', get_string('assignlicenses', 'block_iomad_company_admin'));
            $foundlicenses = $DB->get_records_sql_menu(
                "SELECT id, name
                 FROM {local_iomad_company_licenses}
                 WHERE expirydate >= :timestamp
                 AND companyid = :companyid
                 AND used < allocation",
                ['timestamp' => time(),
                 'companyid' => $this->selectedcompany]);
            $licenses = ['0' => get_string('nolicense', 'block_iomad_company_admin')] + $foundlicenses;
            $licensecourses = [];
            if (count($foundlicenses) == 0) {
                // No valid licenses.
                $mform->addElement(
                    'html',
                    html_writer::tag(
                        'div',
                        html_writer::tag(
                            'b',
                            get_string('nolicenses', 'block_iomad_company_admin')
                        ),
                        [
                            'id' => 'licensedetails',
                        ]
                        )
                    );
            } else {
                $mform->addElement(
                    'select',
                    'licenseid',
                    get_string('select_license', 'block_iomad_company_admin'),
                    $licenses,
                    ['id' => 'licenseidselector']);
                $mylicenseid = $this->licenseid;

                if (!empty($this->licenseid)) {
                    $mylicensedetails = $DB->get_record('local_iomad_company_licenses', ['id' => $this->licenseid]);
                    $usedcount = $mylicensedetails->used;
                    // Is this a program license?
                    if (!empty($mylicense->program) && !empty($usedcount)) {
                        $licensecourses = $DB->count_records(
                            'local_iomad_company_license_courses',
                            ['licenseid' => $this->licenseid]
                        );
                        if (!empty($licensecourses)) {
                            $usedcount = $usedcount / $licensecourses;
                        } else {
                            $usedcount = 0;
                        }
                    }
                    $remainder = $mylicensedetails->humanallocation - $usedcount;
                    $mform->addElement(
                        'html',
                        html_writer::tag(
                            'div',
                            html_writer::tag(
                                'b',
                                format_string(
                                    get_string('licenseleft1', 'block_iomad_company_admin') .
                                    $remainder .
                                    get_string('licenseleft2', 'block_iomad_company_admin')
                                    ),
                            ),
                            [
                                'id' => 'licensedetails',

                            ]
                            ));
                } else {
                    $mform->addElement(
                        'html',
                        html_writer::tag(
                            'div',
                            '',
                            [
                                'id' => "licensedetails",
                                'style' => 'display: none;',
                            ]
                        ));
                }

                // Get the license courses.
                if (!$licensecourses = $DB->get_records_sql_menu(
                    "SELECT c.id, c.fullname
                     FROM {local_iomad_company_license_courses} clc
                     JOIN {course} c ON (clc.courseid = c.id
                     AND clc.licenseid = :licenseid)
                     ORDER BY c.fullname",
                    ['licenseid' => $mylicenseid])) {
                    $licensecourses = [];
                }
            }

            // Is this a program of courses?
            if (!empty($mylicensedetails->program)) {
                 $mform->addElement('html', html_writer::start_tag('div', ['style' => 'display:none']));
            }

            // Add the license course selector.
            $mform->addElement(
                'html',
                html_writer::start_tag(
                    'div',
                    [
                        'id' => "licensecoursescontainer",
                        'style' => 'display: none;',
                    ]
                ));
            $licensecourseselect = $mform->addElement(
                'select',
                'licensecourses',
                get_string('select_license_courses', 'block_iomad_company_admin'),
                $licensecourses,
                ['id' => 'licensecourseselector']);
            $licensecourseselect->setMultiple(true);
            $mform->addElement('html', html_writer::end_tag('div'));

            // Set the selected courses.
            if (!empty($mylicensedetails->program)) {
                $licensecourseselect->setSelected($licensecourses);
            } else {
                $licensecourseselect->setSelected([]);
            }

            // If this is a program of courses - end the hidden div.
            if (!empty($mylicensedetails->program)) {
                $mform->addElement('html', html_writer::end_tag('div'));
            }
        }

        // Deal with manual enrolment courses.
        if (iomad::has_capability('block/iomad_company_admin:company_course_users', $this->companycontext)) {
            $mform->addElement('header', 'courses', get_string('assigncourses', 'block_iomad_company_admin'));
            $autooptions = ['multiple' => true,
                            'noselectionstring' => get_string('none')];
            $mform->addElement('autocomplete',
                               'currentcourses',
                               get_string('selectenrolmentcourse', 'block_iomad_company_admin'),
                               $this->companycourses,
                               $autooptions);
        }

        // Disable the onchange popup.
        $mform->disable_form_change_checker();

        // Add action buttons.
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                            get_string('createuseragain', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('submit', 'submitandback',
                            get_string('createuserandback', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     * Get the form data
     *
     * @return array
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            if ($this->courseselector) {
                $data->selectedcourses = $this->courseselector->get_selected_courses();
            }
        }
        return $data;
    }

    /**
     * Form validation
     *
     * @param array $usernew
     * @param array $files
     * @return void
     */
    public function validation($usernew, $files) {
        global $CFG, $DB;

        $errors = parent::validation($usernew, $files);

        $usernew = (object)$usernew;

        // Check allowed characters. - We only care if we are being passed a username.
        if (!empty(get_config('local_iomad', 'allow_username'))) {
            if (!$usernew->use_email_as_username) {
                if (empty($usernew->username)) {
                    $errors['username'] = get_string('required');
                } else if ($usernew->username !== core_text::strtolower($usernew->username)) {
                    $errors['username'] = get_string('usernamelowercase');
                } else if ($usernew->username !== core_user::clean_field($usernew->username, 'username')) {
                        $errors['username'] = get_string('invalidusername');
                } else if ($DB->get_records_sql(
                    "SELECT cu.userid
                     FROM {company_users} cu
                     JOIN {user} u ON (cu.userid = u.id)
                     WHERE cu.companyid = :companyid
                     AND u.username = :username",
                    ['username' => $usernew->username,
                     'companyid' => $this->company->id])) {
                    $errors['username'] = get_string('usernameexists');
                }
            }
        }

        // Validate email.
        if ($existingusers = $DB->get_records('user', ['email' => $usernew->email, 'mnethostid' => $CFG->mnet_localhost_id])) {
            foreach ($existingusers as $existinguser) {
                if ($DB->record_exists(
                    'local_iomad_company_users',
                    ['userid' => $existinguser->id, 'companyid' => $this->company->id])) {
                    if (empty($CFG->allowaccountssameemail)) {
                        $errors['email'] = get_string('emailexists');
                        break;
                    }
                }
            }
        }

        // Validate email as username in the same company.
        if ($usernew->use_email_as_username) {
            if ($DB->get_records_sql("SELECT u.id FROM {user} u
                                      JOIN {local_iomad_company_users} cu ON u.id = cu.userid
                                      WHERE cu.companyid = :companyid
                                      AND u.username = :email",
                                      ['companyid' => $this->company->id,
                                       'email' => $usernew->email])) {
                        $errors['email'] = get_string('emailexists');
            }
        }

        if (!empty($usernew->newpassword)) {
            $errmsg = ''; // Prevent eclipse warning.
            if (!check_password_policy($usernew->newpassword, $errmsg)) {
                $errors['newpassword'] = $errmsg;
            }
        }

        // It is insecure to send passwords by email without forcing them to be changed on first login.
        if (!$usernew->preference_auth_forcepasswordchange && $usernew->sendnewpasswordemails) {
            $errors['preference_auth_forcepasswordchange'] = get_string(
                'sendemailsforcepasswordchange',
                'block_iomad_company_admin',
                [
                    'forcechange' => get_string('forcepasswordchange'),
                    'sendemail' => get_string('sendnewpasswordemails',
                    'block_iomad_company_admin')]);
        }

        // Check numbers of licensed courses against license.
        if (!empty($usernew->licenseid)) {
            $license = $DB->get_record('local_iomad_company_licenses', ['id' => $usernew->licenseid]);

            // Are we dealing with a program license?
            if (!empty($license->program)) {
                // If so the courses are not passed automatically.
                $usernew->licensecourses = $DB->get_records_sql_menu(
                    "SELECT c.id, c.fullname
                     FROM {local_iomad_company_license_courses} clc
                     JOIN {course} c ON (clc.courseid = c.id
                     AND clc.licenseid = :licenseid)",
                    ['licenseid' => $license->id]);
            }

            if (!empty($usernew->licensecourses)) {
                if ($license = $DB->get_record('local_iomad_company_licenses', ['id' => $usernew->licenseid])) {
                    if (count($usernew->licensecourses) + $license->used > $license->allocation) {
                        $errors['licensecourses'] = get_string('triedtoallocatetoomanylicenses', 'block_iomad_company_admin');
                    }
                } else {
                    $errors['licenseid'] = get_string('invalidlicense', 'block_iomad_company_admin');
                }
            }
        }

        return $errors;
    }
}
