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
 * Local IOMAD iomad class definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use cache;
use context;
use context_system;
use moodle_url;
use moodleform;
use local_iomad\custom_context\context_company;
use required_capability_exception;

/**
 * Local IOMAD iomad class definition
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class iomad {

    /**
     * Registers the IOMAD site
     * @param $parms - (array)
     */
    public static function register_site($data) {
        global $CFG;

        // Add in the missing data.
        $data['siteurl'] = $CFG->wwwroot;
        $data['tenants'] = $DB->count_records('company');
        $data['siteid'] = get_site_identifier();
        $url = new moodle_url(
            'https://www.iomad.org/wp-json/contact-form-7/v1/contact-forms/4445/feedback',
            [
                '_wpcf7_unit_tag' => 'wpcf7-f4445-p5646-o1',
            ]
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);

        curl_close($ch);
    }

    /**
     * Gets the current users company ID depending on
     * if the user is an admin and editing a company or is a
     * company user tied to a company.
     * @param $context - stdclass()
     * @return int
     */
    public static function get_my_companyid($context, $required = true) {
        global $SESSION, $USER, $DB, $CFG;

        // Are we logged in?
        if (
            during_initial_install() ||
            (empty($USER->id) &&
                (empty($SESSION->currenteditingcompany) &&
                    empty($CFG->foundcompanyid))) ||
            !$DB->get_manager()->table_exists('company')
        ) {
            return -1;
        }

        // Set the companyid to bypass the company select form if possible.
        $companyid = 0;
        if (!empty($SESSION->currenteditingcompany)) {
            $companyid = $SESSION->currenteditingcompany;
        } else if (self::is_company_user()) {
            $companyid = self::companyid();
        } else if (!self::has_capability('block/iomad_company_admin:company_view_all', $context) && $required) {
            if (self::has_capability('block/iomad_company_admin:company_edit', $context)) {
                if (!empty($SESSION->currenteditingcompany)) {
                    return $SESSION->currenteditingcompany;
                } else {
                    redirect(
                        new moodle_url(
                            '/blocks/iomad_company_admin/index.php'
                        ),
                        get_string('pleaseselect', 'block_iomad_company_admin')
                    );
                }
            }
        } else if (!empty($CFG->foundcompanyid)) {
            // If the SESSION variable isn't set up when we initially find the company id
            // e.g. on hostname matching - we end up here.
            $companyid = $CFG->foundcompanyid;
            $SESSION->currenteditingcompany = $CFG->foundcompanyid;

            // Forget this from now on.
            unset($CFG->foundcompanyid);
        }

        return $companyid;
    }

    /**
     * Check to see if a user is associated to a company.
     *
     * Returns int or false;
     *
     **/
    public static function is_company_user($user = null) {
        global $USER, $DB, $SESSION;

        // Are we being passed a user?
        if (empty($user)) {
            $user = $USER;
        }

        if (empty($user->id) && empty($SESSION->currenteditingcompany)) {
            // We are installing.  Go no further.
            return false;
        }

        if ($user->id == $USER->id && !empty($SESSION->currenteditingcompany)) {
            return $SESSION->currenteditingcompany;
        } else if ($usercompanies = $DB->get_records('company_users', ['userid' => $user->id], 'id', 'id,companyid', 0, 1)) {
            $usercompany = array_pop($usercompanies);

            // Cache this if it's the current user.
            if ($user->id == $USER->id) {
                $SESSION->currenteditingcompany = $usercompany->companyid;
            }

            return $usercompany->companyid;
        } else {
            return false;
        }
    }

    /**
     * Check to see if a user is a manager in a company.
     *
     * Returns int or false;
     *
     **/
    public static function is_company_admin($user = null) {
        global $USER, $DB;

        // Are we being passed a user?
        if (empty($user)) {
            $user = $USER;
        }

        if ($usercompany = $DB->get_record('company_users', ['userid' => $user->id])) {
            if ($usercompany->managertype > 0) {
                return $usercompany->companyid;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get a users company id.
     *
     * Returns int;
     *
     **/
    public static function companyid() {
        global $USER;

        if (self::is_company_user()) {
            self::load_company();
            return $USER->company->id;
        }
        return 0;
    }

    /**
     * Get a users company shortname.
     *
     * Returns text;
     *
     **/
    public static function companyshortname() {
        global $USER;

        if (self::is_company_user()) {
            self::load_company();
            return $USER->company->shortname;
        }
        return "";
    }

    /**
     * Set up a users company in their profile.
     *
     **/
    public static function load_company() {
        global $USER;

        if (!isset($USER->company->id)) {
            if (self::is_company_user()) {
                $company = company::by_userid($USER->id);
                $fields = ['id', 'shortname', 'name'];
                if ($company->cssfields) {
                    $fields = array_merge($fields, $company->cssfields);
                }

                $USER->company = $company->get($fields);
                $USER->company->logo_filename = $company->get_logo_url($company->id);
            }
        }
    }

    /**
     * Get the company Custom CSS given an ID.
     *
     * Parameters = $companyid = int;
     *
     * Returns text;
     **/
    public static function get_company_customcss($companyid) {
        global $DB;

        if ($companycustomcss = $DB->get_field('company', 'customcss', ['id' => $companyid])) {
            return $companycustomcss;
        } else {
            return '';
        }
    }

    /**
     * Get the company main colour given an ID.
     *
     * Parameters = $companyid = int;
     *
     * Returns text;
     **/
    public static function get_company_maincolor($companyid) {
        global $DB;

        if ($companyothercss = $DB->get_field('company', 'maincolor', ['id' => $companyid])) {
            return 'body {color: ' . $companyothercss . ' !important}';
        } else {
            return '';
        }
    }

    /**
     * Get the company heading colour given an ID.
     *
     * Parameters = $companyid = int;
     *
     * Returns text;
     **/
    public static function get_company_headingcolor($companyid) {
        global $DB;

        if ($companyothercss = $DB->get_field('company', 'headingcolor', ['id' => $companyid])) {
            return '.block .header .title h2, .block .content h3 {color: ' . $companyothercss . ' !important}';
        } else {
            return '';
        }
    }

    /**
     * Get the company link colour given an ID.
     *
     * Parameters = $companyid = int;
     *
     * Returns text;
     **/
    public static function get_company_linkcolor($companyid) {
        global $DB;

        if ($companyothercss = $DB->get_field('company', 'linkcolor', ['id' => $companyid])) {
            return 'a {color: ' . $companyothercss . ' !important}';
        } else {
            return '';
        }
    }

    /**
     * SQL text processing to add a company course table join
     *
     * Parameters - $alias = text;
     *
     * Returns text;
     *
     **/
    public static function join_company_course($alias = "{course}") {
        $companyid = self::companyid();
        if ($companyid > 0) {
            return " INNER JOIN {company_course} ON $alias.id = {company_course}.courseid
                     AND {company_course}.companyid = $companyid ";
        } else {
            return "";
        }
    }

    /**
     * SQL text processing to add a company user table join
     *
     * Parameters - $alias = text;
     *
     * Returns text;
     *
     **/
    public static function join_company_user($alias = "{user}") {
        if ($companyshortname = self::companyshortname()) {
            return " INNER JOIN {user_info_data} uid ON (
                        uid.userid = $alias.id
                        AND uid.data = '" . str_replace("'", "''", $companyshortname) . "')
                     INNER JOIN {user_info_field} uif ON (
                        uif.id = uid.fieldid
                        AND uif.shortname = 'company'
                     )";
        } else {
            return "";
        }
    }

    /**
     * Add license courses to the list of my courses
     *
     * Parameters - &$mycourses = [];
     *
     **/
    public static function iomad_add_license_courses(&$mycourses) {
        global $DB, $CFG, $USER;
        // Get the list of courses the user has a valid license for but not already enroled in.
        $currentcourses = $DB->get_records_select(
            'course',
            "id IN (
                 SELECT clu.licensecourseid
                 FROM {companylicense_users} clu
                 WHERE clu.userid = :userid
                 AND clu.isusing = 0
             )",
             ['userid' => $USER->id]);
        if ($licensecourses = $currentcourses) {
            $mycourses = $mycourses + $licensecourses;
        }
        return;
    }

    /**
     * Add shared courses to the list of courses
     *
     * Parameters - &$courses = [];
     *
     **/
    public static function iomad_add_shared_courses(&$courses) {
        global $DB, $CFG, $USER;

        if (!empty($USER->profile['company'])) {
            $company = company::get_company_byuserid($USER->id);
            $sharedcourses = $DB->get_records_select(
                'course',
                "id IN (
                     SELECT courseid FROM {iomad_courses}
                     WHERE shared=1
                     AND licensed = 0
                 ) OR id IN (
                     SELECT pc.courseid FROM
                     {iomad_courses} pc
                     JOIN {company_shared_courses} csc
                     ON (
                         csc.courseid=pc.courseid
                         AND csc.companyid = :companyid
                         AND pc.licensed = 0
                     )
                 )",
                ['companyid' => $company->id]);
        } else {
            $sharedcourses = $DB->get_records_select(
                'course',
                "id IN (
                     SELECT courseid FROM {iomad_courses}
                     WHERE shared=1
                 )");
        }
        if (!empty($sharedcourses) && !empty($courses)) {
            foreach ($courses as $course) {
                if (!empty($sharedcourses[$course->id])) {
                    unset($sharedcourses[$course->id]);
                }
            }
            $courses = $courses + $sharedcourses;
        }
        return;
    }

    /**
     * IOMAD:
     * Filter profile field categories to only show 'company' categories for the
     * current user. All other pass through as normal
     * @param array $categories list of category objects
     * @return array filtered list of categories
     */
    public static function iomad_filter_profile_categories($categories, $userid = 0, $companyid = 0) {
        global $DB, $USER;

        if (empty($userid) || $userid == -1) {
            $user = $USER;
            if (empty($companyid)) {
                $companyid = self::get_my_companyid(context_system::instance(), false);
            }
            $user->company = $DB->get_record('company', ['id' => $companyid]);
        } else {
            $user = $DB->get_record('user', ['id' => $userid]);
            $user->company = company::get_company_byuserid($userid);
        }

        $iomadcategories = [];
        foreach ($categories as $id => $category) {

            // Try to find category in company list.
            if ($company = $DB->get_record('company', ['profileid' => $id])) {

                // If this is not the user's company then do not include.
                if (!empty($user->company->id)) {
                    if ($user->company->id == $company->id) {
                        $iomadcategories[$id] = $category;
                    }
                }
            } else {
                $iomadcategories[$id] = $category;
            }
        }

        return $iomadcategories;
    }

    /**
     * IOMAD:
     * Filter categories to only show 'company' categories for the
     * current user. All other pass through as normal
     * @param array $categories list of category objects
     * @return array filtered list of categories
     */
    public static function iomad_filter_categories($categories) {
        global $DB, $USER;

        $contextsystem = context_system::instance();

        // If we aren't already set up - do nothing.
        if (!$DB->get_manager()->table_exists('company')) {
            return $categories;
        }

        // Check if its the client admin.
        if (self::has_capability('block/iomad_company_admin:company_view_all', $contextsystem) && empty($userid)) {
            return $categories;
        }

        if ($companyid = self::get_my_companyid($contextsystem)) {
            $company = $DB->get_record('company', ['id' => $companyid]);
        } else {
            $company = (object) ['id' => 0];
        }

        // Get the cache objects.
        $allcompanycategoriescache = cache::make('local_iomad', 'allcompanycategories');
        $companycategoriescache = cache::make('local_iomad', 'companycategories');
        $companycoursecategoriescache = cache::make('local_iomad', 'companycoursecategories');

        // Get all of the company course categories including children.
        if (!$allcompanycategories = $allcompanycategoriescache->get('all')) {
            $allcompanycategories = [];
            $companyroots = $DB->get_records('company', [], 'category', 'category');
            foreach ($companyroots as $companyroot) {
                $allcompanycategories[$companyroot->category] = $companyroot->category;
                $children = $DB->get_records_sql(
                    "SELECT DISTINCT id
                     FROM {course_categories}
                     WHERE " . $DB->sql_like("path", ":parentpath"),
                    ['parentpath' => "/" . $companyroot->category . "/%"]);
                foreach ($children as $child) {
                    $allcompanycategories[$child->id] = $child->id;
                }
            }
            $allcompanycategoriescache->set('all', $allcompanycategories);
        }

        // Get the current company course categories.
        if (!empty($company->category)) {
            if (!$mycompanycategories = $companycategoriescache->get($company->id)) {
                $mycompanycategories = $DB->get_records_sql(
                    "SELECT DISTINCT id
                     FROM {course_categories}
                     WHERE " . $DB->sql_like('path', ':companycategorysearch'),
                    ['companycategorysearch' => '/' . $company->category . '%']);
                $companycategoriescache->set($company->id, $mycompanycategories);
            }
        } else {
            $mycompanycategories = [];
        }

        // Get the categories for the courses the user is enrolled on.
        $usercourses = enrol_get_users_courses($USER->id);
        $usercategories = [];
        foreach ($usercourses as $usercourse) {
            $usercategories[$usercourse->category] = $usercourse->category;
        }

        // Get all of the categories of courses assigned to the company.
        if (
            !empty($company->id) &&
            !$companycourses = $companycoursecategoriescache->get($company->id)
        ) {
            $companycourses = $DB->get_records_sql(
                "SELECT distinct c.category
                 FROM {course} c
                 JOIN {company_course} cc ON (c.id = cc.courseid)
                 WHERE cc.companyid = :companyid",
                ['companyid' => $companyid]
            );
            $companycoursecategoriescache->set($company->id, $companycourses);
        }

        // Get all of the categories of open shared courses.
        $sharedcourses = [];
        if ($sharedcategories = $DB->get_records_sql(
            "SELECT distinct cc.path
             FROM {course} c
             JOIN {course_categories} cc ON (c.category = cc.id)
             JOIN {iomad_courses} ic ON (c.id = ic.courseid)
             WHERE ic.shared = 1")) {
            foreach ($sharedcategories as $sharedcategory) {
                $sharedpaths = explode('/', $sharedcategory->path);
                foreach ($sharedpaths as $sharedpath) {
                    if (!empty($sharedpath)) {
                        $sharedcourses[$sharedpath] = $sharedpath;
                    }
                }
            }
        }

        // Set up the return array.
        $iomadcategories = [];

        // Process the passed categories.
        foreach ($categories as $id => $category) {

            // Is this a company category?
            if (!empty($mycompanycategories[$id])) {
                $iomadcategories[$id] = $category;
            }

            // Is this a category which has a course you are enrolled on?
            if (!empty($usercategories[$id])) {
                $iomadcategories[$id] = $category;
            }

            // Is this a category for a course assigned to the company?
            if (!empty($companycourses[$id])) {
                $iomadcategories[$id] = $category;
            }

            // Is this an open shared course category?
            if (!empty($sharedcourses[$id])) {
                $iomadcategories[$id] = $category;
            }

            // Is this another company category?
            if (empty($allcompanycategories[$id])) {
                $iomadcategories[$id] = $category;
            }
        }

        return $iomadcategories;
    }

    /**
     * IOMAD:
     * Filter courses to only show 'company' courses for the
     * current user. All other pass through as normal
     * @param array $courses list of courses objects
     * @return array filtered list of courses
     */
    public static function iomad_filter_courses($courses) {
        global $DB, $USER;

        $contextsystem = context_system::instance();

        // Check if its the client admin.
        if (self::has_capability('block/iomad_company_admin:company_view_all', $contextsystem)) {
            return $courses;
        }

        $mycompanyid = self::get_my_companyid($contextsystem);

        $iomadcourses = [];
        foreach ($courses as $id => $course) {
            // Try to find category in company list.
            if ($DB->get_record('company_course', [
                'courseid' => $id,
                'companyid' => $mycompanyid,
            ])) {
                // Include as tied to company.
                $iomadcourses[$id] = $course;
            } else if ($DB->get_record('iomad_courses', [
                'courseid' => $id,
                'shared' => 1,
            ])) {
                // Include as open shared.
                $iomadcourses[$id] = $course;
            } else if (!$DB->get_records('company_course', ['courseid' => $id])) {
                // Include as not a companycourse.
                $iomadcourses[$id] = $course;
            }
        }

        return $iomadcourses;
    }

    /**
     * IOMAD:
     * Add in potential courses for the current user
     * so they can see the calendar events in their calendar
     * @param array $courses list of courses objects
     * @return array filtered list of courses
     */
    public static function add_calendar_trainingevent_courses($courses) {
        global $DB, $USER;

        $context = context_system::instance();
        $companyid = self::get_my_companyid($context, false);

        if (!empty($companyid)) {
            $companyselfenrolcourses = $DB->get_records_sql(
                "SELECT DISTINCT c.id,
                                 c.category,
                                 c.sortorder,
                                 c.shortname,
                                 c.fullname,
                                 c.idnumber,
                                 c.startdate,
                                 c.defaultgroupingid,
                                 c.groupmodeforce,
                                 c.groupmode,
                                 c.visible
                 FROM {enrol} e
                 JOIN {course} c ON (e.courseid = c.id)
                 JOIN {trainingevent} t ON (c.id = t.course AND e.courseid = t.course)
                 WHERE e.enrol = :enrol
                 AND e.status = 0
                 AND c.id IN (
                    SELECT courseid FROM {company_course}
                    WHERE companyid = :companyid)",
                [
                    'companyid' => $companyid,
                    'enrol' => 'self',
                ]
            );

            // Add them.
            foreach ($companyselfenrolcourses as $course) {
                $courses[$course->id] = $course;
            }
            $sharedselfenrolcourses = $DB->get_records_sql(
                "SELECT DISTINCT c.id,
                                 c.category,
                                 c.sortorder,
                                 c.shortname,
                                 c.fullname,
                                 c.idnumber,
                                 c.startdate,
                                 c.defaultgroupingid,
                                 c.groupmodeforce,
                                 c.groupmode,
                                 c.visible
                 FROM {enrol} e
                 JOIN {course} c ON (e.courseid = c.id)
                 JOIN {trainingevent} t ON (c.id = t.course AND e.courseid = t.course)
                 WHERE e.enrol = :enrol
                 AND e.status = 0
                 AND c.id IN (
                    SELECT courseid FROM {iomad_courses}
                    WHERE shared = 1)",
                ['enrol' => 'self']
            );
            // Add them.
            foreach ($sharedselfenrolcourses as $course) {
                $courses[$course->id] = $course;
            }

            // Check if there are any courses from 'blanket' licenses.
            if ($blanketlicenses = $DB->get_records_select(
                'companylicense',
                "companyid = :companyid
                 AND type = :type
                 AND startdate < :startdate
                 AND expirydate > :expirydate",
                ['companyid' => $companyid,
                 'type' => 4,
                 'startdate' => time(),
                 'expirydate' => time(),
                 ])) {
                $blanketcourses = [];
                foreach ($blanketlicenses as $blanketlicense) {
                    $licensecourses = $DB->get_records_sql(
                        "SELECT DISTINCT c.id,
                                         c.category,
                                         c.sortorder,
                                         c.shortname,
                                         c.fullname,
                                         c.idnumber,
                                         c.startdate,
                                         c.defaultgroupingid,
                                         c.groupmodeforce,
                                         c.groupmode,
                                         c.visible
                         FROM {course} c
                         JOIN {companylicense_courses} clc ON (c.id = clc.courseid)
                         JOIN {trainingevent} t ON (c.id = t.course AND clc.courseid = t.course)
                         WHERE clc.licenseid = :licenseid",
                        ['licenseid' => $blanketlicense->id]
                    );
                    // Add them.
                    foreach ($licensecourses as $course) {
                        $courses[$course->id] = $course;
                    }
                }
            }

            // Check for any unused license courses.
            $mynotstartedlicense = $DB->get_records_sql(
                "SELECT DISTINCT c.id,
                                  c.category,
                                  c.sortorder,
                                  c.shortname,
                                  c.fullname,
                                  c.idnumber,
                                  c.startdate,
                                  c.defaultgroupingid,
                                  c.groupmodeforce,
                                  c.groupmode,
                                  c.visible
                 FROM {companylicense_users} clu
                 JOIN {course} c ON (c.id = clu.licensecourseid)
                 JOIN {trainingevent} t ON (c.id = t.course AND clu.licensecourseid = t.course)
                 WHERE clu.userid = :userid
                 AND clu.isusing = 0",
                [
                    'userid' => $USER->id,
                    'companyid' => $companyid,
                ]
            );

            // Add them.
            foreach ($mynotstartedlicense as $course) {
                $courses[$course->id] = $course;
            }
        }

        return $courses;
    }

    /**
     * IOMAD:
     * Filter objects to only show 'company' objects for the
     * current user. All other pass through as normal
     * @param array $objects list of competency objects
     * @return array filtered list of objects.
     */
    public static function get_company_frameworkids($companyid) {
        global $DB;

        $companyframeworks = $DB->get_records('company_comp_frameworks', ['companyid' => $companyid]);
        $closedsharedframeworks = $DB->get_records('company_shared_frameworks', ['companyid' => $companyid]);
        $opensharedframeworks = $DB->get_records('iomad_frameworks', ['shared' => 1]);
        $return = [];
        foreach ($companyframeworks as $framework) {
            $return[$framework->frameworkid] = $framework->frameworkid;
        }
        foreach ($closedsharedframeworks as $framework) {
            $return[$framework->frameworkid] = $framework->frameworkid;
        }
        foreach ($opensharedframeworks as $framework) {
            $return[$framework->frameworkid] = $framework->frameworkid;
        }
        return $return;
    }

    /**
     * IOMAD:
     * Filter objects to only show 'company' objects for the
     * current user. All other pass through as normal
     * @param array $objects list of competency objects
     * @return array filtered list of objects.
     */
    public static function get_company_templateids($companyid) {
        global $DB;

        $companytemplates = $DB->get_records('company_comp_templates', ['companyid' => $companyid]);
        $closedsharedtemplates = $DB->get_records('company_shared_templates', ['companyid' => $companyid]);
        $opensharedtemplates = $DB->get_records('iomad_templates', ['shared' => 1]);
        $return = [];
        foreach ($companytemplates as $template) {
            $return[$template->templateid] = $template->templateid;
        }
        foreach ($closedsharedtemplates as $template) {
            $return[$template->templateid] = $template->templateid;
        }
        foreach ($opensharedtemplates as $template) {
            $return[$template->templateid] = $template->templateid;
        }
        return $return;
    }

    /** IOMAD:
     * Check if a category is attached to a company AND
     * the user belongs to a different company.
     * Otherwise, return true
     */
    public static function iomad_check_category($category) {
        global $CFG, $DB, $USER;

        // If we are installing this will be called to build
        // the basic category tree so just say yes.
        if (during_initial_install() || is_siteadmin($USER->id)) {
            return true;
        }

        // Try to find the category in company list.
        if (!empty($category->id) && $company = $DB->get_record('company', ['category' => $category->id])) {

            // If this is not the user's company then we return false.
            if ($DB->get_record('company_users', ['userid' => $USER->id, 'companyid' => $company->id])) {
                // User is not assigned to this company - hide the category.
                return true;
            } else {
                return false;
            }
        }
        // Category is visible.
        return true;
    }

    /**
     * Check if a course category can be seen by a user.
     *
     * @param int $categoryid
     * @return bool
     */
    public static function iomad_check_categoryid($categoryid) {
        global $CFG, $DB, $USER;

        // If we are installing this will be called to build
        // the basic category tree so just say yes.
        if (during_initial_install() || is_siteadmin($USER->id)) {
            return true;
        }

        // Try to find the category in company list.
        if (!empty($categoryid) && $company = $DB->get_record('company', ['category' => $categoryid])) {

            // If this is not the user's company then we return false.
            if ($DB->get_record('company_users', ['userid' => $USER->id, 'companyid' => $company->id])) {
                // User is not assigned to this company - hide the category.
                return true;
            } else {
                return false;
            }
        }
        // Category is visible.
        return true;
    }

    /**
     * Check if a course exists and is available to the
     * company the user belongs to..
     *
     * @param integer $checkid course id
     * @param string $name course shortname
     * @param string $idnumber course idnumber
     * @param boolean $checkhidden don't strip hidden courses
     * @return boolean
     */
    public static function iomad_check_course(
        $checkid = 0,
        $name = '',
        $idnumber = '',
        $checkhidden = false
    ) {
        global $DB, $USER;

        // If we are installing this will be called to build
        // the basic category tree so just say yes.
        if (during_initial_install() || is_siteadmin($USER->id)) {
            return true;
        }

        // Create the select SQL.
        $sqlwhere = "1 = 2";
        $sqlarray = [];
        if (!empty($checkid)) {
            $sqlwhere = "id = :courseid";
            $sqlarray['courseid'] = $checkid;
        } else if (!empty($name)) {
            $sqlwhere = $DB->sql_compare_text('shortname') .
                " = " .
                $DB->sql_compare_text(':shortname');
            $sqlarray['shortname'] = $name;
        } else if (!empty($idnumber)) {
            $sqlwhere = $DB->sql_compare_text('idnumber') .
                " = " .
                $DB->sql_compare_text(':idnumber');
            $sqlarray['idnumber'] = $idnumber;
        }

        // Does the course exist?
        if (!$course = $DB->get_record_select('course', $sqlwhere, $sqlarray)) {
            return false;
        }

        // Get the user company id.
        $companyid = self::get_my_companyid(context_system::instance());
        if ($companyid > 0) {
            $company = new company($companyid);

            // Get the list of company courses.
            $companycourses = $company->get_menu_courses(
                true,
                false,
                false,
                false,
                false,
                true,
                $checkhidden
            );

            // Check if the found courseid is in the list.
            if (!empty($companycourses[$course->id])) {

                // Course is visible.
                return true;
            }
        }

        // User can't see it.
        return false;
    }

    /**
     * Sets up a new user filter form.
     *
     * Parameters - $companyid = int;
     *
     * Returns form;
     **/
    public static function add_user_filter_form($companyid) {
        require_once('userfilterform.php');

        $mform = new user_filter_form(null, ['companyid' => $companyid]);

        return $mform;
    }

    /**
     * Add the parameters which would be passed by the user filter form
     *
     * Parameters - &$params = array;
     *              $companyid = int;
     *
     **/
    public static function add_user_filter_params(&$params, $companyid) {
        global $DB, $CFG;

        $firstname = optional_param('firstname', 0, PARAM_CLEAN);
        $lastname = optional_param('lastname', '', PARAM_CLEAN);
        $email = optional_param('email', 0, PARAM_CLEAN);
        $sort = optional_param('sort', 'name', PARAM_ALPHA);
        $dir = optional_param('dir', 'ASC', PARAM_ALPHA);
        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 30, PARAM_INT);
        $search = optional_param('search', '', PARAM_CLEAN);
        $departmentid = optional_param('departmentid', 0, PARAM_INTEGER);
        $compfrom = optional_param_array('compfromraw', null, PARAM_INT);
        $compto = optional_param_array('comptoraw', null, PARAM_INT);
        $loginfrom = optional_param_array('loginfromraw', null, PARAM_INT);
        $loginto = optional_param_array('logintoraw', null, PARAM_INT);
        $emailfrom = optional_param_array('emailfromraw', null, PARAM_INT);
        $emailto = optional_param_array('emailtoraw', null, PARAM_INT);
        $licenseuseage = optional_param('licenseusage', 0, PARAM_INT);
        $licenseallocatedfrom = optional_param_array('licenseallocatedfromraw', null, PARAM_INT);
        $licenseallocatedto = optional_param_array('licenseallocatedtoraw', null, PARAM_INT);
        $licenseunallocatedfrom = optional_param_array('licenseunallocatedfromraw', null, PARAM_INT);
        $licenseunallocatedto = optional_param_array('licenseunallocatedtoraw', null, PARAM_INT);

        // Process the params.
        $paramlist = [
            'firstname',
            'lastname',
            'email',
            'search',
            'compfrom',
            'compto',
            'licenseusage',
            'licenseallocatedfrom',
            'licenseallocatedto',
            'licenseunallocatedfrom',
            'licenseunallocatedto',
        ];
        // Get the company additional optional user parameter names.
        $fieldnames = [];
        $idlist = [];
        $foundfields = false;

        if ($companyinfo = $DB->get_record('company', ['id' => $companyid])) {
            // Get field names from company category.
            if ($fields = $DB->get_records('user_info_field', ['categoryid' => $companyinfo->profileid])) {
                foreach ($fields as $field) {
                    $fieldnames[$field->id] = 'profile_field_' . $field->shortname;
                    ${'profile_field_' . $field->shortname} = optional_param('profile_field_' . $field->shortname, null, PARAM_RAW);
                }
            }
        }

        // Get the global optional user parameter names.
        if ($globalfields = $DB->get_records_select(
            'user_info_field',
            "categoryid NOT IN (
                 SELECT profileid FROM {company}
             )")) {
            foreach ($globalfields as $field) {
                if ($field->shortname != 'company') {
                    if ($field->shortname == 'VANTAGE') {
                        $vantagefieldid = $field->id;
                    }
                    $fieldnames[$field->id] = 'profile_field_' . $field->shortname;
                    ${'profile_field_' . $field->shortname} = optional_param('profile_field_' . $field->shortname, null, PARAM_RAW);
                }
            }
            $fields = $fields + $globalfields;
        }

        // Deal with the user optional profile search.
        if (!empty($fieldnames)) {
            $fieldids = [];
            foreach ($fieldnames as $id => $fieldname) {
                $paramarray = [];
                if ($fields[$id]->datatype == "menu") {
                    $paramarray = explode("\n", $fields[$id]->param1);
                    if (${$fieldname} == "-1") {
                        // Ignore this and continue.
                        continue;
                    }
                    if (!empty($paramarray[${$fieldname}])) {
                        ${$fieldname} = $paramarray[${$fieldname}];
                    }
                }
                if (!empty(${$fieldname})) {
                    $idlist[0] = "We found no one";
                    $fieldsql = $DB->sql_like('data', ':fieldname') . " AND fieldid = :fieldid";
                    if ($idfields = $DB->get_records_select(
                        'user_info_data',
                        $fieldsql,
                        ['fieldname' => '%' . ${$fieldname} . '%',
                         'fieldid' => $id], '', "userid")) {
                        $fieldids[] = $idfields;
                    }
                    if (!empty($paramarray)) {
                        $params[$fieldname] = array_search(${$fieldname}, $paramarray);
                    } else {
                        $params[$fieldname] = ${$fieldname};
                    }
                }
            }
            if (!empty($fieldids)) {
                $foundfields = true;
                $idlist = array_pop($fieldids);
                if (!empty($fieldids)) {
                    foreach ($fieldids as $fieldid) {
                        $idlist = array_intersect_key($idlist, $fieldid);
                        if (empty($idlist)) {
                            break;
                        }
                    }
                }
            }
        }
        $returnobj = (object) [];
        $returnobj->foundfields = $foundfields;
        $returnobj->idlist = $idlist;

        return $returnobj;
    }

    /**
     * Get a list of users provided a list of parameters
     *
     * Parameters - &$params = [];
     *              $idlist = [];
     *              $sort = text;
     *              $dir = text;
     *              $departmentid = int;
     *
     * Return [];
     **/
    public static function get_user_sqlsearch($params,
                                              $idlist = '',
                                              $sort = "",
                                              $dir = "ASC",
                                              $departmentid = 0,
                                              $nogrades = false,
                                              $allcourse = false) {
        global $DB, $CFG;

        if ($allcourse) {
            $sqlsort = " GROUP BY cc.id, co.id, u.id, d.name";
        } else {
            $sqlsort = " GROUP BY cc.id, u.id, cc.timestarted, cc.timecompleted, d.name";
        }
        if (!$nogrades) {
            $sqlsort .= ', cc.finalscore';
        }
        $sqlsearch = "u.id != '-1' and u.deleted = 0";
        $sqlsearch .= " AND u.id NOT IN (" . $CFG->siteadmins . ")";

        // Deal with suspended users.
        if (empty($params['showsuspended'])) {
            $sqlsearch .= " AND u.suspended = 0";
        }

        $returnobj = (object) [];

        // Deal with search strings.
        $searchparams = [];
        if (!empty($idlist)) {
            [$insql, $searchparams] = $DB->get_in_or_equal(array_keys($idlist),
                                                           SQL_PARAMS_NAMED,
                                                           'uids');

            $sqlsearch .= " AND u.id {$insql}";
        }
        if (!empty($params['firstname'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.firstname', ':firstname');
            $searchparams['firstname'] = '%' . $params['firstname'] . '%';
        }

        if (!empty($params['lastname'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.lastname', ':lastname');
            $searchparams['lastname'] = '%' . $params['lastname'] . '%';
        }

        if (!empty($params['email'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.email', ':email');
            $searchparams['email'] = '%' . $params['email'] . '%';
        }
        if (!empty($params['compfrom'])) {
            $params['courseid2'] = $params['courseid'];
            if ($compfromids = $DB->get_records_select(
                'local_iomad_track',
                "(
                     courseid = :courseid
                     AND timecompleted < :compfrom
                     AND timecompleted IS NOT NULL
                 ) OR (
                     courseid = :courseid2
                     AND timecompleted IS NULL
                 )",
                $params)) {
                [$notinsql, $notinparams] = $DB->get_in_or_equal(array_keys($compfromids),
                                                                 SQL_PARAMS_NAMED,
                                                                 'cflitids',
                                                                 false);

                $sqlsearch .= " AND lit.id {$notinsql} ";
                $searchparams = $searchparams + $notinparams;
            }
        }

        if (!empty($params['compto'])) {
            if ($comptoids = $DB->get_records_select(
                'local_iomad_track',
                "courseid = :courseid
                 AND timecompleted > :compto",
                $params,
                '',
                "id")) {
                [$notinsql, $notinparams] = $DB->get_in_or_equal(array_keys($comptoids),
                                                                 SQL_PARAMS_NAMED,
                                                                 'ctlitids',
                                                                 false);

                $sqlsearch .= " AND lit.id {$notinsql} ";
                $searchparams = $searchparams + $notinparams;
            }
        }

        if (!empty($params['emailfrom'])) {
            $sqlsearch .= " AND e.sent > :emailfrom ";
            $searchparams['emailfrom'] = $params['emailfrom'];
        }

        if (!empty($params['emailto'])) {
            $sqlsearch .= " AND e.sent < :emailto ";
            $searchparams['emailto'] = $params['emailto'];
        }

        if (!empty($params['loginfrom'])) {
            $sqlsearch .= " AND url.lastlogin > :loginfrom ";
            $searchparams['loginfrom'] = $params['loginfrom'];
        }

        if (!empty($params['loginto'])) {
            $sqlsearch .= " AND url.lastlogin < :loginto ";
            $searchparams['loginto'] = $params['loginto'];
        }

        if (!empty($params['licenseallocatedfrom'])) {
            if ($licallocfromids = $DB->get_records_select(
                'local_report_user_lic_allocs',
                "issuedate > :licenseallocatedfrom
                 AND action = 1",
                $params)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($licallocfromids),
                                                           SQL_PARAMS_NAMED,
                                                           'lafids');

                $sqlsearch .= " AND urla.id {$insql} ";
                $searchparams = $searchparams + $inparams;
            } else {
                $sqlsearch .= " AND 1 = 2 ";
            }
        }

        if (!empty($params['licenseallocatedto'])) {
            if ($licalloctoids = $DB->get_records_select(
                'local_report_user_lic_allocs',
                "issuedate < :licenseallocatedto
                 AND action = 1",
                $params)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($licalloctoids),
                                                           SQL_PARAMS_NAMED,
                                                           'latids');

                $sqlsearch .= " AND urla.id {$insql} ";
                $searchparams = $searchparams + $inparams;
            } else {
                $sqlsearch .= " AND 1 = 2 ";
            }
        }

        if (!empty($params['licenseunallocatedfrom'])) {
            if ($licunallocfromids = $DB->get_records_select(
                'local_report_user_lic_allocs',
                "issuedate > :licenseunallocatedfrom
                 AND action = 0",
                $params)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($licunallocfromids),
                                                           SQL_PARAMS_NAMED,
                                                           'lufids');

                $sqlsearch .= " AND urla.id {$insql} ";
                $searchparams = $searchparams + $inparams;
            } else {
                $sqlsearch .= " AND 1 = 2 ";
            }
        }

        if (!empty($params['licenseunallocatedto'])) {
            if ($licunalloctoids = $DB->get_records_select(
                'local_report_user_lic_allocs',
                "issuedate < :licenseunallocatedto
                 AND action = 0",
                $params)) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($licunalloctoids),
                                                           SQL_PARAMS_NAMED,
                                                           'lutids');

                $sqlsearch .= " AND urla.id {$insql} ";
                $searchparams = $searchparams + $inparams;
            } else {
                $sqlsearch .= " AND 1 = 2 ";
            }
        }

        if (!empty($params['licenseusage'])) {
            $params['licenseusage']--;
            if ($licunalloctoids = $DB->get_records_select(
                'local_report_user_lic_allocs',
                "action = :licenseusage",
                $params,
                '',
                'id')) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($licunalloctoids),
                                                           SQL_PARAMS_NAMED,
                                                           'luids');

                $sqlsearch .= " AND urla.id {$insql} ";
                $searchparams = $searchparams + $inparams;
            } else {
                $sqlsearch .= " AND 1 = 2 ";
            }
        }

        // Deal with how we sort the data.
        switch ($sort) {
            case "firstname":
                $sqlsort .= " ORDER BY u.firstname $dir ";
                break;
            case "lastname":
                $sqlsort .= " ORDER BY u.lastname $dir ";
                break;
            case "email":
                $sqlsort .= " ORDER BY u.email $dir ";
                break;
            case "timecreated":
                $sqlsort .= " ORDER BY u.timecreated $dir ";
                break;
            case "timeenrolled":
                $sqlsort .= " ORDER BY cc.timeenrolled $dir ";
                break;
            case "timestarted":
                $sqlsort .= " ORDER BY cc.timestarted $dir ";
                break;
            case "timecompleted":
                $sqlsort .= " ORDER BY cc.timecompleted $dir ";
                break;
            case "department":
                $sqlsort .= " ORDER BY d.name $dir ";
                break;
            case "vantage":
                $sqlsort .= " ORDER BY uid.data $dir ";
                break;
            case "finalscore":
                $sqlsort .= " ORDER BY gg.finalgrade $dir ";
                break;
            default:
                if ($allcourse) {
                    $sqlsort .= " ORDER BY co.id $dir ";
                }
                break;
        }

        $returnobj->sqlsearch = $sqlsearch;
        $returnobj->sqlsort = $sqlsort;
        $returnobj->searchparams = $searchparams;
        $returnobj->departmentid = $departmentid;
        return $returnobj;
    }

    /**
     * Get completion summary info for a course
     *
     * Parameters - $departmentid = int;
     *              $courseid = int;
     *
     * Return [];
     **/
    public static function get_course_summary_info($departmentid, $courseid = 0, $showsuspended = 1) {
        global $DB;

        // Create a temporary table to hold the userids.
        $temptablename = 'tmp_' . uniqid();
        $dbman = $DB->get_manager();

        // Define table user to be created.
        $table = new xmldb_table($temptablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_temp_table($table);

        // Populate it.
        $alldepartments = company::get_all_subdepartments($departmentid);
        if (count($alldepartments) > 0) {
            // Deal with suspended or not.
            if (empty($showsuspended)) {
                $suspendedsql = " AND suspended = 0 ";
            } else {
                $suspendedsql = "";
            }
            $tempcreatesql = "INSERT INTO {" . $temptablename . "} (userid) SELECT userid from {company_users}
                              WHERE departmentid IN (" . implode(',', array_keys($alldepartments)) . ") $suspendedsql";
        } else {
            $tempcreatesql = "";
        }
        $DB->execute($tempcreatesql);

        // All or one course?
        $courses = [];
        if (!empty($courseid)) {
            $courses[$courseid] = (object) [];
            $courses[$courseid]->id = $courseid;
        } else {
            $courses = company::get_recursive_department_courses($departmentid);
        }

        // Process them!
        $returnarr = [];
        foreach ($courses as $course) {
            $courseobj = (object) [];
            $courseobj->id = $course->courseid;
            $courseobj->numenrolled = $DB->count_records_sql("SELECT COUNT(cc.id) FROM {course_completions} cc
                                                   JOIN {" . $temptablename . "} tt ON (cc.userid = tt.userid)
                                                   WHERE
                                                   cc.course = :course",
                                                   ['course' => $course->courseid]);
            $courseobj->numnotstarted = $DB->count_records_sql("SELECT COUNT(cc.id) FROM {course_completions} cc
                                                   JOIN {" . $temptablename . "} tt ON (cc.userid = tt.userid)
                                                   WHERE
                                                   cc.course = :course AND
                                                   cc.timestarted = 0",
                                                   ['course' => $course->courseid]);
            $courseobj->numstarted = $DB->count_records_sql("SELECT COUNT(cc.id) FROM {course_completions} cc
                                                   JOIN {" . $temptablename . "} tt ON (cc.userid = tt.userid)
                                                   WHERE
                                                   cc.course = :course AND
                                                   cc.timestarted != 0",
                                                   ['course' => $course->courseid]);
            $courseobj->numcompleted = $DB->count_records_sql("SELECT COUNT(cc.id) FROM {course_completions} cc
                                                   JOIN {" . $temptablename . "} tt ON (cc.userid = tt.userid)
                                                   WHERE
                                                   cc.course = :course AND
                                                   cc.timecompleted IS NOT NULL",
                                                   ['course' => $course->courseid]);

            if (!$courseobj->coursename = $DB->get_field('course', 'fullname', ['id' => $course->courseid])) {
                continue;
            }
            $returnarr[$course->courseid] = $courseobj;
        }
        return $returnarr;
    }

    /**
     * Get users into temporary table
     */
    private static function populate_temporary_users($temptablename, $searchinfo) {
        global $DB;

        // Create a temporary table to hold the userids.
        $dbman = $DB->get_manager();

        // Define table user to be created.
        $table = new xmldb_table($temptablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_temp_table($table);

        // Populate it.
        $alldepartments = company::get_all_subdepartments($searchinfo->departmentid);
        if (count($alldepartments) > 0) {
            $tempcreatesql = "INSERT INTO {" . $temptablename . "} (userid) SELECT userid from {company_users}
                              WHERE departmentid IN (" . implode(',', array_keys($alldepartments)) . ")";
        } else {
            $tempcreatesql = "";
        }
        $DB->execute($tempcreatesql);

        return [$dbman, $table];
    }

    /**
     * Get user completion info for a course
     *
     * Parameters - $departmentid = int;
     *              $courseid = int;
     *              $page = int;
     *              $perpade = int;
     *
     * Return [];
     **/
    public static function get_user_course_completion_data($searchinfo, $courseid, $page = 0, $perpage = 0, $completiontype = 0) {
        global $DB;

        $completiondata = (object) [];

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        $temptablename = 'tmp_' . uniqid();
        list($dbman, $table) = self::populate_temporary_users($temptablename, $searchinfo);

        // Deal with completion types.
        if (!empty($completiontype)) {
            if ($completiontype == 1) {
                $completionsql = " AND cc.timeenrolled > 0 AND cc.timestarted = 0 ";
            } else if ($completiontype == 2) {
                $completionsql = " AND cc.timestarted > 0 AND cc.timecompleted IS NULL ";
            } else if ($completiontype == 3) {
                $completionsql = " AND cc.timecompleted IS NOT NULL  ";
            }
        } else {
            $completionsql = "";
        }

        // Get the user details.
        $shortname = addslashes($course->shortname);
        $countsql = "SELECT u.id ";
        $selectsql = "SELECT u.id,
                u.id as uid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                u.timecreated AS timecreated,
                '{$shortname}' AS coursename,
                '$courseid' AS courseid,
                cc.timeenrolled AS timeenrolled,
                cc.timestarted AS timestarted,
                cc.timecompleted AS timecompleted,
                d.name as department,
                gg.finalgrade as result ";
        $fromsql = " FROM {user} u, {course_completions} cc, {department} d, {company_users} du, {" . $temptablename . "} tt
                     LEFT JOIN {grade_grades} gg ON ( gg.itemid = (
                       SELECT id FROM {grade_items} WHERE courseid = $courseid AND itemtype='course'))

                WHERE $searchinfo->sqlsearch
                AND tt.userid = u.id
                AND cc.course = $courseid
                AND u.id = cc.userid
                AND du.userid = u.id
                AND d.id = du.departmentid
                AND gg.userid = u.id
                $completionsql
                $searchinfo->sqlsort ";

        $searchinfo->searchparams['courseid'] = $courseid;
        $users = $DB->get_records_sql($selectsql . $fromsql, $searchinfo->searchparams, $page * $perpage, $perpage);
        $countusers = $DB->get_records_sql($countsql . $fromsql, $searchinfo->searchparams);
        $numusers = count($countusers);

        $returnobj = (object) [];
        $returnobj->users = $users;
        $returnobj->totalcount = $numusers;

        $dbman->drop_table($table);

        return $returnobj;
    }

    /**
     * Get all users completion info regardless of course
     *
     * Parameters - $departmentid = int;
     *              $page = int;
     *              $perpade = int;
     *
     * Return [];
     **/
    public static function get_all_user_course_completion_data($searchinfo, $page = 0, $perpage = 0, $completiontype = 0) {
        global $DB;

        $completiondata = (object) [];

        // Create a temporary table to hold the userids.
        $temptablename = 'tmp_' . uniqid();
        list($dbman, $table) = self::populate_temporary_users($temptablename, $searchinfo);

        // Deal with completion types.
        if (!empty($completiontype)) {
            if ($completiontype == 1) {
                $completionsql = " AND cc.timeenrolled > 0 AND cc.timestarted = 0 ";
            } else if ($completiontype == 2) {
                $completionsql = " AND cc.timestarted > 0 AND cc.timecompleted IS NULL ";
            } else if ($completiontype == 3) {
                $completionsql = " AND cc.timecompleted IS NOT NULL  ";
            }
        } else {
            $completionsql = "";
        }

        // Get the user details.
        $countsql = "SELECT " . $DB->sql_concat('co.id', 'u.id') . "AS id ";
        $selectsql = "
                SELECT " .
            $DB->sql_concat('co.id', 'u.id') . " AS id,
                u.id AS uid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                u.timecreated AS timecreated,
                co.shortname AS coursename,
                co.id AS courseid,
                cc.timeenrolled AS timeenrolled,
                cc.timestarted AS timestarted,
                cc.timecompleted AS timecompleted,
                d.name as department,
                '0' as result ";
        $fromsql = " FROM {user} u
                    JOIN {course_completions} cc ON (u.id = cc.userid)
                    JOIN {company_users} du ON (u.id = du.userid AND cc.userid = du.userid)
                    JOIN {department} d ON (cu.departmentid = d.id)
                    JOIN {" . $temptablename . "} tt ON (
                        u.id = tt.userid
                        AND cc.userid = tt.userid
                        AND cu.userid = tt.userid
                    )
                    JOIN {course} co ON (cc.courseid = co.id)

                WHERE $searchinfo->sqlsearch
                $completionsql
                $searchinfo->sqlsort ";

        $users = $DB->get_records_sql($selectsql . $fromsql, $searchinfo->searchparams, $page * $perpage, $perpage);
        $countusers = $DB->get_records_sql($countsql . $fromsql, $searchinfo->searchparams);
        $numusers = count($countusers);
        foreach ($users as $id => $user) {
            $gradeitem = $DB->get_record('grade_items', ['itemtype' => 'course', 'courseid' => $user->courseid]);
            $grade = $DB->get_record('grade_grades', ['itemid' => $gradeitem->id, 'userid' => $user->uid]);
            if ($grade) {
                $user->result = $grade->finalgrade;
            }
        }

        $returnobj = (object) [];
        $returnobj->users = $users;
        $returnobj->totalcount = $numusers;

        $dbman->drop_table($table);

        return $returnobj;
    }

    /**
     * Get a list of users provided a list of parameters
     *
     * Parameters - &$params = [];
     *              $idlist = [];
     *              $sort = text;
     *              $dir = text;
     *              $departmentid = int;
     *
     * Return [];
     **/
    public static function get_user_license_sqlsearch($params,
                                                      $idlist = '',
                                                      $sort = 'lastname',
                                                      $dir = 'ASC',
                                                      $departmentid = null,
                                                      $licenses = false) {
        global $DB, $CFG;

        if (!empty($params['courseid']) && $params['courseid'] == 1) {
            if (!$licenses) {
                $sqlsort = " GROUP BY co.id, cl.name, d.name, u.id";
            } else {
                $sqlsort = " GROUP BY co.id, cl.name, d.name, u.id, clu.id";
            }
        } else {
            if (!$licenses) {
                $sqlsort = " GROUP BY cl.name, d.name, u.id";
            } else {
                $sqlsort = " GROUP BY cl.name, d.name, u.id, clu.id";
            }
        }
        $sqlsearch = "u.id != '-1' and u.deleted = 0";
        $sqlsearch .= " AND u.id NOT IN (" . $CFG->siteadmins . ")";

        // Deal with suspended users.
        if (empty($params['showsuspended'])) {
            $sqlsearch .= " AND u.suspended = 0";
        }

        $returnobj = (object) [];

        // Deal with search strings.
        $searchparams = [];
        if (!empty($idlist)) {
            [$insql, $searchparams] = $DB->get_in_or_equal(array_keys($idlist),
                                                           SQL_PARAMS_NAMED,
                                                           'uids');
            $sqlsearch .= " AND u.id $insql ";
        }
        if (!empty($params['firstname'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.firstname', ':firstname');
            $searchparams['firstname'] = '%' . $params['firstname'] . '%';
        }

        if (!empty($params['lastname'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.lastname', ':lastname');
            $searchparams['lastname'] = '%' . $params['lastname'] . '%';
        }

        if (!empty($params['email'])) {
            $sqlsearch .= " AND " . $DB->sql_like('u.email', ':email');
            $searchparams['email'] = '%' . $params['email'] . '%';
        }

        // Deal with how we sort the data.
        switch ($sort) {
            case "firstname":
                $sqlsort .= " ORDER BY u.firstname $dir ";
                break;
            case "lastname":
                $sqlsort .= " ORDER BY u.lastname $dir ";
                break;
            case "email":
                $sqlsort .= " ORDER BY u.email $dir ";
                break;
            case "licensename":
                $sqlsort .= " ORDER BY cl.name $dir ";
                break;
            case "isusing":
                $sqlsort .= " ORDER BY clu.isusing $dir ";
                break;
            case "department":
                $sqlsort .= " ORDER BY d.name $dir ";
                break;
        }

        $returnobj->sqlsearch = $sqlsearch;
        $returnobj->sqlsort = $sqlsort;
        $returnobj->searchparams = $searchparams;
        $returnobj->departmentid = $departmentid;
        return $returnobj;
    }

    /**
     * Get license summary info for a course
     *
     * Parameters - $departmentid = int;
     *              $courseid = int;
     *
     * Return [];
     **/
    public static function get_course_license_summary_info($departmentid, $courseid = 0, $showsuspended = false) {
        global $DB;

        // Create a temporary table to hold the userids.
        $temptablename = 'tmp_' . uniqid();
        $dbman = $DB->get_manager();

        // Define table user to be created.
        $table = new xmldb_table($temptablename);
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        $dbman->create_temp_table($table);

        // Populate it.
        $alldepartments = company::get_all_subdepartments($departmentid);
        if (count($alldepartments) > 0) {
            // Deal with suspended or not.
            if (empty($showsuspended)) {
                $suspendedsql = " AND suspended = 0 ";
            } else {
                $suspendedsql = "";
            }
            $tempcreatesql = "INSERT INTO {" . $temptablename . "} (userid) SELECT userid from {company_users}
                              WHERE departmentid IN (" . implode(',', array_keys($alldepartments)) . ") $suspendedsql";
        } else {
            $tempcreatesql = "";
        }
        $DB->execute($tempcreatesql);

        // All or one course?
        $courses = [];
        if (!empty($courseid)) {
            $courses[$courseid] = (object) [];
            $courses[$courseid]->id = $courseid;
        } else {
            $courses = company::get_recursive_department_courses($departmentid);
        }

        // Process them!
        $returnarr = [];
        foreach ($courses as $course) {
            $courseobj = (object) [];
            $courseobj->id = $course->courseid;
            $timestamp = time();
            $courseobj->numlicenses = $DB->count_records_sql("SELECT COUNT(clu.id) FROM {companylicense_users} clu
                                                   JOIN {" . $temptablename . "} tt ON (clu.userid = tt.userid)
                                                   JOIN {companylicense} cl ON (cl.id = clu.licenseid)
                                                   WHERE
                                                   clu.licensecourseid = :courseid
                                                   AND cl.expirydate > :timestamp",
            [
                'courseid' => $course->courseid,
                'timestamp' => $timestamp,
            ]);
            $courseobj->numused = $DB->count_records_sql("SELECT COUNT(clu.id) FROM {companylicense_users} clu
                                                   JOIN {" . $temptablename . "} tt ON (clu.userid = tt.userid)
                                                   JOIN {companylicense} cl ON (cl.id = clu.licenseid)
                                                   WHERE
                                                   clu.licensecourseid = :courseid
                                                   AND cl.expirydate > :timestamp
                                                   AND
                                                   clu.isusing = 1",
            [
                'courseid' => $course->courseid,
                'timestamp' => $timestamp,
            ]);
            $courseobj->numunused = $courseobj->numlicenses - $courseobj->numused;

            if (!$courseobj->coursename = $DB->get_field('course', 'fullname', ['id' => $course->courseid])) {
                continue;
            }
            $returnarr[$course->courseid] = $courseobj;
        }
        return $returnarr;
    }

    /**
     * Get all users completion info regardless of course
     *
     * Parameters - $departmentid = int;
     *              $page = int;
     *              $perpade = int;
     *
     * Return [];
     **/
    public static function get_all_user_course_license_data($searchinfo,
                                                            $page = 0,
                                                            $perpage = 0,
                                                            $completiontype = 0,
                                                            $showsuspended = false,
                                                            $showused = false) {
        global $DB;

        $completiondata = (object) [];

        // Create a temporary table to hold the userids.
        $temptablename = 'tmp_' . uniqid();
        list($dbman, $table) = self::populate_temporary_users($temptablename, $searchinfo);

        // Deal with completion types.
        if (!empty($completiontype)) {
            if ($completiontype == 1) {
                $completionsql = " AND cc.timeenrolled > 0 AND cc.timestarted = 0 ";
            } else if ($completiontype == 2) {
                $completionsql = " AND cc.timestarted > 0 AND cc.timecompleted IS NULL ";
            } else if ($completiontype == 3) {
                $completionsql = " AND cc.timecompleted IS NOT NULL  ";
            }
        } else {
            $completionsql = "";
        }

        if (!$showsuspended) {
            $showsuspendedsql = "AND u.suspended = 0";
        } else {
            $showsuspendedsql = "";
        }

        if (!$showused) {
            $showusedsql = "AND clu.isusing = 0";
        } else {
            $showusedsql = "";
        }

        // Get the user details.
        $countsql = "SELECT clu.id AS id ";
        $selectsql = "
                SELECT
                clu.id AS id,
                u.id AS uid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                u.currentlogin AS lastaccess,
                co.shortname AS coursename,
                co.id AS courseid,
                cl.id AS licenseid,
                cl.name AS licensename,
                d.name as department,
                cl.name,
                clu.isusing,
				clu.issuedate,
                '0' as result ";
        $fromsql = " FROM {user} u,
                     JOIN {companylicense_users} clu ON (u.id = clu.userid)
                     JOIN {company_users} cu ON (u.id = cu.userid AND clu.userid = cu.userid)
                     JOIN {department} d ON (cu.departmentid = d.it)
                     JOIN {" . $temptablename . "} tt ON (u.id = tt.userid)
                     JOIN {course} co ON (clu.licensecourseid = co.id)
                     JOIN {companylicense} cl ON (clu.licenseid = cl.id)

                WHERE $searchinfo->sqlsearch
                AND cl.expirydate > :timestamp
                $showusedsql
                $showsuspendedsql
                $completionsql
                $searchinfo->sqlsort ";
        $searchinfo->searchparams['timestamp'] = time();
        $users = $DB->get_records_sql($selectsql . $fromsql, $searchinfo->searchparams, $page * $perpage, $perpage);
        $countusers = $DB->get_records_sql($countsql . $fromsql, $searchinfo->searchparams);
        $numusers = count($countusers);

        $returnobj = (object) [];
        $returnobj->users = $users;
        $returnobj->totalcount = $numusers;

        $dbman->drop_table($table);

        return $returnobj;
    }

    /**
     * Get a list of companies
     *
     * @param string $sort
     * @param string $dir
     * @param integer $page
     * @param integer $recordsperpage
     * @param string $search
     * @param string $firstinitial
     * @param string $lastinitial
     * @param string $extraselect
     * @param array|null $extraparams
     * @return array
     */
    public static function get_companies_listing(
        $sort = 'name',
        $dir = 'ASC',
        $page = 0,
        $recordsperpage = 0,
        $search = '',
        $firstinitial = '',
        $lastinitial = '',
        $extraselect = '',
        array $extraparams = []
    ) {
        global $DB;

        $params = [];

        if (!empty($search)) {
            $search = trim($search);
            $select .= " AND (" . $DB->sql_like("name", ':search1', false, false) .
                " OR " . $DB->sql_like('city', ':search2', false, false) .
                " OR country = :search3)";
            $params['search1'] = "%$search%";
            $params['search2'] = "%$search%";
            $params['search3'] = "$search";
        }

        if ($extraselect) {
            $select = $extraselect;
            $params = $params + (array)$extraparams;
        }

        if ($sort) {
            $sort = " ORDER BY $sort $dir";
        }

        // Warning: will return UNCONFIRMED USERS!
        return $DB->get_records_sql(
            "SELECT *, 0 as depth
                                     FROM {company}
                                     WHERE $select $sort",
            $params,
            $page,
            $recordsperpage
        );
    }

    /**
     * Get user completion info for a course
     *
     * Parameters - $departmentid = int;
     *              $courseid = int;
     *              $page = int;
     *              $perpade = int;
     *
     * Return [];
     **/
    public static function get_user_course_license_data($searchinfo,
                                                        $courseid,
                                                        $page = 0,
                                                        $perpage = 0,
                                                        $completiontype = 0,
                                                        $showsuspended = false,
                                                        $showused = false) {
        global $DB;

        $completiondata = (object) [];

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        $temptablename = 'tmp_' . uniqid();
        list($dbman, $table) = self::populate_temporary_users($temptablename, $searchinfo);

        if (!$showsuspended) {
            $showsuspendedsql = "AND u.suspended = 0";
        } else {
            $showsuspendedsql = "";
        }

        if (!$showused) {
            $showusedsql = "AND clu.isusing = 0";
        } else {
            $showusedsql = "";
        }

        // Get the user details.
        $shortname = addslashes($course->shortname);
        $countsql = "SELECT " . $DB->sql_concat('clu.id', 'u.id', 'clu.isusing') . " AS id";
        $selectsql = "SELECT " .
            $DB->sql_concat('clu.id', 'u.id') . " AS id,
                u.id AS uid,
                u.firstname AS firstname,
                u.lastname AS lastname,
                u.email AS email,
                u.currentlogin AS lastaccess,
                '{$shortname}' AS coursename,
                '$courseid' AS courseid,
                clu.licenseid AS licenseid,
                clu.isusing AS isusing,
				clu.issuedate AS issuedate,
                d.name AS department,
                cl.name AS licensename ";
        $fromsql = " FROM {user} u
                    JOIN {companylicense_users} clu ON (u.id = clu.userid)
                    JOIN {comany_users} cu ON (u.id = cu.userid AND clu.userid = cu.userid)
                    JOIN {department} d ON (clu.departmentid = d.id)
                    JOIN {" . $temptablename . "} tt ON (tt.userid = u.id)
                    JOIN {companylicense} cl ON (clu.licenseid = cl.id)

                    WHERE $searchinfo->sqlsearch
                    AND clu.licensecourseid = $courseid
                    AND cl.expirydate > :timestamp
                    $showsuspendedsql
                    $showusedsql
                    $searchinfo->sqlsort ";

        $searchinfo->searchparams['courseid'] = $courseid;
        $searchinfo->searchparams['timestamp'] = time();
        $users = $DB->get_records_sql($selectsql . $fromsql, $searchinfo->searchparams, $page * $perpage, $perpage);
        $countusers = $DB->get_records_sql($countsql . $fromsql, $searchinfo->searchparams);
        $numusers = count($countusers);

        $returnobj = (object) [];
        $returnobj->users = $users;
        $returnobj->totalcount = $numusers;

        $dbman->drop_table($table);

        return $returnobj;
    }

    /**
     * Copied from similarly named function in accesslib.php
     * modified to check iomad restrictions database.
     * @param unknown $capability
     * @param context $context
     * @param array $accessdata
     * @return boolean
     */
    private static function has_capability_in_accessdata($companyid, $capability, context $context, array &$accessdata) {
        global $CFG, $DB;

        // Build $paths as a list of current + all parent "paths" with order bottom-to-top.
        $path = $context->path;
        $paths = [$path];
        while ($path = rtrim($path, '0123456789')) {
            $path = rtrim($path, '/');
            if ($path === '') {
                break;
            }
            $paths[] = $path;
        }

        $roles = [];
        $switchedrole = false;

        // Find out if role switched.
        if (!empty($accessdata['rsw'])) {
            // From the bottom up...
            foreach ($paths as $path) {
                if (isset($accessdata['rsw'][$path])) {
                    // Found a switchrole assignment - check for that role _plus_ the default user role.
                    $roles = [$accessdata['rsw'][$path] => null, $CFG->defaultuserroleid => null];
                    $switchedrole = true;
                    break;
                }
            }
        }

        if (!$switchedrole) {
            // Get all users roles in this context and above.
            foreach ($paths as $path) {
                if (isset($accessdata['ra'][$path])) {
                    foreach ($accessdata['ra'][$path] as $roleid) {
                        $roles[$roleid] = null;
                    }
                }
            }
        }

        // Now find out what access is given to each role, going bottom-->up direction.
        $rdefs = get_role_definitions(array_keys($roles));
        $allowed = false;

        foreach ($roles as $roleid => $ignored) {
            foreach ($paths as $path) {
                if (isset($rdefs[$roleid][$path][$capability])) {
                    $perm = (int)$rdefs[$roleid][$path][$capability];
                    if ($perm === CAP_PROHIBIT) {
                        // Any CAP_PROHIBIT found means no permission for the user.
                        return false;
                    }
                    if (is_null($roles[$roleid])) {
                        $roles[$roleid] = $perm;
                    }
                }
            }
            // CAP_ALLOW in any role means the user has a permission, we continue only to detect prohibits.
            $restriction = $DB->get_record('company_role_restriction',
            [
                'companyid' => $companyid,
                'roleid' => $roleid,
                'capability' => $capability,
            ]);
            if ($restriction) {
                return false;
            }
            $allowed = ($allowed || $roles[$roleid] === CAP_ALLOW);
        }

        return $allowed;
    }

    /**
     * IOMAD version
     * @param unknown $capability
     * @param context $context
     * @param int $companyid (optional) check for different company (and right to access same).
     * @return bool
     */
    public static function has_capability($capability, context $context, $companyid = 0) {
        global $USER, $DB;

        // If original version says no then it's no.
        // (We also rely on this doing a bunch of sanity checks, so we don't have to).
        if (!has_capability($capability, $context)) {
            return false;
        }

        // If this is the admin then we'll believe it.
        if (is_siteadmin()) {
            return true;
        }

        // If companyid supplied then check the user is a member.
        if ($companyid) {
            if (!$DB->record_exists('company_users', ['companyid' => $companyid, 'userid' => $USER->id])) {
                return false;
            }
        } else {

            // Get user's current company. If no company then it must be true.
            if (!$companyid = self::companyid()) {
                return true;
            }
        }

        // Probably need to get accessdata (again), so...
        if (!isset($USER->access)) {
            load_all_capabilities();
        }
        $access = &$USER->access;

        return self::has_capability_in_accessdata($companyid, $capability, $context, $access);
    }

    /**
     * Iomad version of require_capability
     * @param unknown $capability
     * @param context $context
     * @param int $companyid (optional) check for different company (and right to access same).
     * @throws required_capability_exception
     */
    public static function require_capability($capability, context $context, $companyid = 0) {
        if (!self::has_capability($capability, $context, $companyid)) {
            throw new required_capability_exception($context, $capability, 'nopermissions', 'local_iomad');
        }
    }

    /**
     * Get IOMAD documentation link.
     */
    public static function documentation_link() {
        return 'http://docs.iomad.org/wiki/';
    }

    /**
     * Redirect on company URL matching
     *
     */
    public static function check_redirect($wwwroot, $rurl) {
        global $CFG, $DB;

        // If we are installing then do nothing.
        if (during_initial_install()) {
            return true;
        }

        // Otherwise we redirect when the URL doesn't match the company URL.
        if ($rurl['host'] != $wwwroot['host']) {
            if ($companyrec = $DB->get_record('company', ['hostname' => $rurl['host']])) {
                $redirecturl = new moodle_url(
                    $CFG->wwwroot . '/login/index.php',
                    [
                        'id' => $companyrec->id,
                        'code' => $companyrec->shortname,
                    ]
                );
                redirect($redirecturl);
            }
        }
    }

    /**
     * Fix the passed URL to use the tenant one.
     **/
    public static function fix_url($url) {
        global $CFG;

        $myurlarray = parse_url($CFG->wwwroot);
        $urlarray = parse_url($url);

        // Make the passed hostname my hostname.
        $urlarray['host'] = $myurlarray['host'];

        return self::unparse_url($urlarray);
    }

    /**
     * Unparse a URL array and send back the whole thing.
     * Found script on PHP.net by thomas at gielfeldt dot com
     **/
    private static function unparse_url($parsedurl) {

        $scheme = isset($parsedurl['scheme']) ? $parsedurl['scheme'] . '://' : '';
        $host = isset($parsedurl['host']) ? $parsedurl['host'] : '';
        $port = isset($parsedurl['port']) ? ':' . $parsedurl['port'] : '';
        $user = isset($parsedurl['user']) ? $parsedurl['user'] : '';
        $pass = isset($parsedurl['pass']) ? ':' . $parsedurl['pass'] : '';
        $pass = ($user || $pass) ? "$pass@" : '';
        $path = isset($parsedurl['path']) ? $parsedurl['path'] : '';
        $query = isset($parsedurl['query']) ? '?' . $parsedurl['query'] : '';
        $fragment = isset($parsedurl['fragment']) ? '#' . $parsedurl['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * Wrapper function for
     *
     * @param string $plugin
     * @param string $name
     * @return boolean|object|string
     */
    public static function get_config($plugin, $name = null) {

        // Did we get passed an item?
        if (empty($name)) {
            // No - just run the Moodle function.
            return get_config($plugin, $name);
        }

        // Get my companyid.
        $companyid = self::get_my_companyid(context_system::instance(), false);
        if ($companyid > 0) {
            $companyname = $name . "_" . $companyid;
        } else {
            // Not a valid companyid - use the site setting.
            return get_config($plugin, $name);
        }

        // Is there a company value?
        if ($value = get_config($plugin, $companyname)) {
            return $value;
        } else {
            // Use the site setting.
            return get_config($plugin, $name);
        }
    }
}

global $CFG;
require_once($CFG->libdir.'/formslib.php');

/**
 * Company Filter form used on the Iomad pages.
 *
 */
class iomad_company_filter_form extends \moodleform {
    protected $companyid;

    public function definition() {
        global $CFG, $DB, $USER, $SESSION;

        $mform = &$this->_form;
        $filtergroup = [];
        $mform->addElement('header', '', format_string(get_string('companysearchfields', 'local_iomad')));
        $mform->addElement('text', 'name', get_string('companynamefilter', 'local_iomad'), 'size="20"');
        $mform->addElement('text', 'city', get_string('companycityfilter', 'local_iomad'), 'size="20"');
        $mform->addElement('text', 'country', get_string('companycountryfilter', 'local_iomad'), 'size="20"');
        $mform->setType('name', PARAM_CLEAN);
        $mform->setType('city', PARAM_CLEAN);
        $mform->setType('country', PARAM_CLEAN);
        $mform->addElement('checkbox', 'showsuspended', get_string('show_suspended_companies', 'local_iomad'));
        $mform->setType('showsuspended', PARAM_INT);

        $this->add_action_buttons(false, get_string('companyfilter', 'local_iomad'));
    }
}
