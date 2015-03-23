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
 * Script to let a user create a user for a particular company.
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once('lib.php');

class user_edit_form extends company_moodleform {
    protected $title = '';
    protected $description = '';
    protected $context = null;
    protected $courseselector = null;
    protected $departmentid = 0;
    protected $licenseid = 0;
    protected $licensecourses = array();

    public function __construct($actionurl, $companyid, $departmentid, $licenseid=0) {
        global $CFG, $USER;

        $this->selectedcompany = $companyid;
        $this->departmentid = $departmentid;
        $this->licenseid = $licenseid;
        $company = new company($this->selectedcompany);
        $parentlevel = company::get_company_parentnode($company->id);
        $this->companydepartment = $parentlevel->id;
        $systemcontext = context_system::instance();

        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $systemcontext)) {
            $userhierarchylevel = $parentlevel->id;
        } else {
            $userlevel = company::get_userlevel($USER);
            $userhierarchylevel = $userlevel->id;
        }

        $this->subhierarchieslist = company::get_all_subdepartments($userhierarchylevel);
        if ($this->departmentid == 0) {
            $departmentid = $userhierarchylevel;
        } else {
            $departmentid = $this->departmentid;
        }
        $this->userdepartment = $userhierarchylevel;

        $options = array('context' => $this->context,
                         'multiselect' => true,
                         'companyid' => $this->selectedcompany,
                         'departmentid' => $departmentid,
                         'subdepartments' => $this->subhierarchieslist,
                         'parentdepartmentid' => $parentlevel,
                         'showopenshared' => true,
                         'license' => false);
        
        $this->currentcourses = new potential_subdepartment_course_selector('currentcourses', $options);
        $this->currentcourses->set_rows(20);
        $this->context = context_coursecat::instance($CFG->defaultrequestcategory);
        parent::moodleform($actionurl);
    }

    public function definition() {
        global $CFG, $DB;

        // Get the system context.
        $systemcontext = context_system::instance();

        $mform =& $this->_form;

        // Then show the fields about where this block appears.
        $mform->addElement('header', 'header', get_string('companyuser', 'block_iomad_company_admin'));

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);

        /* copied from /user/editlib.php */
        $strrequired = get_string('required');

        // Deal with the name order sorting and required fields.
        $necessarynames = useredit_get_required_name_fields();
        foreach ($necessarynames as $necessaryname) {
            $mform->addElement('text', $necessaryname, get_string($necessaryname), 'maxlength="100" size="30"');
            $mform->addRule($necessaryname, $strrequired, 'required', null, 'client');
            $mform->setType($necessaryname, PARAM_NOTAGS);
        } 

        // Do not show email field if change confirmation is pending.
        if (!empty($CFG->emailchangeconfirmation) and !empty($user->preference_newemail)) {
            $notice = get_string('auth_emailchangepending', 'auth_email', $user);
            $notice .= '<br /><a href="edit.php?cancelemailchange=1&amp;id='.$user->id.'">'
                    . get_string('auth_emailchangecancel', 'auth_email') . '</a>';
            $mform->addElement('static', 'emailpending', get_string('email'), $notice);
        } else {
            $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="30"');
            $mform->addRule('email', $strrequired, 'required', null, 'client');
            $mform->setType('email', PARAM_EMAIL);
        }
        /* /copied from /user/editlib.php */

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

        // Deal with company optional fields.
        $mform->addElement('header', 'category_id', format_string(get_string('companyprofilefields', 'block_iomad_company_admin')));
        // Department drop down.
        $mform->addElement('select', 'userdepartment', get_string('department', 'block_iomad_company_admin'),
                            $this->subhierarchieslist, $this->userdepartment);

        // Add in company/department manager checkboxes.
        $managerarray = array();
        if (iomad::has_capability('block/iomad_company_admin:assign_department_manager', $systemcontext)) {
            $managerarray['0'] = get_string('user', 'block_iomad_company_admin');
            $managerarray['2'] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        if (iomad::has_capability('block/iomad_company_admin:assign_company_manager', $systemcontext)) {
            if (empty($managearray)) {
                $managerarray['0'] = get_string('user', 'block_iomad_company_admin');
            }
            $managerarray['1'] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if (!empty($managerarray)) {
            $mform->addElement('select', 'managertype', get_string('managertype', 'block_iomad_company_admin'), $managerarray, 0);
        } else {
            $mform->addElement('hidden', 'managertype', 0);
        }

        // Get global fields.
        if ($fields = $DB->get_records_sql("SELECT * FROM {user_info_field}
                                            WHERE categoryid NOT IN (
                                             SELECT profileid FROM {company})")) {
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
        if ($companyinfo = $DB->get_record('company', array('id' => $this->selectedcompany))) {

            // Get fields from company category.
            if ($fields = $DB->get_records('user_info_field', array('categoryid' => $companyinfo->profileid))) {
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
        if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $systemcontext)) {
            $mform->addElement('header', 'licenses', get_string('licenses', 'block_iomad_company_admin'));
            $foundlicenses = $DB->get_records_sql_menu("SELECT id, name FROM {companylicense}
                                                   WHERE expirydate >= :timestamp
                                                   AND companyid = :companyid
                                                   AND used < allocation",
                                                   array('timestamp' => time(),
                                                         'companyid' => $this->selectedcompany));
            $licenses = array('0' => get_string('nolicense', 'block_iomad_company_admin')) + $foundlicenses;
            if (count($foundlicenses) == 0) {
                // No valid licenses.
                $mform->addElement('html', '<div id="licensedetails"><b>' . get_string('nolicenses', 'block_iomad_company_admin') . '</b></div>');
                $onlyone = true;
            } else {
                if (empty($this->licenseid) && count($foundlicenses) == 1) {
                    // There is only one so select it!
                    $onlyone = true;
                    unset($licenses[0]);
                    list($mylicenseid, $mylicensecourse) = each($licenses);
                    $mylicensedetails = $DB->get_record('companylicense', array('id' => $mylicenseid));
					$licensestring = get_string('licensedetails', 'block_iomad_company_admin', $mylicensedetails);
					$licensestring2 = get_string('licensedetails2', 'block_iomad_company_admin', $mylicensedetails);
					$licensestring3 = get_string('licensedetails3', 'block_iomad_company_admin', $mylicensedetails);
                    $mform->addElement('html', '<div id="licensedetails"><b>You have ' . ((intval($licensestring3, 0)) - (intval($licensestring2, 0))) . ' courses left to allocate on this license</b></div>');
                    $mform->addElement('hidden', 'licenseid', $mylicenseid);
                    $mform->setType('licenseid', PARAM_INT);
                } else {
                    $onlyone = false;
                    $mform->addElement('html', "<div class='fitem'><div class='fitemtitle'>" .
                                                get_string('selectlicensecourse', 'block_iomad_company_admin') .
                                                "</div><div class='felement'>");
                    $mform->addElement('select', 'licenseid', get_string('select_license', 'block_iomad_company_admin'), $licenses, array('id' => 'licenseidselector'));
                    $mylicenseid = $this->licenseid;
                    if (empty($this->licenseid)) {
                        $mform->addElement('html', '<div id="licensedetails"></div>');
                    } else {
                        $mylicensedetails = $DB->get_record('companylicense', array('id' => $this->licenseid));
						$licensestring = get_string('licensedetails', 'block_iomad_company_admin', $mylicensedetails);
						$licensestring2 = get_string('licensedetails2', 'block_iomad_company_admin', $mylicensedetails);
						$licensestring3 = get_string('licensedetails3', 'block_iomad_company_admin', $mylicensedetails);
                        $mform->addElement('html', '<div id="	"><b>You have ' . ((intval($licensestring3, 0)) - (intval($licensestring2, 0))) . ' courses left to allocate on this license </b></div>');
                    }
                }

                $licensecourses = $DB->get_records_sql_menu("SELECT c.id, c.fullname FROM {companylicense_courses} clc
                                                             JOIN {course} c ON (clc.courseid = c.id
                                                             AND clc.licenseid = :licenseid)",
                                                             array('licenseid' => $mylicenseid));

                $licensecourseselect = $mform->addElement('select', 'licensecourses',
                                                          get_string('select_license_courses', 'block_iomad_company_admin'),
                                                          $licensecourses, array('id' => 'licensecourseselector'));
                $licensecourseselect->setMultiple(true);
            }

            if (!$onlyone) {
                $mform->addElement('html', "</div></div>");
            }
        }

/*         if (iomad::has_capability('block/iomad_company_admin:company_course_users', $systemcontext)) {
            $mform->addElement('header', 'courses', get_string('courses', 'block_iomad_company_admin'));
            $mform->addElement('html', "<div class='fitem'><div class='fitemtitle'>" .
                                        get_string('selectenrolmentcourse', 'block_iomad_company_admin') .
                                        "</div><div class='felement'>");
            $mform->addElement('html', $this->currentcourses->display(true));
            $mform->addElement('html', "</div></div>");
        } */

        // add action buttons
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton',
                            get_string('createuseragain', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('submit', 'submitandback',
                            get_string('createuserandback', 'block_iomad_company_admin'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    public function get_data() {
        $data = parent::get_data();

        if ($data) {
            $data->title = '';
            $data->description = '';

            if ($this->title) {
                $data->title = $this->title;
            }

            if ($this->description) {
                $data->description = $this->description;
            }

            if ($this->courseselector) {
                $data->selectedcourses = $this->courseselector->get_selected_courses();
            }
        }
        return $data;
    }

    // Perform some extra moodle validation.
    /* copied from /user/edit_form.php */
    public function validation($usernew, $files) {
        global $CFG, $DB;

        $errors = parent::validation($usernew, $files);

        $usernew = (object)$usernew;

        // Validate email.
        if ($DB->record_exists('user', array('email' => $usernew->email, 'mnethostid' => $CFG->mnet_localhost_id))) {
            $errors['email'] = get_string('emailexists');
        }

        if (!empty($usernew->newpassword)) {
            $errmsg = ''; // Prevent eclipse warning.
            if (!check_password_policy($usernew->newpassword, $errmsg)) {
                $errors['newpassword'] = $errmsg;
            }
        }

        // It is insecure to send passwords by email without forcing them to be changed on first login.
        if (!$usernew->preference_auth_forcepasswordchange && $usernew->sendnewpasswordemails) {
            $errors['preference_auth_forcepasswordchange'] = get_string('sendemailsforcepasswordchange',
                                                                        'block_iomad_company_admin',
                                                             array('forcechange' => get_string('forcepasswordchange'),
                                                                   'sendemail' => get_string('sendnewpasswordemails',
                                                                   'block_iomad_company_admin')));
        }

        //  Check numbers of licensed courses against license.
        if (!empty($usernew->licenseid)) {
            if ($license = $DB->get_record('companylicense', array('id' => $usernew->licenseid))) {
                if (count($usernew->licensecourses) + $license->used > $license->allocation) {
                    $errors['licensecourses'] = get_string('triedtoallocatetoomanylicenses', 'block_iomad_company_admin');
                }
            } else {
                $errors['licenseid'] = get_string('invalidlicense', 'block_iomad_company_admin');
            }
        }
        return $errors;
    }

}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$companyid = optional_param('companyid', company_user::companyid(), PARAM_INTEGER);
$departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
$createdok = optional_param('createdok', 0, PARAM_INTEGER);
$createcourses = optional_param_array('currentcourses', null, PARAM_INT);
$licensecourses = optional_param_array('licensecourses', null, PARAM_INT);
$licenseid = optional_param('licenseid', 0, PARAM_INTEGER);

$context = context_system::instance();
require_login();
iomad::require_capability('block/iomad_company_admin:user_create', $context);

$PAGE->set_context($context);
$PAGE->requires->jquery();


$urlparams = array('companyid' => $companyid);
if ($returnurl) {
    $urlparams['returnurl'] = $returnurl;
}
$companylist = new moodle_url('/local/iomad_dashboard/index.php', $urlparams);

// Correct the navbar.
// Set the name for the page.
$linktext = get_string('createuser', 'block_iomad_company_admin');
// Set the url.
$linkurl = new moodle_url('/blocks/iomad_company_admin/company_user_create_form.php');
$dashboardurl = new moodle_url('/local/iomad_dashboard/index.php');
// Build the nav bar.
company_admin_fix_breadcrumb($PAGE, $linktext, $linkurl);

$blockpage = new blockpage($PAGE, $OUTPUT, 'iomad_company_admin', 'block', 'user_create_title');
$blockpage->setup();

// Set the companyid
$companyid = iomad::get_my_companyid($context);

$companyform = new company_select_form($PAGE->url, $companyid, 'createuserforcompany');
$mform = new user_edit_form($PAGE->url, $companyid, $departmentid, $licenseid);

if ($companyform->is_cancelled() || $mform->is_cancelled()) {
    if ($returnurl) {
        redirect($returnurl);
    } else {
        redirect($dashboardurl);
    }
} else if ($data = $mform->get_data()) {
    $data->userid = $USER->id;
    if ($companyid > 0) {
        $data->companyid = $companyid;
    }

    if (!$userid = company_user::create($data)) {
        $this->verbose("Error inserting a new user in the database!");
        if (!$this->get('ignore_errors')) {
            die();
        }
    }
    $user = new stdclass();
    $user->id = $userid;
    $systemcontext = context_system::instance();

    // Check if we are assigning a different role to the user.
    if (!empty($data->managertype)) {
        $companycourseeditorrole = $DB->get_record('role', array('shortname' => 'companycourseeditor'));
        $companycoursenoneditorrole = $DB->get_record('role', array('shortname' => 'companycoursenoneditor'));
        if ($data->managertype == 2) {
            // Assign the department manager role.
            $departmentmanagerrole = $DB->get_record('role', array('shortname' => 'companydepartmentmanager'));
            role_assign($departmentmanagerrole->id, $userid, $systemcontext->id);
            //  Assign appropriate roles to company courses.
            if ($companycourses = $DB->get_records('company_course', array('companyid' => $companyid))) {
            // Give them the manager role.
                foreach ($companycourses as $companycourse) {
                    if ($DB->record_exists('course', array('id' => $companycourse->courseid))) {
                        company_user::enrol($user,
                                            array($companycourse->courseid),
                                            $companycourse->companyid,
                                            $companycoursenoneditorrole->id);
                    }
                }
            }
        } else if ($data->managertype == 1) {
            // Assign the user as a company manager.
            $companymanagerrole = $DB->get_record('role', array('shortname' => 'companymanager'));
            // Give them the manager role.
            role_assign($companymanagerrole->id, $userid, $systemcontext->id);
            if ($companycourses = $DB->get_records('company_course', array('companyid' => $companyid))) {
                foreach ($companycourses as $companycourse) {
                    if ($DB->record_exists('course', array('id' => $companycourse->courseid))) {
                        // If its a company created course then assign the editor role to the user.
                        if ($DB->record_exists('company_created_courses', array ('companyid' => $companyid,
                                                                                 'courseid' => $companycourse->courseid))) {
                            company_user::enrol($user,
                                                 array($companycourse->courseid),
                                                 $companycourse->companyid,
                                                 $companycourseeditorrole->id);
                        } else {
                             company_user::enrol($user,
                                                 array($companycourse->courseid),
                                                 $companycourse->companyid,
                                                 $companycoursenoneditorrole->id);
                        }
                    }
                }
            }
        }
    }
    // Assign the user to the default company department.
    $parentnode = company::get_company_parentnode($companyid);
    if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $systemcontext)) {
        $userhierarchylevel = $parentnode->id;
    } else {
        $userlevel = company::get_userlevel($USER);
        $userhierarchylevel = $userlevel->id;
    }
    company::assign_user_to_department($data->userdepartment, $userid);

    // Enrol the user on the courses.
    if (!empty($createcourses)) {
        $userdata = $DB->get_record('user', array('id' => $userid));
        company_user::enrol($userdata, $createcourses, $companyid);
    }
    // Assign and licenses.
    if (!empty($licensecourses)) {
        $licenserecord = (array) $DB->get_record('companylicense', array('id' => $licenseid));
        $userdata = $DB->get_record('user', array('id' => $userid));
        $count = $licenserecord['used'];
        $numberoflicenses = $licenserecord['allocation'];
        foreach ($licensecourses as $licensecourse) {
            if ($count >= $numberoflicenses) {
                // Set the used amount.
                $licenserecord['used'] = $count;
                $DB->update_record('companylicense', $licenserecord);
                redirect(new moodle_url("/blocks/iomad_company_admin/company_license_users_form.php",
                                         array('licenseid' => $licenseid, 'error' => 1)));
            }
            $allow = true;

            if ($allow) {
                $count++;
                $DB->insert_record('companylicense_users',
                                    array('userid' => $userdata->id, 'licenseid' => $licenseid,
                                          'licensecourseid' => $licensecourse));
            }
            // Create an email event.
            $license = new stdclass();
            $license->length = $licenserecord['validlength'];
            $license->valid = date('d M Y', $licenserecord['expirydate']);
            EmailTemplate::send('license_allocated', array('course' => $licensecourse,
                                                           'user' => $userdata,
                                                           'license' => $license));
        }

        // Set the used amount for the license.
        $licenserecord['used'] = $DB->count_records('companylicense_users', array('licenseid' => $licenseid));
        $DB->update_record('companylicense', $licenserecord);
    }

    if (isset($data->submitandback)) {
        redirect($dashboardurl);
    } else {
        redirect($linkurl."?createdok=1");
    }
}
$blockpage->display_header();
?>
<script type="text/javascript">
Y.on('change', submit_form, '#licenseidselector');
 function submit_form() {
     var nValue = Y.one('#licenseidselector').get('value');
    $.ajax({
        type: "GET",
        url: "<?php echo $CFG->wwwroot; ?>/blocks/iomad_company_admin/js/company_user_create_form.ajax.php?licenseid="+nValue,
        datatype: "HTML",
        success: function(response){
            $("#licensecourseselector").html(response);
        }
    });
    $.ajax({
        type: "GET",
        url: "<?php echo $CFG->wwwroot; ?>/blocks/iomad_company_admin/js/company_user_create_form-license.ajax.php?licenseid="+nValue,
        datatype: "HTML",
        success: function(response){
            $("#licensedetails").html(response);
        }
    });
 }
</script>
<?php

// Display a message if user is created..
if ($createdok) {
    echo '<h2>'.get_string('usercreated', 'block_iomad_company_admin').'</h2>';
}
// Display the form.
$mform->display();

echo $OUTPUT->footer();

