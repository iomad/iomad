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

class attendancerep{

    // check the user and the companyid are allowed
    public function confirm_user_company( $user, $companyid ) {
        global $DB;

        // companyid is defined?
        if ($companyid==0) {
            return true;
        }

        // user must either be in the companymanager table for THIS company
        // or not at all
        if ($companies = $DB->get_records('companymanager', array('userid'=>$user->id))) {
            foreach ($companies as $company) {
                if ($company->companyid == $companyid) {
                    return true;
                }
            }

            // if we get this far then there's a problem
            return false;
        }

        // not in table, so that's fine
        return true;
    }

    // create the select list of courses
    static public function courseselectlist($companyid=0) {
        global $DB;
        global $SITE;

        // "empty" array
        $course_select = array();

        // if the companyid=0 then there's no courses
        if ($companyid==0) {
            return $course_select;
        }

        // get courses for given company
        if (!$companycourses = $DB->get_records('company_course', array('companyid'=>$companyid),
                                                                        null, 'courseid')) {
            return $course_select;
        } else {
            $companyselect = " course in (".implode(',', array_keys($companycourses)).")";
        }

        if (!$classmodinfo = $DB->get_record('modules', array('name'=>'courseclassroom'))) {
            return $course_select;
        }
        if (!$courses = $DB->get_records_sql("SELECT DISTINCT course FROM {course_modules}
                                              WHERE module=$classmodinfo->id AND $companyselect")) {
            return $course_select;
        }
        // get the course names and put them in the list
        foreach ($courses as $course) {
            if ($course->course == $SITE->id) {
                continue;
            }
            $coursefull = $DB->get_record('course', array('id'=>$course->course));
            $course_select[$coursefull->id] = $coursefull->fullname;
        }
        return $course_select;
    }

}
