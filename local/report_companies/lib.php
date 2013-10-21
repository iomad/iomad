<?php

class companyrep{

    // get the jsmodule setup thingy
    function getJsModule() {
        $jsmodule = array(
            'name'     => 'local_report_completion',
            'fullpath' => '/local/report_completion/module.js',
            'requires' => array('base', 'node', 'charts', 'json'),
            'strings' => array(
                )
        );
        return $jsmodule;
    }

    // create the select list of companies
    // if the user is in the company managers table then the list is restricted
    public static function companylist( $user ) {
        global $DB;

        // "empty" array
        $companylist = array();

        // get the companies they manage
        $managed_companies = array();
        if ($managers = $DB->get_records_sql("SELECT * from {company_users} WHERE 
                                              userid = :userid
                                              AND managertype != 0", array('userid'=>$user->id))) {
            foreach ($managers as $manager) {
                $managed_companies[] = $manager->companyid;
            }
        }

        // get companies information
        if (!$companies = $DB->get_records('company')) {
            return $company_select;
        }

        // ...and finally build the list
        foreach ($companies as $company) {

            // if managers found then only allow selected companies
            if (!empty($managed_companies)) {
                if (!in_array($company->id,$managed_companies)) {
                    continue;
                }
            }
            $companylist[$company->id] = $company;
        }

        return $companylist;
    }

    // append the company managers to companies
    public static function addmanagers( &$companies ) {
        global $DB;

        // iterate over companies adding their managers
        foreach ($companies as $company) {
            $managers = array();
            if ($companymanagers = $DB->get_records_sql("SELECT * from {company_users} WHERE 
                                                  companyid = :companyid
                                                  AND managertype != 0", array('companyid'=>$company->id))) {
                foreach ($companymanagers as $companymanager) {
                    if ($user = $DB->get_record( 'user',array('id'=>$companymanager->userid))) {
                        $managers[$user->id] = $user;
                    }
                }
            }
            $company->managers = $managers;
        }
    }

    // append the company users to companies
    public static function addusers( &$companies ) {
        global $DB;

        // iterate over companies adding their managers
        foreach ($companies as $company) {
            $users = array();
            if ($companyusers = $DB->get_records('company_users', array('companyid'=>$company->id))) {
                foreach ($companyusers as $companyuser) {
                    if($user = $DB->get_record( 'user',array('id'=>$companyuser->userid))) {
                        $users[$user->id] = $user;
                    }
                }
            }
            $company->users = $users;
        }
    }

    // append the company courses to companies
    public static function addcourses( &$companies ) {
        global $DB;

        // iterate over companies adding their managers
        foreach ($companies as $company) {
            $courses = array();
            if ($companycourses = $DB->get_records( 'company_course',array('companyid'=>$company->id))) {
                foreach ($companycourses as $companycourse) {
                    if ($course = $DB->get_record( 'course',array('id'=>$companycourse->courseid))) {
                        $courses[$course->id] = $course;
                    }
                }
            }
            $company->courses = $courses;
        }
    }

    // list users
    function listusers( $users ) {
        global $CFG;

        echo "<ul class=\"iomad_user_list\">\n";
        foreach ($users as $user) {
            if (!empty($usser->id) && !empty($user->email) && !empty($user->firstname) && !empty($user->lastname)) {
                $link = "{$CFG->wwwroot}/user/view.php?id={$user->id}";
                echo "<li><a href=\"$link\">".fullname( $user )."</a> ({$user->email})</li>\n";
            }
        }
        echo "</ul>\n";
    }

}


