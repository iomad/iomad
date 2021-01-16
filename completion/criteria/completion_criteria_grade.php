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
 * Course completion critieria - completion on achieving course grade
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/querylib.php';

/**
 * Course completion critieria - completion on achieving course grade
 *
 * @package core_completion
 * @category completion
 * @copyright 2009 Catalyst IT Ltd
 * @author Aaron Barnes <aaronb@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_criteria_grade extends completion_criteria {

    /* @var int Criteria type constant [COMPLETION_CRITERIA_TYPE_GRADE] */
    public $criteriatype = COMPLETION_CRITERIA_TYPE_GRADE;

    /**
     * Finds and returns a data_object instance based on params.
     *
     * @param array $params associative array varname => value of various
     * parameters used to fetch data_object
     * @return data_object data_object instance or false if none found.
     */
    public static function fetch($params) {
        $params['criteriatype'] = COMPLETION_CRITERIA_TYPE_GRADE;
        return self::fetch_helper('course_completion_criteria', __CLASS__, $params);
    }

    /**
     * Add appropriate form elements to the critieria form
     *
     * @param moodle_form $mform Moodle forms object
     * @param stdClass $data containing default values to be set in the form
     */
    public function config_form_display(&$mform, $data = null) {
        $mform->addElement('checkbox', 'criteria_grade', get_string('enable'));
        $mform->addElement('text', 'criteria_grade_value', get_string('graderequired', 'completion'));
        $mform->disabledIf('criteria_grade_value', 'criteria_grade');
        $mform->setType('criteria_grade_value', PARAM_RAW); // Uses unformat_float.
        // Grades are stored in Moodle with 5 decimal points, make sure we do not accidentally round them
        // when setting the form value.
        $mform->setDefault('criteria_grade_value', format_float($data, 5));

        if ($this->id) {
            $mform->setDefault('criteria_grade', 1);
            $mform->setDefault('criteria_grade_value', format_float($this->gradepass, 5));
        }
    }

    /**
     * Update the criteria information stored in the database
     *
     * @param stdClass $data Form data
     */
    public function update_config(&$data) {

        if (!empty($data->criteria_grade)) {
            $formatedgrade = unformat_float($data->criteria_grade_value);
            // TODO validation
            if (!empty($formatedgrade) && is_numeric($formatedgrade)) {
                $this->course = $data->id;
                $this->gradepass = $formatedgrade;
                $this->insert();
            }
        }
    }

    /**
     * Get user's course grade in this course
     *
     * @param completion_completion $completion an instance of completion_completion class
     * @return float
     */
    private function get_grade($completion) {
        $grade = grade_get_course_grade($completion->userid, $this->course);
        return $grade->grade;
    }

    /**
     * Review this criteria and decide if the user has completed
     *
     * @param completion_completion $completion The user's completion record
     * @param bool $mark Optionally set false to not save changes to database
     * @return bool
     */
    public function review($completion, $mark = true) {
        // Get user's course grade
        $grade = $this->get_grade($completion);

        // If user's current course grade is higher than the required pass grade
        if ($this->gradepass && $this->gradepass <= $grade) {
            if ($mark) {
                $completion->gradefinal = $grade;
                $completion->mark_complete();
            }

            return true;
        }

        return false;
    }

    /**
     * Return criteria title for display in reports
     *
     * @return  string
     */
    public function get_title() {
        return get_string('coursegrade', 'completion');
    }

    /**
     * Return a more detailed criteria title for display in reports
     *
     * @return string
     */
    public function get_title_detailed() {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $decimalpoints = grade_get_setting($this->course, 'decimalpoints', $CFG->grade_decimalpoints);
        $graderequired = format_float($this->gradepass, $decimalpoints);
        return get_string('gradexrequired', 'completion', $graderequired);
    }

    /**
     * Return criteria type title for display in reports
     *
     * @return string
     */
    public function get_type_title() {
        return get_string('grade');
    }

    /**
     * Return criteria status text for display in reports
     *
     * @param completion_completion $completion The user's completion record
     * @return string
     */
    public function get_status($completion) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $decimalpoints = grade_get_setting($this->course, 'decimalpoints', $CFG->grade_decimalpoints);

        $grade = $this->get_grade($completion);
        $graderequired = $this->get_title_detailed();

        if ($grade) {
            $grade = format_float($grade, $decimalpoints);
        } else {
            $grade = get_string('nograde');
        }

        return $grade.' ('.$graderequired.')';
    }

    /**
     * Find user's who have completed this criteria
     */
    public function cron() {
        global $DB;

        // Get all users who meet this criteria
        $sql = '
            SELECT DISTINCT
                c.id AS course,
                cr.id AS criteriaid,
                ra.userid AS userid,
                gg.finalgrade AS gradefinal,
                gg.timemodified AS timecompleted
            FROM
                {course_completion_criteria} cr
            INNER JOIN
                {course} c
             ON cr.course = c.id
            INNER JOIN
                {context} con
             ON con.instanceid = c.id
            INNER JOIN
                {role_assignments} ra
              ON ra.contextid = con.id
            INNER JOIN
                {grade_items} gi
             ON gi.courseid = c.id
            AND gi.itemtype = \'course\'
            INNER JOIN
                {grade_grades} gg
             ON gg.itemid = gi.id
            AND gg.userid = ra.userid
            LEFT JOIN
                {course_completion_crit_compl} cc
             ON cc.criteriaid = cr.id
            AND cc.userid = ra.userid
            WHERE
                cr.criteriatype = '.COMPLETION_CRITERIA_TYPE_GRADE.'
            AND con.contextlevel = '.CONTEXT_COURSE.'
            AND c.enablecompletion = 1
            AND cc.id IS NULL
            AND gg.finalgrade >= cr.gradepass
        ';

        // Loop through completions, and mark as complete
        $rs = $DB->get_recordset_sql($sql);
        foreach ($rs as $record) {
            $completion = new completion_criteria_completion((array) $record, DATA_OBJECT_FETCH_BY_KEY);
            $completion->mark_complete($record->timecompleted);
        }
        $rs->close();
    }

    /**
     * Return criteria progress details for display in reports
     *
     * @param completion_completion $completion The user's completion record
     * @return array An array with the following keys:
     *     type, criteria, requirement, status
     */
    public function get_details($completion) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $decimalpoints = grade_get_setting($this->course, 'decimalpoints', $CFG->grade_decimalpoints);

        $details = array();
        $details['type'] = get_string('coursegrade', 'completion');
        $details['criteria'] = get_string('graderequired', 'completion');
        $details['requirement'] = format_float($this->gradepass, $decimalpoints);
        $details['status'] = '';

        $grade = format_float($this->get_grade($completion), $decimalpoints);
        if ($grade) {
            $details['status'] = $grade;
        }

        return $details;
    }

    /**
     * Return pix_icon for display in reports.
     *
     * @param string $alt The alt text to use for the icon
     * @param array $attributes html attributes
     * @return pix_icon
     */
    public function get_icon($alt, array $attributes = null) {
        return new pix_icon('i/grades', $alt, 'moodle', $attributes);
    }
}
