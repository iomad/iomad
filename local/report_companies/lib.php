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

class companyrep{

    // Get the jsmodule setup thingy.
    public function getjsmodule() {
        $jsmodule = array(
            'name'     => 'local_report_completion',
            'fullpath' => '/local/report_completion/module.js',
            'requires' => array('base', 'node', 'charts', 'json'),
            'strings' => array(
                )
        );
        return $jsmodule;
    }

    // Create the select list of companies.
    // If the user is in the company managers table then the list is restricted.
    public static function companylist( $user ) {
        global $DB;

        // Create "empty" array.
        $companylist = array();

        // Get the companies they manage.
        $managedcompanies = array();
        if ($managers = $DB->get_records_sql("SELECT * from {company_users} WHERE
                                              userid = :userid
                                              AND managertype != 0", array('userid' => $user->id))) {
            foreach ($managers as $manager) {
                $managedcompanies[] = $manager->companyid;
            }
        }

        // Get companies information.
        if (!$companies = $DB->get_records('company', array(), 'name')) {
            return $companylist;
        }

        // And finally build the list.
        foreach ($companies as $company) {

            // If managers found then only allow selected companies.
            if (!empty($managedcompanies)) {
                if (!in_array($company->id, $managedcompanies)) {
                    continue;
                }
            }
            $companylist[$company->id] = $company;
        }

        return $companylist;
    }

    // Append the company managers to companies.
    public static function addmanagers( &$companies ) {
        global $DB;

        // Iterate over companies adding their managers.
        foreach ($companies as $company) {
            $companymanagers = array();
            $managers = array();
            if ($companymanagers = $DB->get_records_sql("SELECT * from {company_users} WHERE
                                                  companyid = :companyid
                                                  AND managertype = 1", array('companyid' => $company->id))) {
                foreach ($companymanagers as $companymanager) {
                    if ($user = $DB->get_record( 'user', array('id' => $companymanager->userid))) {
                        $managers[$user->id] = $user;
                    }
                }
            }
            $company->managers['company'] = $managers;
            $managers = array();
            if ($companymanagers = $DB->get_records_sql("SELECT * from {company_users} WHERE
                                                  companyid = :companyid
                                                  AND managertype = 2", array('companyid' => $company->id))) {
                foreach ($companymanagers as $companymanager) {
                    if ($user = $DB->get_record( 'user', array('id' => $companymanager->userid))) {
                        $managers[$user->id] = $user;
                    }
                }
            }
            $company->managers['department'] = $managers;
        }
    }

    // Append the company users to companies.
    public static function addusers( &$companies ) {
        global $DB;

        // Iterate over companies adding their managers.
        foreach ($companies as $company) {
            $users = array();
            if ($companyusers = $DB->get_records('company_users', array('companyid' => $company->id))) {
                foreach ($companyusers as $companyuser) {
                    if ($user = $DB->get_record( 'user', array('id' => $companyuser->userid))) {
                        $users[$user->id] = $user;
                    }
                }
            }
            $company->users = $users;
        }
    }

    // Append the company courses to companies.
    public static function addcourses( &$companies ) {
        global $DB;

        // Iterate over companies adding their managers.
        foreach ($companies as $company) {
            $courses = array();
            if ($companycourses = $DB->get_records( 'company_course', array('companyid' => $company->id))) {
                foreach ($companycourses as $companycourse) {
                    if ($course = $DB->get_record( 'course', array('id' => $companycourse->courseid))) {
                        $courses[$course->id] = $course;
                    }
                }
            }
            $company->courses = $courses;
        }
    }

    // List users.
    public static function listusers( $users ) {
        global $CFG;

        echo "<ul class=\"iomad_user_list\">\n";
        foreach ($users as $user) {
            if (!empty($user->id) && !empty($user->email) && !empty($user->firstname) && !empty($user->lastname)) {
                $link = "{$CFG->wwwroot}/user/view.php?id={$user->id}";
                echo "<li><a href=\"$link\">".fullname( $user )."</a> ({$user->email})</li>\n";
            }
        }
        echo "</ul>\n";
    }

}


