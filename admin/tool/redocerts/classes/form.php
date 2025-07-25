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
 * @package   tool_redocerts
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Site wide search-redocerts form.
 */
class tool_redocerts_form extends moodleform {

    function definition() {
        global $CFG, $DB;
        $systemcontext = context_system::instance();
        $companyid = iomad::get_my_companyid($systemcontext);
        $hidecompanyid = false;

        // Gathering Companies, Courses, and Users for lists
        if (iomad::has_capability('block/iomad_company_admin:company_view_all', $systemcontext)) {
            // Array of all User names identified by User ID
            $users = $DB->get_records_sql_menu(
                                                "SELECT
                                                    id,
                                                    concat(firstname, ' ', lastname) AS fullname
                                                FROM {user}
                                                WHERE deleted = 0
                                                ORDER BY fullname",
                                                array());

            // Array of all Course name identified by ID
            $courses = $DB->get_records_menu('course', array(), 'fullname', 'id,fullname');

            // Array of all Company names identified by ID
            $companies = $DB->get_records_menu('company', array(), 'name', 'id,name');

            // All companies, courses, and users
            $allusers = array(0 => get_string('all')) + $users;
            $allcourses = array(0 => get_string('all')) + $courses;
            $allcompanies = array(0 => get_string('all')) + $companies;
        } else {
            // Array of this company's name identified by Company ID
            $mycompanies = $DB->get_records_sql_menu(
                                                "SELECT
                                                    id,
                                                    name
                                                FROM mdl_company
                                                WHERE id = $companyid
                                                ORDER BY name;",
                                                array());

            // Get all child companies and grandchildren and greatgrandchildren and so on...
            $iterator = new ArrayIterator($mycompanies);
            foreach ($iterator as $thiscompanyid => $thiscompanyname) {
                $mychildren = $DB->get_records_sql_menu(
                                                "SELECT
                                                    id,
                                                    name
                                                FROM mdl_company
                                                WHERE parentid = $thiscompanyid;",
                                                array());

                foreach ($mychildren as $child) {
                    $iterator->append($child);
                }
            }

            $myusers = array();
            $mycourses = array();
            foreach ($iterator->getArrayCopy() as $mycompanyid => $mycompanyname) {
                // Array of this company's (and company's children's) User names identified by User ID
                $myusers = array_merge($myusers, $DB->get_records_sql_menu(
                                                "SELECT
                                                    mdl_user.id,
                                                    concat(firstname, ' ', lastname) AS fullname
                                                FROM mdl_user
                                                INNER JOIN mdl_company
                                                    ON mdl_user.institution = mdl_company.shortname
                                                WHERE mdl_user.deleted = 0 AND mdl_company.id = $mycompanyid
                                                ORDER BY fullname;",
                                                array()));

                // Array of this company's (and company's children's) Course names identified by Course ID
                $mycourses = array_merge($mycourses, $DB->get_records_sql_menu(
                                                "SELECT
                                                    mdl_course.id,
                                                    mdl_course.fullname
                                                FROM mdl_course
                                                INNER JOIN mdl_company_course
                                                    ON mdl_course.id=mdl_company_course.courseid
                                                INNER JOIN mdl_company
                                                    ON mdl_company_course.companyid=mdl_company.id
                                                WHERE mdl_company.id = $mycompanyid
                                                ORDER BY mdl_course.fullname;",
                                                array()));
            }

            // All companies, courses, and users that this user is allowed to select
            $allusers = array(0 => get_string('all')) + $myusers;
            $allcourses = array(0 => get_string('all')) + $mycourses;
            $allcompanies = $iterator->getArrayCopy();
            $hidecompanyid = true;
        }

        $mform = $this->_form;

        $mform->addElement('autocomplete', 'user', get_string('searchusers', 'tool_redocerts'), $allusers);
        $mform->addElement('text', 'userid', get_string('userid', 'tool_redocerts'));
        $mform->addElement('autocomplete', 'course', get_string('searchcourses', 'tool_redocerts'), $allcourses);
        $mform->addElement('text', 'courseid', get_string('courseid', 'tool_redocerts'));
        $mform->addElement('autocomplete', 'company', get_string('searchcompanies', 'tool_redocerts'), $allcompanies);
        if ($hidecompanyid) {
            $mform->addElement('hidden', 'companyid');
        } else {
            $mform->addElement('text', 'companyid', get_string('companyid', 'tool_redocerts'));
        }
        $mform->addElement('date_time_selector', 'fromdate', get_string('fromdate', 'tool_redocerts'), array('optional' => true));
        $mform->addElement('date_time_selector', 'todate', get_string('todate', 'tool_redocerts'), array('optional' => true));
        $mform->setType('idnumber', PARAM_INT);
        $mform->setType('userid', PARAM_INT);
        $mform->setType('courseid', PARAM_INT);
        $mform->setType('companyid', PARAM_INT);

        $this->add_action_buttons(false, get_string('doit', 'tool_redocerts'));
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
