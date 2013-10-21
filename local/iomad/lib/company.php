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

    // These are the fields that will be retrieved by
    public $cssfields = array('bgcolor_header', 'bgcolor_content');

    public function __construct($companyid) {
        $this->id = $companyid;
    }

    // factory method to return an instance of this class
    public static function by_shortname($shortname) {
        global $DB;

        $company = $DB->get_record('company', array('shortname' => $shortname), 'id', MUST_EXIST);

        return new company($company->id);
    }

    public function get($fields = '*') {
        global $DB;

        if ( $this->id == 0 ) {
            return '';
        }
        $companyrecord = $DB->get_record('company', array('id' => $this->id), $fields, MUST_EXIST);

        return $companyrecord;
    }

    public function get_name() {
        $companyrecord = $this->get('Name');
        return $companyrecord->name;
    }

    public function get_managertypes() {

        $returnarray = array('0'=> get_string('user', 'block_iomad_company_admin'));
        $systemcontext = get_context_instance(CONTEXT_SYSTEM);
        if (has_capability('block/iomad_company_admin:assign_company_manager', $systemcontext)) {
            $returnarray['1'] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if (has_capability('block/iomad_company_admin:assign_department_manager', $systemcontext)) {
            $returnarray['2'] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        return $returnarray;
    }

    public function get_shortname() {
        $companyrecord = $this->get('shortname');
        return $companyrecord->shortname;
    }

    public function get_logo_filename() {
        global $DB;

        $fs = get_file_storage();
        $context = get_system_context();

        $files = $fs->get_area_files($context->id, 'theme_iomad', 'logo', $this->id,
                                     "sortorder, itemid, filepath, filename", false);

        // there should be only one file, but we'll still use a foreach as
        // the array indexes are based on the hash, just return the first one
        foreach ($files as $f) {
            return $f->get_filename();
        }
    }

    public static function get_companies_rs($page=0, $perpage=0) {
        global $DB;

        return $DB->get_recordset('company', null, 'name', '*', $page, $perpage);
    }

    public static function get_companies_select() {
        global $DB;

        $companies = $DB->get_recordset('company', null, 'name', '*');
        $companyselect = array();
        foreach ($companies as $company) {
            $companyselect[$company->id] = $company->name;
        }
        return $companyselect;
    }

    public static function get_companyname_byid($companyid) {
        global $DB;
        $company = $DB->get_record('company', array('id'=>$companyid));
        return $company->name;
    }

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

    public function add_course($course, $departmentid=0, $own=false) {
        global $DB;
        
        if ($departmentid != 0 ) {
            // adding to a specified department
            $companydepartment = $departmentid;
        } else {
            // put course in default company department
            $companydepartmentnode = self::get_company_parentnode($this->id);
            $companydepartment = $companydepartmentnode->id;
        }
        if (!$DB->record_exists('company_course', array('companyid'=>$this->id,
                                                       'courseid'=>$course->id))) {
            $DB->insert_record('company_course', array('companyid'=>$this->id,
                                                      'courseid'=>$course->id,
                                                      'departmentid'=>$companydepartment));
        }

        // set up defaults for course management
        if (!$DB->get_record('iomad_courses', array('courseid'=>$course->id))) {
            $DB->insert_record('iomad_courses', array('courseid'=>$course->id,
                                                         'licensed'=>0,
                                                         'shared'=>0));
        }
        // set up manager roles
        if ($companymanagers = $DB->get_records_sql("SELECT * FROM {company_users}
                                                     WHERE companyid = :companyid
                                                     AND managertype != 0", array('companyid'=>$this->id))) {
            $companycoursenoneditorrole = $DB->get_record('role',
                                               array('shortname'=>'companycoursenoneditor'));
            $companycourseeditorrole = $DB->get_record('role',
                                                        array('shortname'=>'companycourseeditor'));
            foreach ($companymanagers as $companymanager) {
                if ($user = $DB->get_record('user', array('id'=>$companymanager->userid,
                                                          'deleted'=>0)) ) {
                    if ($DB->record_exists('course', array('id'=>$course->id))) {
                        if (!$own) {
                            // not created by a company manager
                            company_user::enrol($user, array($course->id), $this->id,
                                                $companycoursenoneditorrole->id);
                        } else {
                            if ($companymanager->managertype == 2) {
                                // assign the department manager course access role
                                company_user::enrol($user, array($course->id), $this->id,
                                                    $companycoursenoneditorrole->id);
                            } else {
                                // assign the company manager course access role
                                company_user::enrol($user, array($course->id), $this->id,
                                                    $companycourseeditorrole->id);
                            }
                        }
                    }
                }
            }
        }
        if ($own && $departmentid==0) {
            // add it to the list of company created courses
            if (!$DB->record_exists('company_created_courses', array('companyid'=>$this->id,
                                                                     'courseid'=>$course->id))) {
                $DB->insert_record('company_created_courses', array('companyid'=>$this->id,
                                                                    'courseid'=>$course->id));
            }
        }
    }

    public static function remove_course($course, $companyid, $departmentid=0) {
        global $DB;
        if ($departmentid == 0) {
            // deal with the company departments
            $companydepartments = $DB->get_records('department', array ('company'=>$companyid));
            // check if it was a company created course and remove if it was
            if ($companycourse = $DB->get_record('company_created_courses',
                                                 array('companyid'=>$companyid,
                                                       'courseid'=>$course->id))) {
                $DB->delete_records('company_created_courses', array('id'=>$companycourse->id));
            }
            // check if its an unshared course in iomad
            if ($DB->get_record('iomad_courses', array('courseid'=>$course->id, 'shared'=>0))) {
                $DB->delete_records('iomad_courses', array('courseid'=>$course->id, 'shared'=>0));
            }
            $DB->delete_records('company_course', array('companyid'=>$companyid,
                                                       'courseid'=>$course->id));
        } else {
            // put course in default company department
            $companydepartment = self::get_company_parentnode($companyid);
            self::assign_course_to_department($companydepartment->id, $course->id, $companyid);
        }
    }

    public function get_user_defaults() {
        global $DB;

        $companyrecord = $DB->get_record('company', array('id' => $this->id),
                       'city, country, maildisplay, mailformat, maildigest, autosubscribe,
                        trackforums, htmleditor, screenreader, timezone, lang',
                        MUST_EXIST);

        return $companyrecord;
    }

    public function get_user_ids() {
        global $DB;

                // by default wherecondition retrieves all users except the
                // deleted, not confirmed and guest
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

    public function assign_user_to_company($userid) {
        global $DB;

        $defaultdepartment = self::get_company_parentnode($this->id);
        $userrecord = array();
        $userrecord['departmentid'] = $defaultdepartment->id;
        $userrecord['userid'] = $userid;
        $userrecord['managertype'] = 0;
        $userrecord['companyid'] = $this->id;

        // moving a user
        if (!$DB->insert_record('company_users', $userrecord)) {
            print_error(get_string('cantassignusersdb', 'block_iomad_company_admin'));
        }
        return true;
    }

    // Department functions

    public static function initialise_departments($companyid) {
        global $DB;
        $company = $DB->get_record('company', array('id'=>$companyid));
        $parentnode=array();
        $parentnode['shortname'] = $company->shortname;
        $parentnode['name'] = $company->name;
        $parentnode['company'] = $company->id;
        $parentnode['parent'] = 0;
        $parentnodeid=$DB->insert_record('department', $parentnode);
        // get the company user's ids
        if ($userids = $DB->get_records('company_users', array('companyid'=>$companyid))) {
            foreach ($userids as $userid) {
                $userid->departmentid = $parentnodeid;
                $DB->update_record('company_users', $userid);
            }
        }
        // get the company courses
        if ($companycourses = $DB->get_records('company_course', array('companyid'=>$company->id))) {
            foreach ($companycourses as $companycourse) {
                $companycourse->departmentid = $parentnodeid;
                $DB->update_record('company_course', $companycourse);
            }
        }
    }

    public static function check_valid_department($departmentid) {
        global $DB;
        if ($DB->get_record('department', array('id'=>$departmentid))) {
            return true;
        } else {
            return false;
        }
    }

    public static function get_userlevel($user) {

        global $DB;
        $userdepartment = $DB->get_record('company_users', array('userid'=>$user->id));
        $userlevel = $DB->get_record('department', array('id'=>$userdepartment->departmentid));
        return $userlevel;
    }

    public static function get_departmentbyid($departmentid) {
        global $DB;
        return $DB->get_record('department', array('id'=>$departmentid));
    }

    public static function get_subdepartments($parent) {
        global $DB;

        $returnarray = $parent;
        // check to see if its the top node
        if (isset($parent->id)) {
            if ($children = $DB->get_records('department', array('parent'=>$parent->id))) {
                foreach ($children as $child) {
                    $returnarray->children[]=self::get_subdepartments($child);
                }
            }
        }

        return $returnarray;
    }

    public static function get_subdepartments_list($parent) {
        $subdepartmentstree=self::get_subdepartments($parent);
        $subdepartmentslist = self::get_department_list($subdepartmentstree);
        $returnlist = self::array_flatten($subdepartmentslist);
        unset($returnlist[$parent->id]);
        return $returnlist;
    }

    public static function get_department_list( $tree, $path='' ) {

        $flat_list = array();
        if (isset($tree->id)) {
            $flat_list[$tree->id] = $path . '/' . $tree->name;
        }

        if (!empty($tree->children)) {
            foreach ($tree->children as $child) {
                $flat_list[$child->id] = self::get_department_list($child, $path.'/'.$tree->name);
            }
        }

        return $flat_list;
    }

    public static function get_company_parentnode($companyid) {
        global $DB;
        if (!$parentnode = $DB->get_record('department', array('company'=>$companyid,
                                                               'parent'=>'0'))) {
            return false;
        }
        return $parentnode;
    }

    public static function get_department_parentnode($departmentid) {
        global $DB;
        if ($department = $DB->get_record('department', array('id'=>$departmentid))) {
            $parent = $DB->get_record('department', array('id'=>$department->parent));
            return $parent;
        } else {
            print_error(get_string('errorgettingparentnode', 'block_iomad_company_admin'));
        }
    }

    public static function get_top_department($departmentid) {
        global $DB;
        $department = $DB->get_record('department', array('id'=>$departmentid));
        $parentnode = self::get_company_parentnode($department->company);
        return $parentnode->id;
    }

    public static function get_all_departments($company) {

        $parent_list=array();
        $parentnode = self::get_company_parentnode($company);
        $parent_list[$parentnode->id] = array($parentnode->id=>'/'.$parentnode->name);
        $departmenttree = self::get_subdepartments($parentnode);
        $departmentlist = self::array_flatten($parent_list +
                                              self::get_department_list($departmenttree));
        return $departmentlist;
    }

    public static function array_flatten($array, &$result=null) {

        $r = null === $result;
        $i = 0;
        foreach ($array as $key => $value) {
            $i++;
            if (is_array($value)) {
                self::array_flatten($value, $result);
            } else {
                $result[$key]=$value;
            }
        }
        if ($r) {
            return $result;
        }
    }

    public static function get_all_subdepartments($parentnodeid) {

        $parentnode = self::get_departmentbyid($parentnodeid);
        $parent_list=array();
        $parent_list[$parentnode->id] = $parentnode->name;
        $departmenttree = self::get_subdepartments($parentnode);
        $departmentlist = self::array_flatten($parent_list +
                                              self::get_department_list($departmenttree));
        return $departmentlist;
    }

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

    public static function get_department_users($departmentid) {
        global $DB;
        if ($departmentusers = $DB->get_records('company_users',
                                                 array('departmentid'=>$departmentid))) {
            return $departmentusers;
        } else {
            return array();
        }
    }

    public static function assign_user_to_department($departmentid, $userid) {
        global $DB;

        $userrecord = array();
        $userrecord['departmentid'] = $departmentid;
        $userrecord['userid'] = $userid;
        // moving a user
        if ($currentuser = $DB->get_record('company_users', array('userid'=>$userid))) {
            $currentuser->departmentid=$departmentid;
            if (!$DB->update_record('company_users', $currentuser)) {
                print_error(get_string('cantupdatedepartmentusersdb', 'block_iomad_company_admin'));
            }
        }
        return true;
    }

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
            // we are editing a current department
            if (!$DB->update_record('department', $newdepartment)) {
                print_error(get_string('cantupdatedepartmentdb', 'block_iomad_company_admin'));
            }
        } else {
            // adding a new department
            if (!$DB->insert_record('department', $newdepartment)) {
                print_error(get_string('cantinsertdepartmentdb', 'block_iomad_company_admin'));
            }
        }
        return true;
    }

    public static function delete_department($departmentid) {
        global $DB;
        if (!$DB->delete_records('department', array('id'=>$departmentid))) {
            print_error(get_string('cantdeletedepartmentdb', 'blocks_iomad_company_admin'));
        }
        return true;
    }

    public static function delete_department_recursive($departmentid, $targetdepartment=0) {
        // get all the users from here and below
        $userlist = self::get_recursive_department_users($departmentid);
        $departmentlist = self::get_all_subdepartments($departmentid);
        if ($targetdepartment == 0) {
            // moving users to the parent node of the current department
            $parentnode = self::get_department_parentnode($departmentid);
            $targetdepartment = $parentnode->id;
        }
        foreach ($userlist as $user) {
            //  move the users
            self::assign_user_to_department($targetdepartment, $user->id);
        }
        foreach ($departmentlist as $id => $value) {
            self::delete_department($id);
        }
    }

    public static function can_manage_department($departmentid) {
        global $DB, $USER;
        if (has_capability('block/iomad_company_admin:edit_all_departments',
                                    get_context_instance(CONTEXT_SYSTEM))) {
            return true;
        } else if (!has_capability('block/iomad_company_admin:edit_departments',
                                    get_context_instance(CONTEXT_SYSTEM))) {
            return false;
        } else {
            //get the list of departments at and below the user assignment
            $userhierarchylevel = self::get_userlevel($USER);
            $subhierarchytree = self::get_all_subdepartments($userhierarchylevel);
            $subhieracrhieslist = self::get_department_list($subhierarchytree);
            if (isset($subhieracrhieslist[$departmentid])) {
                // current department is a child of the users assignment
                return true;
            } else {
                return false;
            }
        }
        // we shouldn't get this far, return a default no
        return false;
    }

    public static function get_recursive_department_courses($departmentid) {
        global $DB;

        $departmentlist = self::get_all_subdepartments($departmentid);
        $courselist = array();
        foreach ($departmentlist as $id => $value) {
            $departmentcourses = self::get_department_courses($id);
            $courselist = $courselist + $departmentcourses;
        }
        // get the top level courses
        $companydepartment = self::get_top_department($departmentid);
        if ($companydepartment != $departmentid ) {
            $topdepartmentcourses = self::get_department_courses($companydepartment);
            $courselist = $courselist + $topdepartmentcourses;
        }
        //  Get the shared courses
        $sharedcourses = $DB->get_records('iomad_courses', array('shared'=>1));
        return $courselist + $sharedcourses;
    }

    public static function get_department_courses($departmentid) {
        global $DB;
        if ($departmentcourses = $DB->get_records('company_course',
                                                   array('departmentid'=>$departmentid))) {
            return $departmentcourses;
        } else {
            return array();
        }
    }

    public static function assign_course_to_department($departmentid, $courseid, $companyid) {
        global $DB;

        // moving a course
        // get all the department assignments which may exist taking
        // shared courses into consideration.
        if ($currentcourses = $DB->get_records('company_course',
                                                array('courseid'=>$courseid))) {
            $foundcourse = false;
            foreach ($currentcourses as $currentcourse) {
                // check if the found record belongs to the current company
                if ($DB->get_record('department', array('company'=>$companyid,
                                                        'id'=>$departmentid))) {
                    $foundcourse = true;
                    //  Update it
                    $currentcourse->departmentid = $departmentid;
                    if (!$DB->update_record('company_course', $currentcourse)) {
                        print_error(get_string('cantupdatedepartmentcoursesdb',
                                               'block_iomad_company_admin'));
                    }
                    break;
                }
            }
            if (!$foundcourse) {
                // assigning a shared course to a new company
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
            // assigning a new course to a company
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

    public static function get_departments_by_course($courseid) {
        global $DB;
        if ($depts = $DB->get_records('company_course', array('courseid' => $courseid),
                                                                   null, 'departmentid')) {
            return array_keys($depts);
        } else {
            return array();
        }
    }

    // Licenses stuff

    public static function get_recursive_departments_licenses($departmentid) {

        // get all the courses for this department down
        $courses = self::get_recursive_department_courses($departmentid);
        $licenselist = array();
        foreach ($courses as $course) {
            $courselicenses = self::get_course_licenses($course->courseid);
            $licenselist = $licenselist + $courselicenses;
        }
        return $licenselist;
    }

    public static function get_course_licenses($courseid) {
        global $DB;
        if ($licenses = $DB->get_records('companylicense_courses', array('courseid'=>$courseid),
                                                                          null, 'licenseid')) {
            return $licenses;
        } else {
            return array();
        }
    }

    public static function get_courses_by_license($licenseid) {
        global $DB;
        if ($courseids = $DB->get_records('companylicense_courses', array('licenseid'=>$licenseid),
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

    // Shared course stuff

    public static function create_company_course_group($companyid, $courseid) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // creates a company group within a shared course
        $company = $DB->get_record('company', array('id' => $companyid));
        $data = new object();
        $data->timecreated  = time();
        $data->timemodified = $data->timecreated;
        $data->name = $company->shortname;
        $data->description = "Course group for ".$company->name;
        $data->courseid = $courseid;

        // create the group record
        $groupid = groups_create_group($data);

        // create the pivot table entry
        $grouppivot = array();
        $grouppivot['companyid'] = $companyid;
        $grouppivot['courseid'] = $courseid;
        $grouppivot['groupid'] = $groupid;

        // write the data to the DB
        if (!$DB->insert_record('company_course_groups', $grouppivot)) {
            print_error(get_string('cantcreatecompanycoursegroup', 'block_iomad_company_admin'));
        }
        return $groupid;
    }

    public static function get_company_groupname($companyid, $courseid) {
        global $DB;
        // gets the company course groupname
        if (!$companygroup=$DB->get_record('company_course_groups', array('companyid'=>$companyid,
                                                                          'courseid'=>$courseid))) {
       	    // not got one, create a default
            $companygroup->groupid = self::create_company_course_group($companyid, $courseid);
        }
        // get the group information
        $groupinfo = $DB->get_record('groups', array('id'=>$companygroup->groupid));
        return $groupinfo->name;
    }

    public static function get_company_group($companyid, $courseid) {
        global $DB;
        // gets the company course groupname
        if (!$companygroup=$DB->get_record('company_course_groups', array('companyid'=>$companyid,
                                                                          'courseid'=>$courseid))) {
       	    // not got one, create a default
       	    $companygroup = new stdclass();
            $companygroup->id = self::create_company_course_group($companyid, $courseid);
        }
        // get the group information
        $groupinfo = $DB->get_record('groups', array('id'=>$companygroup->id));
        return $groupinfo;
    }

    public static function add_user_to_shared_course($courseid, $userid, $companyid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // adds a user to a shared course
        // get the group id
        if (!$groupinfo = $DB->get_record('company_course_groups', array('companyid'=>$companyid,
                                                                         'courseid'=>$courseid))) {
            $groupid = self::create_company_course_group($companyid, $courseid);
        } else {
            $groupid = $groupinfo->groupid;
        }

        //  add the user to the group
        groups_add_member($groupid, $userid);
    }

    public static function remove_user_from_shared_course($courseid, $userid, $companyid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // removes a user from a shared course
        // get the group id
        if (!$groupinfo = $DB->get_record('company_course_groups', array('companyid'=>$companyid,
                                                                         'courseid'=>$courseid))) {
            return;  // dont need to remove them.
        } else {
            $groupid = $groupinfo->groupid;
        }

        //  add the user to the group
        groups_remove_member($groupid, $userid);
    }

    public static function delete_company_course_group($companyid, $course, $oktounenroll=false) {
        global $DB;
        // removes a company group within a shared course
        // get the group
        if ($group = self::get_company_group($companyid, $course->id)) {
            // check there are no members of the group unless oktounenroll
            if (!$DB->get_records('company_course_groups', array('groupid'=>$group->id)) ||
                $oktounenroll) {
                // delete the group
                $DB->delete_records('groups', array('id'=>$group->id));
                $DB->delete_records('company_course_groups', array('companyid'=>$companyid,
                                                                   'groupid'=>$group->id,
                                                                   'courseid'=>$course->id));
                self::remove_course($course, $companyid);
                return true;
            } else {
                return "usersingroup";
            }
        }
    }

    public static function company_users_to_company_course_group($companyid, $courseid) {
        global $DB, $CFG;
        // adds all the users to a company group within a shared course

        require_once($CFG->dirroot.'/group/lib.php');

        // get the group
        if (!$groupid = self::get_company_group($companyid, $courseid)) {
            $groupid = self::create_company_course_group($companyid, $courseid);
        }
        // this is used for a course which is becoming shared.
        //  all all current course enrolled users to this company group
        if ($users = $DB->get_records_sql("SELECT userid FROM {user_enrolments}
                                           WHERE enrolid IN (
                                           SELECT id FROM {enrol} WHERE courseid = $courseid)")) {
            foreach ($users as $user) {
                if ($DB->get_record('user', array('id'=>$user->userid))) {
                    groups_add_member($groupid, $user->userid);
                }
            }
        }
    }

    public static function unenrol_company_from_course($companyid, $courseid) {
        global $DB;

        $timenow = time();
        // get the company users
        $companydepartment = self::get_company_parentnode($companyid);
        $companyusers = self::get_recursive_department_users($companydepartment->id);
        if ($group = self::get_company_group($companyid, $courseid)) {
            // end all enrolments now.
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
        $DB->delete_records('company_shared_courses', array('courseid'=>$courseid,
                                                            'companyid'=>$companyid));
    }
}
