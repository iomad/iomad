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
 * Form class for redocerts tool
 *
 * @package   tool_redocerts
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_redocerts\form;

use context_system;
use local_iomad\custom_context\context_company;
use local_iomad\iomad;
use moodleform;

/**
 * Site wide search-redocerts form.
 */
class redocerts_form extends moodleform {

    /**
     * Class definition function.
     *
     * @return void
     */
    public function definition() {
        $systemcontext = context_system::instance();
        $companyid = iomad::get_my_companyid($systemcontext);
        $hidecompanyid = true;

        // All companies, courses, and users that this user is allowed to select.
        $allcourses = [-1 => get_string('all'), 0 => get_string('none')];
        $allcompanies = [-1 => get_string('all'), 0 => get_string('none')];
        $allusers = [-1 => get_string('all'), 0 => get_string('none')];

        // Gathering Companies, Courses, and Users for lists.
        if (iomad::has_capability('block/iomad_company_admin:company_view_all', $systemcontext)) {
            $hidecompanyid = false;
        }

        // Set up the form variable.
        $mform = $this->_form;

        // Add the user selector.
        $userselect = $mform->addElement(
            'autocomplete',
            'user',
            get_string('searchusers', 'tool_redocerts'),
            $allusers,
            [
                'multiple' => false,
                'noselectionstring' => get_string('searchusers'),
                'data-selector' => 'userselect',
                'data-companyid' => $companyid,
            ]
        );
        $userselect->setSelected('0');

        // Add the user ID text field.
        $mform->addElement('text', 'userid', get_string('userid', 'tool_redocerts'));

        // Add the Course selector.
        $courseselect = $mform->addElement(
            'autocomplete',
            'course',
            get_string('searchcourses', 'tool_redocerts'),
            $allcourses,
            [
                'multiple' => false,
                'noselectionstring' => get_string('searchcourses'),
                'data-selector' => 'courseselect',
            ]
        );
        $courseselect->setSelected('0');

        // Add the user ID text field.
        $mform->addElement('text', 'courseid', get_string('courseid', 'tool_redocerts'));

        // Add the company selector.
        $companyselect = $mform->addElement(
            'autocomplete',
            'company',
            get_string('searchcompanies', 'tool_redocerts'),
            $allcompanies,
            [
                'multiple' => false,
                'noselectionstring' => get_string('selectacompany', 'block_iomad_company_admin'),
                'data-selector' => 'companyselect',
            ]
        );
        $companyselect-> setSelected('0');

        // Add the company ID text field.
        if ($hidecompanyid) {
            $mform->addElement('hidden', 'companyid');
        } else {
            $mform->addElement('text', 'companyid', get_string('companyid', 'tool_redocerts'));
        }

        // Add the to and from data selectors.
        $mform->addElement('date_time_selector', 'fromdate', get_string('fromdate', 'tool_redocerts'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'todate', get_string('todate', 'tool_redocerts'), ['optional' => true]);

        // Add the default types.
        $mform->setType('idnumber', PARAM_INT);
        $mform->setType('userid', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('companyid', PARAM_INT);

        // Add the buttons.
        $this->add_action_buttons(false, get_string('doit', 'tool_redocerts'));
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

        // Set some defaults.
        $systemcontext = context_system::instance();
        $companyid = iomad::get_my_companyid($systemcontext);
        $companycontext = context_company::instance($companyid);

        // Get any parent form class errors.
        $errors = parent::validation($data, $files);

        // Check the passed form field types.
        if (empty($data['user']) && empty($data['userid'])) {
            $errors['user'] = get_string('required');
        }
        if (empty($data['course']) && empty($data['courseid'])) {
            $errors['course'] = get_string('required');
        }
        if (empty($data['company']) && empty($data['companyid'])) {
            $errors['company'] = get_string('required');
        }
        if (!empty($data['userid'])) {
            if (!is_int($data['userid'])) {
                $errors['userid'] = get_string('invalidnum', 'error');
            } else if (!DB->record_exists('user', ['id' => $data['userid']])) {
                $errors['userid'] = get_string('invaliduserid', 'error');
            }
        }
        if (!empty($data['companyid'])) {
            if (!is_int($data['companyid'])) {
                $errors['companyid'] = get_string('invalidnum', 'error');
            } else if (!$companyrec = $DB->get_record('local_iomad_companies', ['id' => $data['companyid']])) {
                $errors['companyid'] = get_string('statusunknown', 'moodle');
            } else {
                $companyid = $companyrec->id;
                $companycontext = context_company::instance($companyid);
                if (!iomad::has_capability('tool/redocerts:redocertificates', $companycontext)) {
                    $errors['companyid'] = get_string('statusunknown', 'moodle');
                }
            }
        }
        if (!empty($data['courseid'])) {
            if (!is_int($data['courseid'])) {
                $errors['courseid'] = get_string('invalidnum', 'error');
            } else if (!$courserec = $DB->get_record('course', ['id' => $data['courseid']])) {
                $errors['courseid'] = get_string('invalidcourseid', 'error');
            } else {
                $courses = [$courserec->id => $courserec];
                $courses = iomad::iomad_filter_courses($courses);
                if (empty($courses)) {
                    $errors['courseid'] = get_string('invalidcourseid', 'error');
                }
            }
        }

        return $errors;
    }
}
