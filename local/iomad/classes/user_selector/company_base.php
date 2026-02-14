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
 * Local IOMAD company base user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad\user_selector;

use html_writer;
use local_iomad\company;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../../user/selector/lib.php');

/**
 * Local IOMAD company base user selector class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class company_base extends \user_selector_base {

    /** @var bool is this all user? */
    protected $allusers = false;

    /** @var object company */
    protected $company;

    /** @var int company id */
    protected $companyid;

    /** @var int course id */
    protected $courseid;

    /** @var array list of courses  */
    protected $courses;

    /** @var int department id */
    protected $departmentid;

    /** @var object license */
    protected $license;

    /** @var int license id */
    protected $licenseid;

    /** @var object parent department */
    protected $parentdepartment;

    /** @var int profile field id */
    protected $profilefieldid = 0;

    /** @var object program */
    protected $program;

    /** @var array selected courses */
    protected $selectedcourses;

    /** @var array sub departments */
    protected $subdepartments;

    /** @var array JavaScript YUI3 Module definition */
    protected static $jsmodule = [
                'name' => 'user_selector',
                'fullpath' => '/local/iomad/classes/user_selector/module.js',
                'requires'  => ['node', 'event-custom', 'datasource', 'json', 'moodle-core-notification'],
                'strings' => [
                    ['previouslyselectedusers', 'moodle', '%%SEARCHTERM%%'],
                    ['nomatchingusers', 'moodle', '%%SEARCHTERM%%'],
                    ['none', 'moodle'],
                ]];

    /**
     * Constructor function
     *
     * @param string $name
     * @param array $options
     */
    public function __construct($name, $options) {

        $this->allusers = !empty($options['allusers']) ? $options['allusers'] : false;
        $this->companyid  = $options['companyid'];
        $this->courses = !empty($options['courses']) ? $options['courses'] : [];
        $this->courseid = !empty($options['courseid']) ? $options['courseid'] : 0;
        $this->parentdepartment = !empty($options['parentdepartment']) ? $options['parentdepartment'] : 0;
        $this->licenseid = !empty($options['licenseid']) ? $options['licenseid'] : 0;
        $this->program = !empty($options['program']) ? $options['program'] : 0;
        $this->selectedcourses = !empty($options['selectedcourses']) ? $options['selectedcourses'] : [];
        if (empty($options['departmentid'])) {
            $parentdepartment = company::get_company_parentnode($this->companyid);
            $this->departmentid = $parentdepartment->id;
        } else {
            $this->departmentid = $options['departmentid'];
        }
        if (!empty($options['profilefieldid'])) {
            $profileid = $options['profilefieldid'];
        } else {
            $profileid = optional_param($name . '_profilefieldid', 0, PARAM_INT);
        }
        $this->profilefieldid = $profileid;
        $this->company = new company($this->companyid);
        $this->subdepartments = company::get_all_subdepartments($this->departmentid);

        parent::__construct($name, $options);
    }

    /**
     * Get selector options
     *
     * @return array
     */
    protected function get_options() {
        $options = parent::get_options();
        $options['file']    = 'local/iomad/classes/user_selector/company_base.php';
        $options['allusers'] = $this->allusers;
        $options['company'] = $this->company;
        $options['companyid'] = $this->companyid;
        $options['courseid'] = $this->courseid;
        $options['courses'] = $this->courses;
        $options['departmentid'] = $this->departmentid;
        $options['licenseid'] = $this->licenseid;
        $options['parentdepartment'] = $this->parentdepartment;
        $options['profilefieldid'] = $this->profilefieldid;
        $options['program'] = $this->program;
        $options['selectedcourses'] = $this->selectedcourses;
        $options['subdepartments'] = $this->subdepartments;

        return $options;
    }

    /**
     * Get the list of users in a course
     *
     * @return array
     */
    protected function get_course_user_ids() {
        global $DB, $PAGE;
        if (!isset( $this->courseid) ) {
            return [];
        } else {
            $course = $DB->get_record('course', ['id' => $this->courseid]);
            $courseenrolmentmanager = new courseenrolmentmanager($PAGE, $course);

            $users = $courseenrolmentmanager->get_users('lastname');

            // Only return the keys (user ids).
            return array_keys($users);
        }
    }

    /**
     * Returns an array with SQL to perform a search and the params that go into it.
     *
     * @param string $search the text to search for.
     * @param string $u the table alias for the user table in the query being
     *      built. May be ''.
     * @return array an array with two elements, a fragment of SQL to go in the
     *      where clause the query, and an array containing any required parameters.
     *      this uses ? style placeholders.
     */
    protected function search_sql(string $search, string $u): array {
        global $DB;

        if (empty($this->profilefieldid)) {
            return users_search_sql($search, $u, $this->searchtype, $this->extrafields,
                    $this->exclude, $this->validatinguserids);
        } else {
            $wheresqsl = "ui.fieldid = :profilefieldid
                          AND " . $DB->sql_like('ui.data', ":profilesearch", false, false) .
                         " AND ui.data <> ''";
            $params = ['profilefieldid' => $this->profilefieldid,
                       'profilesearch' => "%".$search."%"];
            return [$wheresqsl, $params];
        }
    }

    /**
     * Initialises JS for this control.
     *
     * @param string $search
     * @return string any HTML needed here.
     */
    protected function initialise_javascript($search) {
        global $USER, $PAGE;
        $output = '';

        // Put the options into the session, to allow search.php to respond to the ajax requests.
        $options = $this->get_options();
        $hash = md5(serialize($options));
        $USER->userselectors[$hash] = $options;

        // Initialise the selector.
        $PAGE->requires->js_init_call(
            'M.core_user.init_user_selector',
            [$this->name, $hash, $this->extrafields, $search],
            false,
            self::$jsmodule
        );
        return $output;
    }

    /**
     * Output this user_selector as HTML.
     *
     * @param boolean $return if true, return the HTML as a string instead of outputting it.
     * @return mixed if $return is true, returns the HTML as a string, otherwise returns nothing.
     */
    public function display($return = false) {
        global $DB;

        // Get the list of requested users.
        $search = optional_param($this->name . '_searchtext', '', PARAM_RAW);
        if (optional_param($this->name . '_clearbutton', false, PARAM_BOOL)) {
            $search = '';
        }
        $groupedusers = $this->find_users($search);

        // Get the company profile fields.
        $companyprofilecategories = $DB->get_records_sql("SELECT uif.id,uif.name FROM {user_info_category} uic
                                                          JOIN {user_info_field} uif ON (uic.id = uif.categoryid)
                                                          WHERE uic.id NOT IN (
                                                              SELECT profileid FROM {company}
                                                              WHERE id <> :companyid
                                                          )
                                                          ORDER BY uif.name DESC",
                                                          ['companyid' => $this->companyid]);

        // Output the select.
        $name = $this->name;
        $multiselect = '';
        if ($this->multiselect) {
            $name .= '[]';
            $multiselect = "multiple";
        }

        // Create the profile field selectors.
        $profilesearch = html_writer::empty_tag(
            'select',
            [
                'name' => $this->name . '_profilefieldid',
                'class' => 'form-control custom_srch d-block col-12 my-2',
                'id' => $this->name . '_custom_srch',

            ]) .
            html_writer::tag('option', get_string('user'), ['value' => 0]);
        foreach ($companyprofilecategories as $companyprofilecategory) {
            if (!empty($profileid) &&
                $profileid == $companyprofilecategory->id) {
                $profilesearch .= html_writer::tag(
                    'option',
                    format_string($companyprofilecategory->name),
                    [
                        'value' => $companyprofilecategory->id,
                        'selected' => true,
                    ]);
            } else {
                $profilesearch .= html_writer::tag(
                    'option',
                    format_string($companyprofilecategory->name),
                    [
                        'value' => $companyprofilecategory->id,
                    ]);
            }
        }
        $profilesearch .= html_writer::end_tag('select');

        $output = html_writer::start_tag(
            'div',
            [
                'class' => 'userselector',
                'id' => $this->name . '_wrapper',
            ]
        ) .
            html_writer::empty_tag(
                'select',
                [
                    'name' => $name,
                    'id' => $this->name,
                    'multiple' => $multiselect,
                    'size' => $this->rows,
                    'class' => "form-control no-overflow",
                ]
            );

        // Populate the select.
        $output .= $this->output_options($groupedusers, $search);

        // Output the search controls.
        $output .= html_writer::end_tag('select') .
                   html_writer::start_tag('div', ['class' => "form-inline"]);
        $output .= $profilesearch;
        $output .= html_writer::empty_tag(
            'input',
            [
                'type' => "text",
                'name' => $this->name . '_searchtext',
                'id' => $this->name . '_searchtext',
                'size' => "15",
                'value' => $search,
                'class' => "form-control",
            ]);
        $output .= html_writer::empty_tag(
            'input',
            [
                'type' => "submit",
                'name' => $this->name . '_searchbutton',
                'id' => $this->name . '_searchbutton',
                'value' => $this->search_button_caption(),
                'class' => "btn btn-secondary",
            ]);
        $output .= html_writer::empty_tag(
            'input',
            [
                'type' => "submit",
                'name' => $this->name . '_clearbutton',
                'id' => $this->name . '_clearbutton',
                'value' => get_string('clear'),
                'class' => "btn btn-secondary",
            ]);
        $output .= html_writer::end_tag('div') .
                   html_writer::end_tag('div');

        // Initialise the ajax functionality.
        $output .= $this->initialise_javascript($search);

        // Return or output it.
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }
}
