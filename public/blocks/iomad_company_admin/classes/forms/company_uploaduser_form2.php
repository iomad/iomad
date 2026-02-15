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
 * IOMAD Dashboard upload user form classes
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_iomad_company_admin\forms;

use local_iomad\{company, company_user, iomad};
use html_writer;


/**
 * IOMAD Dashboard company upload user form2 class
 *
 * @package   block_iomad_company_admin
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company_uploaduser_form2 extends company_moodleform {

    /** @var object course selector */
    protected $courseselector = null;

    /** @var int license ID */
    protected $licenseid;

    /**
     * Form definition
     *
     * @return void
     */
    public function definition() {
        global $companycontext, $USER, $SESSION;

        // Set up the form.
        $mform =& $this->_form;
        $columns =& $this->_customdata;

        // I am the template user, why should it be the administrator? we have roles now, other ppl may use this script ;-).
        $templateuser = $USER;

        // Upload settings and file.
        $mform->addElement('header', 'settingsheader', get_string('settings'));

        $mform->addElement('static', 'uutypelabel', get_string('uuoptype', 'tool_uploaduser'));

        $choices = [0 => get_string('infilefield', 'auth'), 1 => get_string('createpasswordifneeded', 'auth')];
        $mform->addElement('select', 'uupasswordnew', get_string('uupasswordnew', 'tool_uploaduser'), $choices);
        $mform->setDefault('uupasswordnew', 1);
        $mform->disabledIf('uupasswordnew', 'uutype', 'eq', UU_UPDATE);

        $mform->addElement('selectyesno', 'sendnewpasswordemails',
                            get_string('sendnewpasswordemails', 'block_iomad_company_admin'));
        $mform->setDefault('sendnewpasswordemails', 1);

        $choices = [0 => get_string('nochanges', 'tool_uploaduser'),
                         1 => get_string('uuupdatefromfile', 'tool_uploaduser'),
                         2 => get_string('uuupdateall', 'tool_uploaduser'),
                         3 => get_string('uuupdatemissing', 'tool_uploaduser')];
        $mform->addElement('select', 'uuupdatetype', get_string('uuupdatetype', 'tool_uploaduser'), $choices);
        $mform->setDefault('uuupdatetype', 0);
        $mform->disabledIf('uuupdatetype', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuupdatetype', 'uutype', 'eq', UU_ADDINC);

        $choices = [0 => get_string('nochanges', 'tool_uploaduser'), 1 => get_string('update')];
        $mform->addElement('select', 'uupasswordold', get_string('uupasswordold', 'tool_uploaduser'), $choices);
        $mform->setDefault('uupasswordold', 0);
        $mform->disabledIf('uupasswordold', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uupasswordold', 'uutype', 'eq', UU_ADDINC);
        $mform->disabledIf('uupasswordold', 'uuupdatetype', 'eq', 0);
        $mform->disabledIf('uupasswordold', 'uuupdatetype', 'eq', 3);

        $mform->addElement('selectyesno', 'uuallowrenames', get_string('allowrenames', 'tool_uploaduser'));
        $mform->setDefault('uuallowrenames', 0);
        $mform->disabledIf('uuallowrenames', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuallowrenames', 'uutype', 'eq', UU_ADDINC);

        $mform->addElement('selectyesno', 'uuallowdeletes', get_string('allowdeletes', 'tool_uploaduser'));
        $mform->setDefault('uuallowdeletes', 0);
        $mform->disabledIf('uuallowdeletes', 'uutype', 'eq', UU_ADDNEW);
        $mform->disabledIf('uuallowdeletes', 'uutype', 'eq', UU_ADDINC);

        $mform->addElement('selectyesno', 'uunoemailduplicates', get_string('uunoemailduplicates', 'tool_uploaduser'));
        $mform->setDefault('uunoemailduplicates', 1);

        $choices = [0 => get_string('no'),
                         1 => get_string('uubulknew', 'tool_uploaduser'),
                         2 => get_string('uubulkupdated', 'tool_uploaduser'),
                         3 => get_string('uubulkall', 'tool_uploaduser')];
        $mform->addElement('select', 'uubulk', get_string('uubulk', 'tool_uploaduser'), $choices);
        $mform->setDefault('uubulk', 0);

        // Roles selection.
        $showroles = false;
        foreach ($columns as $column) {
            if (preg_match('/^type\d+$/', $column)) {
                $showroles = true;
                break;
            }
        }
        if ($showroles) {
            $mform->addElement('header', 'rolesheader', get_string('roles'));

            $choices = uu_allowed_roles(true);

            $mform->addElement('select', 'uulegacy1', get_string('uulegacy1role', 'tool_uploaduser'), $choices);
            if ($studentroles = get_archetype_roles('student')) {
                foreach ($studentroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy1', $role->id);
                        break;
                    }
                }
                unset($studentroles);
            }

            $mform->addElement('select', 'uulegacy2', get_string('uulegacy2role', 'tool_uploaduser'), $choices);
            if ($editteacherroles = get_archetype_roles('editingteacher')) {
                foreach ($editteacherroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy2', $role->id);
                        break;
                    }
                }
                unset($editteacherroles);
            }

            $mform->addElement('select', 'uulegacy3', get_string('uulegacy3role', 'tool_uploaduser'), $choices);
            if ($teacherroles = get_archetype_roles('teacher')) {
                foreach ($teacherroles as $role) {
                    if (isset($choices[$role->id])) {
                        $mform->setDefault('uulegacy3', $role->id);
                        break;
                    }
                }
                unset($teacherroles);
            }
        }

        // Hidden fields.
        $mform->addElement('hidden', 'iid');
        $mform->setType('iid', PARAM_INT);

        $mform->addElement('hidden', 'auth');
        $mform->setDefault('auth', '');
        $mform->setType('auth', PARAM_TEXT);

        $mform->addElement('hidden', 'previewrows');
        $mform->setType('previewrows', PARAM_INT);

        $mform->addElement('hidden', 'readcount');
        $mform->setType('readcount', PARAM_INT);

        $mform->addElement('hidden', 'uutype');
        $mform->setType('uutype', PARAM_INT);

        $mform->addElement('hidden', 'companyid', $this->selectedcompany);
        $mform->setType('companyid', PARAM_INT);
    }

    /**
     * Form tweaks that depend on current data.
     */
    public function definition_after_data() {
        global $SESSION, $CFG, $DB, $output;

        // Set up the form.
        $mform =& $this->_form;
        $columns =& $this->_customdata;

        foreach ($columns as $column) {
            if ($mform->elementExists($column)) {
                $mform->removeElement($column);
            }
        }

        // Set the companyid to bypass the company select form if possible.
        if (!empty($SESSION->currenteditingcompany)) {
            $companyid = $SESSION->currenteditingcompany;
        } else {
            $companyid = company_user::companyid();
        }

        // Get the department list.
        $company = new company($companyid);
        $companycontext = $company->context;
        $companymanualcourses = $company->get_menu_courses(true, true);;
        $parentlevel = company::get_company_parentnode($companyid);
        $output->display_tree_selector_form($company, $mform, 0, '', false, false);

        // When are we sending emails?
        $mform->addElement('date_time_selector', 'due', get_string('senddate', 'block_iomad_company_admin'));
        $mform->addHelpButton('due', 'senddate', 'block_iomad_company_admin');

        // Optional profile fields.
        $mform->addElement('header', 'profile_id', get_string('profilefields', 'mnet'));

        // Get global fields.
        if ($fields = $DB->get_records_sql("SELECT * FROM {user_info_field}
                                            WHERE categoryid NOT IN (
                                                SELECT profileid FROM {company}
                                            )")) {
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
        if ($companyinfo = $DB->get_record('company', ['id' => $this->selectedcompany])) {

            // Get fields from company category.
            if ($fields = $DB->get_records('user_info_field', ['categoryid' => $companyinfo->profileid])) {
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
        if (iomad::has_capability('block/iomad_company_admin:allocate_licenses', $companycontext)) {
            $mform->addElement('header', 'licenses', get_string('assignlicenses', 'block_iomad_company_admin'));
            $foundlicenses = $DB->get_records_sql_menu(
                "SELECT id, name
                 FROM {companylicense}
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
                    $mylicensedetails = $DB->get_record('companylicense', ['id' => $this->licenseid]);
                    $usedcount = $mylicensedetails->used;
                    // Is this a program license?
                    if (!empty($mylicense->program) && !empty($usedcount)) {
                        $licensecourses = $DB->count_records('companylicense_courses', ['licenseid' => $this->licenseid]);
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
                     FROM {companylicense_courses} clc
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
        if (iomad::has_capability('block/iomad_company_admin:company_course_users', $companycontext)) {
            $mform->addElement('header', 'courses', get_string('assigncourses', 'block_iomad_company_admin'));
            $autooptions = ['multiple' => true,
                            'noselectionstring' => get_string('none')];
            $mform->addElement('autocomplete',
                               'currentcourses',
                               get_string('selectenrolmentcourse', 'block_iomad_company_admin'),
                               $companymanualcourses,
                               $autooptions);
        }

        $this->add_action_buttons(true, get_string('uploadusers', 'tool_uploaduser'));
    }

    /**
     * Server side validation.
     */
    public function validation($data, $files) {
        global $DB, $SESSION;
        if (!empty($data['cancel'])) {
            return true;
        }
        $errors = parent::validation($data, $files);
        $columns =& $this->_customdata;
        $optype = $data['uutype'];

        // Detect if password column needed in file.
        if (!in_array('password', $columns)) {
            switch ($optype) {
                case UU_UPDATE:
                    if (!empty($data['uupasswordold'])) {
                        $errors['uupasswordold'] = get_string('missingfield', 'error', 'password');
                    }
                break;

                case UU_ADD_UPDATE:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
                    if (!empty($data['uupasswordold'])) {
                        $errors['uupasswordold'] = get_string('missingfield', 'error', 'password');
                    }
                break;

                case UU_ADDNEW:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
                break;
                case UU_ADDINC:
                    if (empty($data['uupasswordnew'])) {
                        $errors['uupasswordnew'] = get_string('missingfield', 'error', 'password');
                    }
               break;
            }
        }

        // Look for other required data.
        if ($optype != UU_UPDATE) {
            if (!in_array('firstname', $columns)) {
                $errors['uutype'] = get_string('missingfield', 'error', 'firstname');
            }

            if (!in_array('lastname', $columns)) {
                if (isset($errors['uutype'])) {
                    $errors['uutype'] = '';
                } else {
                    $errors['uutype'] = ' ';
                }
                $errors['uutype'] .= get_string('missingfield', 'error', 'lastname');
            }

            if (!in_array('email', $columns) && empty($data['email'])) {
                $errors['email'] = get_string('requiredtemplate', 'tool_uploaduser');
            }
        }

        if (!empty($data['licenseid'])) {
            $license = $DB->get_record('companylicense', ['id' => $data['licenseid']]);

            // Are we dealing with a program license?
            if (!empty($license->program)) {
                // If so the courses are not passed automatically.
                $data['licensecourses'] = $DB->get_records_sql_menu(
                    "SELECT c.id, c.fullname
                     FROM {companylicense_courses} clc
                     JOIN {course} c ON (clc.courseid = c.id
                     AND clc.licenseid = :licenseid)",
                    ['licenseid' => $license->id]);
            }
            if (!empty($data['licensecourses'])) {
                if (empty($license->program)) {
                    $requiredcount = count($data['licensecourses']) * ($data['readcount'] - 1);
                } else {
                    $requiredcount = $data['readcount'] - 1;
                }
            } else {
                $requiredcount = 0;
            }
            if (empty($license->program)) {
                $free = ($license->allocation - $license->used);
            } else {
                $free = ($license->allocation - $license->used) / count($data['licensecourses']);
            }
            if ( $requiredcount > $free) {
                // Check how many free spaces and
                // compare it to numbers of users.
                $errors['licenseid'] = 'We need ' . $requiredcount . ' license slots and have ' . $free;
            }
        }

        return $errors;
    }

    /**
     * Used to reformat the data from the editor component
     *
     * @return stdClass
     */
    public function get_data() {
        $data = parent::get_data();

        if ($data !== null && $this->courseselector) {
            $data->selectedcourses = $this->courseselector->get_selected_courses();
        }

        return $data;
    }

    /**
     * Set the form data
     *
     * @param [type] $data
     * @return void
     */
    public function set_data($data) {
        parent::set_data($data);

        if ($data['companyid'] > 0) {
            $this->selectedcompany = $data['companyid'];
        }
    }
}
