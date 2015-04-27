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

class company {
    public $id = 0;

    // These are the fields that will be retrieved by.
    public $cssfields = array('bgcolor_header', 'bgcolor_content');

    public function __construct($companyid) {
        $this->id = $companyid;
    }

    /**
     * Return an instance of the class using the company shortname
     *
     * Paramters -
     *             $userid = int;
     *
     * Returns class object.
     *
     **/
    public static function by_userid($userid) {
        global $DB, $SESSION;

        if (!empty($SESSION->currenteditingcompany)) {
            return new company($SESSION->currenteditingcompany);
        } else {
            if ($companies = $DB->get_records('company_users', array('userid' => $userid), 'companyid')) {
                $company = array_pop($companies);
                return new company($company->companyid);
            } else {
                return false;
            }
        }
    }

    /**
     * Gets the company DB record.
     *
     * Paramters -
     *             $firelds = array();
     *
     * Returns - stdclass();
     *
     **/
    public function get($fields = '*') {
        global $DB;

        if ( $this->id == 0 ) {
            return '';
        }
        $companyrecord = $DB->get_record('company', array('id' => $this->id), $fields, MUST_EXIST);

        return $companyrecord;
    }

    /**
     * Gets the company name for the current instance
     *
     * Returns text;
     *
     **/
    public function get_name() {
        $companyrecord = $this->get('Name');
        return $companyrecord->name;
    }

    /**
     * Gets the types of managers available to the class
     *
     * Returns array();
     *
     **/
    public function get_managertypes() {

        $returnarray = array('0' => get_string('user', 'block_iomad_company_admin'));
        $systemcontext = context_system::instance();
        if (has_capability('block/iomad_company_admin:assign_company_manager', $systemcontext)) {
            $returnarray['1'] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if (has_capability('block/iomad_company_admin:assign_department_manager', $systemcontext)) {
            $returnarray['2'] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        return $returnarray;
    }

    /**
     * Gets the company short name for the current instance
     *
     * Returns text;
     *
     **/
    public function get_shortname() {
        $companyrecord = $this->get('shortname');
        return $companyrecord->shortname;
    }

    /**
     * Gets the company theme name for the current instance
     *
     * Returns text;
     *
     **/
    public function get_theme() {
        $companyrecord = $this->get('theme');
        return $companyrecord->theme;
    }

    /**
     * Gets the file path for the company logo for the current instance
     *
     * Returns text;
     *
     **/
    public function get_logo_filename() {
        global $DB;

        $fs = get_file_storage();
        $context = context_system::instance();

        $files = $fs->get_area_files($context->id, 'theme_iomad', 'logo', $this->id,
                                     "sortorder, itemid, filepath, filename", false);

        // There should be only one file, but we'll still use a foreach as
        // the array indexes are based on the hash, just return the first one.
        foreach ($files as $f) {
            return $f->get_filename();
        }
    }

    /**
     * Gets the record set of all companies
     *
     * Parameters -
     *              $page = int;
     *              $perpage = int;
     *
     * Returns array;
     *
     **/
    public static function get_companies_rs($page=0, $perpage=0) {
        global $DB;

        return $DB->get_recordset('company', null, 'name', '*', $page, $perpage);
    }

    /**
     * Creates an array of companies to be used in a Select menu
     *
     * Returns array;
     *
     **/
    public static function get_companies_select() {
        global $DB, $USER;

        // Is this an admin, or a normal user?
        if (has_capability('block/iomad_company_admin:company_add', context_system::instance())) {
            $companies = $DB->get_recordset('company', null, 'name', '*');
        } else {
            $companies = $DB->get_recordset_sql("SELECT * FROM {company}
                                                 WHERE id IN (
                                                   SELECT companyid FROM {company_users}
                                                   WHERE userid = :userid )
                                                 ORDER BY name", array('userid' => $USER->id));
        }
        $companyselect = array();
        foreach ($companies as $company) {
            if (empty($company->suspended)) {
                $companyselect[$company->id] = $company->name;
            } else {
                $companyselect[$company->id] = $company->name . '(S)';
            }
        }
        return $companyselect;
    }

    /**
     * Gets the name of a company given its ID
     *
     * Parameters -
     *              $companyid = int;
     *
     * Returns text;
     *
     **/
    public static function get_companyname_byid($companyid) {
        global $DB;
        $company = $DB->get_record('company', array('id' => $companyid));
        return $company->name;
    }

    /**
     * Gets the company record given a member
     *
     * Parameters -
     *              $userid = int;
     *
     * Returns stdclass();
     *
     **/
    public static function get_company_byuserid($userid) {
        global $DB;
        $company = $DB->get_record_sql("SELECT c.*
                                        FROM
                                            {company_users} cu
                                            INNER JOIN {company} c ON cu.companyid = c.id
                                        WHERE cu.userid = :userid",
                                       array('userid' => $userid));
        return $company;
    }

    /**
     * Gets the user info category record associated to a company
     *
     * Parameters -
     *              $companyid = int;
     *
     * Returns stdclass() or false;
     *
     **/
    public static function get_category($companyid) {
        global $DB;
        if ($category = $DB->get_record_sql("SELECT uic.id, uic.name FROM
                                             {user_info_category} uic, {company} c
                                             WHERE c.id = ".$companyid."
                                             AND ".$DB->sql_compare_text('c.shortname'). "=".
                                             "'".$DB->sql_compare_text('uic.name')."'")) {
            return $category;
        } else {
            return false;
        }
    }

    /**
     * Associates a course to a company
     *
     * Parameters -
     *              $course = stdclass();
     *              $departmentid = int;
     *              $own = boolean;
     *
     **/
    public function add_course($course, $departmentid=0, $own=false, $licensed=false) {
        global $DB;

        if ($departmentid != 0 ) {
            // Adding to a specified department.
            $companydepartment = $departmentid;
        } else {
            // Put course in default company department.
            $companydepartmentnode = self::get_company_parentnode($this->id);
            $companydepartment = $companydepartmentnode->id;
        }
        if (!$DB->record_exists('company_course', array('companyid' => $this->id,
                                                       'courseid' => $course->id))) {
            $DB->insert_record('company_course', array('companyid' => $this->id,
                                                      'courseid' => $course->id,
                                                      'departmentid' => $companydepartment));
        }

        // Set up defaults for course management.
        if (!$DB->get_record('iomad_courses', array('courseid' => $course->id))) {
            $DB->insert_record('iomad_courses', array('courseid' => $course->id,
                                                         'licensed' => $licensed,
                                                         'shared' => 0));
        }
        // Set up manager roles.
        if (!$licensed) {
            if ($companymanagers = $DB->get_records_sql("SELECT * FROM {company_users}
                                                         WHERE companyid = :companyid
                                                         AND managertype != 0", array('companyid' => $this->id))) {
                $companycoursenoneditorrole = $DB->get_record('role',
                                                   array('shortname' => 'companycoursenoneditor'));
                $companycourseeditorrole = $DB->get_record('role',
                                                            array('shortname' => 'companycourseeditor'));
                foreach ($companymanagers as $companymanager) {
                    if ($user = $DB->get_record('user', array('id' => $companymanager->userid,
                                                              'deleted' => 0)) ) {
                        if ($DB->record_exists('course', array('id' => $course->id))) {
                            if (!$own) {
                                // Not created by a company manager.
                                company_user::enrol($user, array($course->id), $this->id,
                                                    $companycoursenoneditorrole->id);
                            } else {
                                if ($companymanager->managertype == 2) {
                                    // Assign the department manager course access role.
                                    company_user::enrol($user, array($course->id), $this->id,
                                                        $companycoursenoneditorrole->id);
                                } else {
                                    // Assign the company manager course access role.
                                    company_user::enrol($user, array($course->id), $this->id,
                                                        $companycourseeditorrole->id);
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($own && $departmentid == 0) {
            // Add it to the list of company created courses.
            if (!$DB->record_exists('company_created_courses', array('companyid' => $this->id,
                                                                     'courseid' => $course->id))) {
                $DB->insert_record('company_created_courses', array('companyid' => $this->id,
                                                                    'courseid' => $course->id));
            }
        }
    }

    /**
     * Removes a course from a company
     *
     * Parameters -
     *              $course = stdclass();
     *              $companyid = int;
     *              $departmentid = int;
     *
     **/
    public static function remove_course($course, $companyid, $departmentid=0) {
        global $DB;
        if ($departmentid == 0) {
            // Deal with the company departments.
            $companydepartments = $DB->get_records('department', array ('company' => $companyid));
            // Check if it was a company created course and remove if it was.
            if ($companycourse = $DB->get_record('company_created_courses',
                                                 array('companyid' => $companyid,
                                                       'courseid' => $course->id))) {
                $DB->delete_records('company_created_courses', array('id' => $companycourse->id));
            }
            // Check if its an unshared course in iomad.
            if ($DB->get_record('iomad_courses', array('courseid' => $course->id, 'shared' => 0))) {
                $DB->delete_records('iomad_courses', array('courseid' => $course->id, 'shared' => 0));
            }
            $DB->delete_records('company_course', array('companyid' => $companyid,
                                                       'courseid' => $course->id));
        } else {
            // Put course in default company department.
            $companydepartment = self::get_company_parentnode($companyid);
            self::assign_course_to_department($companydepartment->id, $course->id, $companyid);
        }
    }

    /**
     * Gets the copmpany defined user account default variables
     *
     * Returns stdclass();
     *
     **/
    public function get_user_defaults() {
        global $DB;

        $companyrecord = $DB->get_record('company', array('id' => $this->id),
                       'city, country, maildisplay, mailformat, maildigest, autosubscribe,
                        trackforums, htmleditor, screenreader, timezone, lang',
                        MUST_EXIST);

        return $companyrecord;
    }

    /**
     * Get the user ids associated to a company
     * does not pass back any managers
     *
     * returns stdclass();
     *
     **/
    public function get_user_ids() {
        global $DB;

        // By default wherecondition retrieves all users except the
        // deleted, not confirmed and guest.
        $params['companyid'] = $this->id;
        $params['companyidforjoin'] = $this->id;

        $sql = " SELECT u.id, u.id AS mid
                FROM
	                {company_users} cu
                    INNER JOIN {user} u ON (cu.userid = u.id)
                WHERE u.deleted = 0
                      AND cu.managertype = 0";

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';

        return $DB->get_records_sql_menu($sql . $order, $params);
    }

    /**
     * Get all the user ids associated to a company
     *
     * returns stdclass();
     *
     **/
    public function get_all_user_ids() {
        global $DB;

        // By default wherecondition retrieves all users except the
        // deleted, not confirmed and guest.
        $params['companyid'] = $this->id;
        $params['companyidforjoin'] = $this->id;

        $sql = " SELECT u.id, u.id AS mid
                FROM
	                {company_users} cu
                    INNER JOIN {user} u ON (cu.userid = u.id)
                WHERE u.deleted = 0
                AND cu.companyid = :companyid";

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';

        return $DB->get_records_sql_menu($sql . $order, $params);
    }

    /**
     * Associates a user to a company
     *
     * Parameters -
     *              $userid = stdclass();
     *
     **/
    public function assign_user_to_company($userid) {
        global $DB;

        $defaultdepartment = self::get_company_parentnode($this->id);
        $userrecord = array();
        $userrecord['departmentid'] = $defaultdepartment->id;
        $userrecord['userid'] = $userid;
        $userrecord['managertype'] = 0;
        $userrecord['companyid'] = $this->id;

        if (!$DB->get_record('company_users', array('companyid' => $this->id, 'userid' => $userid))) {
            // Moving a user.
            if (!$DB->insert_record('company_users', $userrecord)) {
                print_error(get_string('cantassignusersdb', 'block_iomad_company_admin'));
            }
        }

        // Deal with the company theme.
        $DB->set_field('user', 'theme', $this->get_theme(), array('id' => $userid));

        return true;
    }

    // Department functions.

    /**
     * Set up default company department.
     *
     * Parameters -
     *              $companyid = int;
     *
     **/
    public static function initialise_departments($companyid) {
        global $DB;
        $company = $DB->get_record('company', array('id' => $companyid));
        $parentnode = array();
        $parentnode['shortname'] = $company->shortname;
        $parentnode['name'] = $company->name;
        $parentnode['company'] = $company->id;
        $parentnode['parent'] = 0;
        $parentnodeid = $DB->insert_record('department', $parentnode);
        // Get the company user's ids.
        if ($userids = $DB->get_records('company_users', array('companyid' => $companyid))) {
            foreach ($userids as $userid) {
                $userid->departmentid = $parentnodeid;
                $DB->update_record('company_users', $userid);
            }
        }
        // Get the company courses.
        if ($companycourses = $DB->get_records('company_course', array('companyid' => $company->id))) {
            foreach ($companycourses as $companycourse) {
                $companycourse->departmentid = $parentnodeid;
                $DB->update_record('company_course', $companycourse);
            }
        }
    }

    /**
     * Checks if the department is a valid company one.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns boolean;
     *
     **/
    public static function check_valid_department($departmentid) {
        global $DB;
        if ($DB->get_record('department', array('id' => $departmentid))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the department a user is associated to.
     *
     * Parameters -
     *              $user = stdclass();
     *
     * Returns stdclass();
     *
     **/
    public static function get_userlevel($user) {

        global $DB;
        if ($userdepartment = $DB->get_record('company_users', array('userid' => $user->id))) {
            $userlevel = $DB->get_record('department', array('id' => $userdepartment->departmentid));
            return $userlevel;
        } else {
            return false;
        }
    }

    /**
     * Get the department details given an id.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns stdclass();
     *
     **/
    public static function get_departmentbyid($departmentid) {
        global $DB;
        return $DB->get_record('department', array('id' => $departmentid));
    }

    public static function get_parentdepartments($department) {
        global $DB;

        $returnarray = $department;
        // Check to see if its the top node.
        if (isset($department->id)) {
            if ($department->parent != 0) {
                $parent = self::get_department_parentnode($department->id);
                if ($parent->parent != 0 ) {

                    $returnarray->parents[] = self::get_parentdepartments($parent);
                } else {
                    $returnarray->parents[] = $parent;
                }
            }
        }

        return $returnarray;
    }

    /**
     * Get list of departments which are below this on on the tree.
     *
     * Parameters -
     *              $parent = stdclass();
     *
     * Returns array();
     *
     **/
    public static function get_subdepartments($parent) {
        global $DB;

        $returnarray = $parent;
        // Check to see if its the top node.
        if (isset($parent->id)) {
            if ($children = $DB->get_records('department', array('parent' => $parent->id))) {
                foreach ($children as $child) {
                    $returnarray->children[] = self::get_subdepartments($child);
                }
            }
        }

        return $returnarray;
    }

    /**
     * Get an array of all subdepartments to be used in a select.
     *
     * Parameters -
     *              $parent = stdclass();
     *
     * Returns array();
     *
     **/
    public static function get_subdepartments_list($parent) {
        $subdepartmentstree = self::get_subdepartments($parent);
        $subdepartmentslist = self::get_department_list($subdepartmentstree);
        $returnlist = self::array_flatten($subdepartmentslist);
        unset($returnlist[$parent->id]);
        return $returnlist;
    }

    /**
     * Get a list of all departments
     *
     * Parameters -
     *              $tree = stdclass();
     *              $path = text;
     *
     * Returns array();
     *
     **/
    public static function get_department_list( $tree, $path='' ) {

        $flatlist = array();
        if (isset($tree->id)) {
            $flatlist[$tree->id] = $path . '/' . $tree->name;
        }

        if (!empty($tree->children)) {
            foreach ($tree->children as $child) {
                $flatlist[$child->id] = self::get_department_list($child, $path.'/'.$tree->name);
            }
        }

        return $flatlist;
    }

    /**
     * Get a list of all departments
     *
     * Parameters -
     *              $tree = stdclass();
     *              $path = text;
     *
     * Returns array();
     *
     **/
    public static function get_parents_list($tree, &$return = array()) {

        if (isset($tree->id)) {
            $return[$tree->id] = $tree->id;
        }

        if (!empty($tree->parents)) {
            foreach ($tree->parents as $parent) {
                self::get_parents_list($parent, $return);
            }
        }
    }

    /**
     * The top level department given a companyid
     *
     * Parameters -
     *              $companyid = int;
     *
     * Returns stdclass() || false;
     *
     **/
    public static function get_company_parentnode($companyid) {
        global $DB;
        if (!$parentnode = $DB->get_record('department', array('company' => $companyid,
                                                               'parent' => '0'))) {
            return false;
        }
        return $parentnode;
    }

    /**
     * The parent department given a departmentid
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns stdclass() || false;
     *
     **/
    public static function get_department_parentnode($departmentid) {
        global $DB;
        if ($department = $DB->get_record('department', array('id' => $departmentid))) {
            $parent = $DB->get_record('department', array('id' => $department->parent));
            return $parent;
        } else {
            return false;
        }
    }

    /**
     * All parent departments given a departmentid
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns stdclass() || false;
     *
     **/
    public static function get_department_parentnodes($departmentid) {
        global $DB;

        $parents = array();
        while ($myparent = self::get_department_parentnode($departmentid)) {
            $parents[$myparent->id] = $myparent;
            $departmentid = $myparent->id;
        }
        return $parents;
    }

    /**
     * The top level department given a departmentid
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns int;
     *
     **/
    public static function get_top_department($departmentid) {
        global $DB;
        $department = $DB->get_record('department', array('id' => $departmentid));
        $parentnode = self::get_company_parentnode($department->company);
        return $parentnode->id;
    }

    /**
     * Gets a department tree list given a company id.
     *
     * Parameters -
     *              $companyid = int;
     *
     * Returns array()
     *
     **/
    public static function get_all_departments($company) {

        $parentlist = array();
        $parentnode = self::get_company_parentnode($company);
        $parentlist[$parentnode->id] = array($parentnode->id => '/'.$parentnode->name);
        $departmenttree = self::get_subdepartments($parentnode);
        $departmentlist = self::array_flatten($parentlist +
                                              self::get_department_list($departmenttree));
        return $departmentlist;
    }

    /**
     * function to flatten a multi-dimension array to a single dimension array.
     *
     * Parameters -
     *              $array = array();
     *              &$result = array();
     *
     * Returns array();
     *
     **/
    public static function array_flatten($array, &$result=null) {

        $r = null === $result;
        $i = 0;
        foreach ($array as $key => $value) {
            $i++;
            if (is_array($value)) {
                self::array_flatten($value, $result);
            } else {
                $result[$key] = $value;
            }
        }
        if ($r) {
            return $result;
        }
    }

    /**
     * Gets a list of the sub department tree list given a department id
     * including the passed department.
     *
     * Parameters -
     *              $parentnodeid = int;
     *
     * Returns array()
     *
     **/
    public static function get_all_subdepartments($parentnodeid) {

        $parentnode = self::get_departmentbyid($parentnodeid);
        $parentlist = array();
        $parentlist[$parentnodeid] = $parentnode->name;
        $departmenttree = self::get_subdepartments($parentnode);
        $departmentlist = self::array_flatten($parentlist +
                                              self::get_department_list($departmenttree));
        return $departmentlist;
    }

    /**
     * Gets a list of all users from this department down
     * including the passed department.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_recursive_department_users($departmentid) {
        global $DB;

        $departmentlist = self::get_all_subdepartments($departmentid);
        $userlist = array();
        foreach ($departmentlist as $id => $value) {
            $departmentusers = self::get_department_users($id);
            $userlist = $userlist + $departmentusers;
        }
        return $userlist;
    }

    /**
     * Gets all of the users that manager is responsible for
     *
     * Parameters -
     *             $companyid = int;
     *             $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_my_users($companyid=0, $departmentid=0) {
        global $USER;

        if (empty($companyid)) {
            return array();
        }
        if (empty($departmentid)) {
            if (is_siteadmin($USER->id)) {
                $department = self::get_company_parentnode($companyid);
                $departmentid = $department->id;
            } else {
                $department = self::get_userlevel($USER);
                $departmentid = $department->id;
            }
        }
        return self::get_recursive_department_users($departmentid);
    }

     /**
      * Gets a list of the managers for that user
      *
      * Parameters -
      *             $userid = int;
      *             $managertype = int;
      *
      * Returns string
      *
      **/
     public static function get_my_managers($userid, $managertype) {
         global $DB, $USER;

         // Get the users department.
         $usercompanyinfo = $DB->get_record('company_users', array('userid' => $userid));
         // Get the list of parent departments.
         $userdepartment = self::get_departmentbyid($usercompanyinfo->departmentid);
         $departmentlist = self::get_parentdepartments($userdepartment);
         self::get_parents_list($departmentlist, $departments);

         // Get the managers in that list of departments.
         $managers = $DB->get_records_sql("SELECT userid FROM {company_users}
                                           WHERE managertype = :managertype
                                           AND userid != :userid
                                           AND departmentid IN (".implode(',', array_keys($departments)).")",
                                           array('managertype' => $managertype, 'userid' => $USER->id));
         //  return them.
         return $managers;
     }

    /**
     * Gets a list of the users that manager is responsible for
     *
     * Parameters -
     *             $companyid = int;
     *             $departmentid = int;
     *
     * Returns string
     *
     **/
    public static function get_my_users_list($companyid=0, $departmentid=0) {
        global $USER;

        if (empty($companyid)) {
            return array();
        }
        $userlist = self::get_my_users($companyid, $departmentid);
        $users = array();
        foreach ($userlist as $user) {
            $users[] = $user->userid;
        }
        return implode(',', $users);
    }

    /**
     * Gets a list of the users at this department id
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_department_users($departmentid) {
        global $DB;
        if ($departmentusers = $DB->get_records('company_users',
                                                 array('departmentid' => $departmentid),
                                                 null,
                                                 'userid,id,companyid,managertype,departmentid,suspended')) {
            return $departmentusers;
        } else {
            return array();
        }
    }

    /**
     * Assign a user to a department.
     *
     * Parameters -
     *              $departmentid = int;
     *              $userid = int;
     *
     **/
    public static function assign_user_to_department($departmentid, $userid) {
        global $DB;

        $userrecord = array();
        $userrecord['departmentid'] = $departmentid;
        $userrecord['userid'] = $userid;
        // Moving a user.
        if ($currentuser = $DB->get_record('company_users', array('userid' => $userid))) {
            $currentuser->departmentid = $departmentid;
            if (!$DB->update_record('company_users', $currentuser)) {
                print_error(get_string('cantupdatedepartmentusersdb', 'block_iomad_company_admin'));
            }
        }
        return true;
    }

    /**
     * Creates a new department
     *
     * Parameters -
     *              $departmentid = int;
     *              $companyid = int;
     *              $fullname = string;
     *              $shortname = string;
     *              $parentid = int;
     *
     **/
    public static function create_department($departmentid, $companyid, $fullname,
                                      $shortname, $parentid=0) {
        global $DB;

        $newdepartment = array();
        if (!$parentid) {
            $newdepartment['id'] = $departmentid;
        } else {
            $newdepartment['parent'] = $parentid;
        }
        $newdepartment['company'] = $companyid;
        $newdepartment['name'] = $fullname;
        $newdepartment['shortname'] = $shortname;

        if (isset($newdepartment['id'])) {
            // We are editing a current department.
            if (!$DB->update_record('department', $newdepartment)) {
                print_error(get_string('cantupdatedepartmentdb', 'block_iomad_company_admin'));
            }
        } else {
            // Adding a new department.
            if (!$DB->insert_record('department', $newdepartment)) {
                print_error(get_string('cantinsertdepartmentdb', 'block_iomad_company_admin'));
            }
        }
        return true;
    }

    /**
     * Delete a department.
     *
     * Parameters -
     *              $departmentid = int;
     *
     **/
    public static function delete_department($departmentid) {
        global $DB;
        if (!$DB->delete_records('department', array('id' => $departmentid))) {
            print_error(get_string('cantdeletedepartmentdb', 'blocks_iomad_company_admin'));
        }
        return true;
    }

    /**
     * Delete all departments from this point down moving all the associated things to targetid
     *
     * Parameters -
     *              $departmentid = int;
     *              $targetid = int;
     *
     **/
    public static function delete_department_recursive($departmentid, $targetdepartment=0) {
        // Get all the users from here and below.
        $userlist = self::get_recursive_department_users($departmentid);
        $departmentlist = self::get_all_subdepartments($departmentid);
        if ($targetdepartment == 0) {
            // Moving users to the parent node of the current department.
            $parentnode = self::get_department_parentnode($departmentid);
            $targetdepartment = $parentnode->id;
        }
        foreach ($userlist as $user) {
            //  Move the users.
            self::assign_user_to_department($targetdepartment, $user->id);
        }
        foreach ($departmentlist as $id => $value) {
            self::delete_department($id);
        }
    }

    /**
     * Check if a user is a manger of this department.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Return boolean;
     **/
    public static function can_manage_department($departmentid) {
        global $DB, $USER;
        if (has_capability('block/iomad_company_admin:edit_all_departments',
                                    context_system::instance())) {
            return true;
        } else if (!has_capability('block/iomad_company_admin:edit_departments',
                                    context_system::instance())) {
            return false;
        } else {
            // Get the list of departments at and below the user assignment.
            $userhierarchylevel = self::get_userlevel($USER);
            $subhierarchytree = self::get_all_subdepartments($userhierarchylevel);
            $subhieracrhieslist = self::get_department_list($subhierarchytree);
            if (isset($subhieracrhieslist[$departmentid])) {
                // Current department is a child of the users assignment.
                return true;
            } else {
                return false;
            }
        }
        // We shouldn't get this far, return a default no.
        return false;
    }

    /**
     * Gets a list of all courses from this department down
     * including the passed department.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_recursive_department_courses($departmentid) {
        global $DB;

        $departmentlist = self::get_all_subdepartments($departmentid);
        $courselist = array();
        foreach ($departmentlist as $id => $value) {
            $departmentcourses = self::get_department_courses($id);
            $courselist = $courselist + $departmentcourses;
        }
        // Get the top level courses.
        $companydepartment = self::get_top_department($departmentid);
        if ($companydepartment != $departmentid ) {
            $topdepartmentcourses = self::get_department_courses($companydepartment);
            $courselist = $courselist + $topdepartmentcourses;
        }
        //  Get the shared courses.
        $sharedcourses = $DB->get_records('iomad_courses', array('shared' => 1));
        return $courselist + $sharedcourses;
    }

    /**
     * Gets a list of all courses in this department
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_department_courses($departmentid) {
        global $DB;
        if ($departmentcourses = $DB->get_records('company_course',
                                                   array('departmentid' => $departmentid))) {
            return $departmentcourses;
        } else {
            return array();
        }
    }

    /**
     * Assign a course to this department
     *
     * Parameters -
     *              $departmentid = int;
     *              $courseid = int;
     *              $companyid = int;
     *
     **/
    public static function assign_course_to_department($departmentid, $courseid, $companyid) {
        global $DB;

        // Moving a course.
        // Get all the department assignments which may exist taking
        // shared courses into consideration.
        if ($currentcourses = $DB->get_records('company_course',
                                                array('courseid' => $courseid))) {
            $foundcourse = false;
            foreach ($currentcourses as $currentcourse) {
                // Check if the found record belongs to the current company.
                if ($DB->get_record('department', array('company' => $companyid,
                                                        'id' => $departmentid))) {
                    $foundcourse = true;
                    //  Update it.
                    $currentcourse->departmentid = $departmentid;
                    if (!$DB->update_record('company_course', $currentcourse)) {
                        print_error(get_string('cantupdatedepartmentcoursesdb',
                                               'block_iomad_company_admin'));
                    }
                    break;
                }
            }
            if (!$foundcourse) {
                // Assigning a shared course to a new company.
                $courserecord = array();
                $courserecord['departmentid'] = $departmentid;
                $courserecord['courseid'] = $courseid;
                $courserecord['companyid'] = $companyid;
                if (!$DB->insert_record('company_course', $courserecord)) {
                    print_error(get_string('cantinsertdepartmentcoursesdb',
                                           'block_iomad_company_admin'));
                }
            }
        } else {
            // Assigning a new course to a company.
            $courserecord = array();
            $courserecord['departmentid'] = $departmentid;
            $courserecord['courseid'] = $courseid;
            $courserecord['companyid'] = $companyid;
            if (!$DB->insert_record('company_course', $courserecord)) {
                print_error(get_string('cantinsertdepartmentcoursesdb',
                                       'block_iomad_company_admin'));
            }
        }
        return true;
    }

    /**
     * Get a list of departments a course is associated to
     *
     * Parameters -
     *              $courseid = int;
     *
     *  Return array();
     **/
    public static function get_departments_by_course($courseid) {
        global $DB;
        if ($depts = $DB->get_records('company_course', array('courseid' => $courseid),
                                                                   null, 'departmentid')) {
            return array_keys($depts);
        } else {
            return array();
        }
    }

    // Licenses stuff.

    /**
     * Gets a list of all licenses from this department down
     * including the passed department.
     *
     * Parameters -
     *              $departmentid = int;
     *
     * Returns array()
     *
     **/
    public static function get_recursive_departments_licenses($departmentid) {

        // Get all the courses for this department down.
        $courses = self::get_recursive_department_courses($departmentid);
        $licenselist = array();
        foreach ($courses as $course) {
            $courselicenses = self::get_course_licenses($course->courseid);
            $licenselist = $licenselist + $courselicenses;
        }
        return $licenselist;
    }

    /**
     * Gets a list of all licenses for this course
     *
     * Parameters -
     *              $courseid = int;
     *
     * Returns array()
     *
     **/
    public static function get_course_licenses($courseid) {
        global $DB;
        if ($licenses = $DB->get_records('companylicense_courses', array('courseid' => $courseid),
                                                                          null, 'licenseid')) {
            return $licenses;
        } else {
            return array();
        }
    }

    /**
     * Gets a list of all courses for this license
     *
     * Parameters -
     *              $licenseid = int;
     *
     * Returns array()
     *
     **/
    public static function get_courses_by_license($licenseid) {
        global $DB;
        if ($courseids = $DB->get_records('companylicense_courses', array('licenseid' => $licenseid),
                                                                           null, 'courseid')) {
            $sql = "SELECT id, fullname FROM {course} WHERE id IN (".
                      implode(',', array_keys($courseids)).
                   ") ";
            if ($courses = $DB->get_records_sql($sql)) {
                return $courses;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }

    // Shared course stuff.

    /**
     * Create a company group for the passed course
     *
     * Parameters -
     *              $companyid = int;
     *              $courseid = int;
     *
     * Returns int;
     *
     **/
    public static function create_company_course_group($companyid, $courseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Creates a company group within a shared course.
        $company = $DB->get_record('company', array('id' => $companyid));
        $data = new object();
        $data->timecreated  = time();
        $data->timemodified = $data->timecreated;
        $data->name = $company->shortname;
        $data->description = "Course group for ".$company->name;
        $data->courseid = $courseid;

        // Create the group record.
        $groupid = groups_create_group($data);

        // Create the pivot table entry.
        $grouppivot = array();
        $grouppivot['companyid'] = $companyid;
        $grouppivot['courseid'] = $courseid;
        $grouppivot['groupid'] = $groupid;

        // Write the data to the DB.
        if (!$DB->insert_record('company_course_groups', $grouppivot)) {
            print_error(get_string('cantcreatecompanycoursegroup', 'block_iomad_company_admin'));
        }
        return $groupid;
    }

    /**
     * Get the course group name for the company for the passed course
     *
     * Parameters -
     *              $companyid = int;
     *              $courseid = int;
     *
     * Returns string;
     *
     **/
    public static function get_company_groupname($companyid, $courseid) {
        global $DB;
        // Gets the company course groupname.
        if (!$companygroup = $DB->get_record('company_course_groups', array('companyid' => $companyid,
                                                                          'courseid' => $courseid))) {
            // Not got one, create a default.
            $companygroup->groupid = self::create_company_course_group($companyid, $courseid);
        }
        // Get the group information.
        $groupinfo = $DB->get_record('groups', array('id' => $companygroup->groupid));
        return $groupinfo->name;
    }

    /**
     * Get the course group for the company for the passed course
     *
     * Parameters -
     *              $companyid = int;
     *              $courseid = int;
     *
     * Returns stdclass();
     *
     **/
    public static function get_company_group($companyid, $courseid) {
        global $DB;
        // Gets the company course groupname.
        if (!$companygroup = $DB->get_record('company_course_groups', array('companyid' => $companyid,
                                                                          'courseid' => $courseid))) {
            // Not got one, create a default.
            $companygroup = new stdclass();
            $companygroup->id = self::create_company_course_group($companyid, $courseid);
        }
        // Get the group information.
        $groupinfo = $DB->get_record('groups', array('id' => $companygroup->id));
        return $groupinfo;
    }

    /**
     * Add a company user to a shared course company group.
     *
     * Parameters -
     *              $courseid = int;
     *              $userid = int;
     *              $companyid = int;
     *
     **/
    public static function add_user_to_shared_course($courseid, $userid, $companyid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // Adds a user to a shared course.
        // Get the group id.
        if (!$groupinfo = $DB->get_record('company_course_groups', array('companyid' => $companyid,
                                                                         'courseid' => $courseid))) {
            $groupid = self::create_company_course_group($companyid, $courseid);
        } else {
            $groupid = $groupinfo->groupid;
        }

        // Add the user to the group.
        groups_add_member($groupid, $userid);
    }

    /**
     * Remove a company user to a shared course company group.
     *
     * Parameters -
     *              $courseid = int;
     *              $userid = int;
     *              $companyid = int;
     *
     **/
    public static function remove_user_from_shared_course($courseid, $userid, $companyid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // Removes a user from a shared course.
        // Get the group id.
        if (!$groupinfo = $DB->get_record('company_course_groups', array('companyid' => $companyid,
                                                                         'courseid' => $courseid))) {
            return;  // Dont need to remove them.
        } else {
            $groupid = $groupinfo->groupid;
        }

        // Add the user to the group.
        groups_remove_member($groupid, $userid);
    }

    /**
     * Delete a shared course company group.
     *
     * Parameters -
     *              $companyid = int;
     *              $course = stdclass();
     *              $oktounenroll = boolean;
     *
     **/
    public static function delete_company_course_group($companyid, $course, $oktounenroll=false) {
        global $DB;
        // Removes a company group within a shared course.
        // Get the group.
        if ($group = self::get_company_group($companyid, $course->id)) {
            // Check there are no members of the group unless oktounenroll.
            if (!$DB->get_records('company_course_groups', array('groupid' => $group->id)) ||
                $oktounenroll) {
                // Delete the group.
                $DB->delete_records('groups', array('id' => $group->id));
                $DB->delete_records('company_course_groups', array('companyid' => $companyid,
                                                                   'groupid' => $group->id,
                                                                   'courseid' => $course->id));
                self::remove_course($course, $companyid);
                return true;
            } else {
                return "usersingroup";
            }
        }
    }

    /**
     * Adds all company users to a shared course company group.
     *
     * Parameters -
     *              $companyid = int;
     *              $courseid = int;
     *
     **/
    public static function company_users_to_company_course_group($companyid, $courseid) {
        global $DB, $CFG;
        // Adds all the users to a company group within a shared course.

        require_once($CFG->dirroot.'/group/lib.php');

        // Get the group.
        if (!$groupid = self::get_company_group($companyid, $courseid)) {
            $groupid = self::create_company_course_group($companyid, $courseid);
        }
        // This is used for a course which is becoming shared.
        //  All current course enrolled users to this company group.
        if ($users = $DB->get_records_sql("SELECT userid FROM {user_enrolments}
                                           WHERE enrolid IN (
                                           SELECT id FROM {enrol} WHERE courseid = $courseid)")) {
            foreach ($users as $user) {
                if ($DB->get_record('user', array('id' => $user->userid))) {
                    groups_add_member($groupid, $user->userid);
                }
            }
        }
    }

    /**
     * Removes all company users and group from a course.
     *
     * Parameters -
     *              $companyid = int;
     *              $courseid = int;
     *
     **/
    public static function unenrol_company_from_course($companyid, $courseid) {
        global $DB;

        $timenow = time();
        // Get the company users.
        $companydepartment = self::get_company_parentnode($companyid);
        $companyusers = self::get_recursive_department_users($companydepartment->id);
        if ($group = self::get_company_group($companyid, $courseid)) {
            // End all enrolments now..
            if ($users = $DB->get_records_sql("SELECT * FROM {user_enrolments}
                                               WHERE enrolid IN (
                                                SELECT id FROM {enrol}
                                                WHERE courseid = $courseid)
                                               AND userid IN (".
                                                implode(',', array_keys($companyusers)).
                                               ")")) {
                foreach ($users as $user) {
                    $user->timeend = $timenow;
                    $DB->update_record('user_enrolments', $user);
                }
            }
            $DB->delete_records('company_course_groups', array('groupid', $group));
        }
        $DB->delete_records('company_shared_courses', array('courseid' => $courseid,
                                                            'companyid' => $companyid));
    }

    /**
     * Updates the theme reference for all the users in the company
     *
     * Parameters -
     *              $theme = string;
     *
     **/
    public function update_theme($theme) {
        global $DB;

        // Get the company users.
        $users = $this->get_all_user_ids();

        // Update their theme.
        foreach ($users as $userid) {
            if ($user = $DB->get_record('user', array('id' => $userid))) {
                $user->theme = $theme;
                $DB->update_record('user', $user);
            }
        }
    }

    /**
     * Suspends or Unsuspends a company and all of their users.
     *
     * Parameters -
     *              $theme = string;
     *
     **/
    public function suspend($suspend) {
        global $DB;

        // Get the company users.
        $users = $this->get_all_user_ids();

        // Update their theme.
        foreach ($users as $userid) {
            if ($user = $DB->get_record('user', array('id' => $userid))) {
                if (! $DB->get_record('company_users', array('userid' => $user->id, 'companyid' => $this->id, 'suspended' => 1))) {
                    $user->suspended  = $suspend;
                    $DB->update_record('user', $user);
                }
                if (!empty($suspend)) {
                    \core\session\manager::kill_user_sessions($user->id);
                }
            }
        }

        // Set the suspend field for the company.
        $DB->set_field('company', 'suspended', $suspend, array('id' => $this->id));
    }
}
