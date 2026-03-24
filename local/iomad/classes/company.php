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
 * Local IOMAD company class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomad;

use block_iomad_company_admin\event\{
    company_created,
    company_deleted,
    company_license_created,
    company_license_deleted,
    company_license_updated,
    company_suspended,
    company_terminated,
    company_unsuspended,
    company_updated,
    company_user_assigned,
    company_user_unassigned,
    user_course_expired,
    user_license_assigned,
    user_license_unassigned,
    user_license_used,
};
use cache;
use cache_helper;
use context_course;
use context_system;
use core\event\{
    competency_framework_created,
    competency_framework_deleted,
    competency_template_created,
    competency_template_deleted,
    course_completed,
    user_created,
    user_updated,
    user_suspended,
    user_unsuspended,
    user_enrolment_created,
    user_deleted,
};
use core\notification;
use course_enrolment_manager;
use iomad_commerce;
use local_iomad\custom_context\context_company;
use local_iomad\task\enroleducatortask;
use local_iomadcustompage\event\iomadcustompage_deleted;
use moodle_url;

/**
 * Local IOMAD company class
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class company {

    /** @var int company id */
    public $id = 0;

    /** @var array company record */
    protected $companyrecord = null;

    /** @var object company context */
    public $context = null;

    /** @var array CSS fields */
    public $cssfields = ['bgcolor_header', 'bgcolor_content'];

    /**
     * Class constructor
     *
     * @param int $companyid
     */
    public function __construct(int $companyid) {
        global $DB, $SESSION;

        $this->id = $companyid;
        if (!$this->companyrecord = $DB->get_record('local_iomad_companies', ['id' => $this->id], '*')) {
            unset($SESSION->currenteditingcompany);
            unset($SESSION->company);
            unset($this->id);
            unset($this->context);
            return null;
        }
        $this->context = context_company::instance($companyid);
    }

    /**
     * Get selected fields
     * @param mixed fields string or array
     * @return mixed string or object (if array)
     */
    public function get($fields): string|object {
        if (is_string($fields)) {
            if (isset($this->companyrecord->$fields)) {
                return $this->companyrecord->$fields;
            } else {
                throw new Exception("Field not found in company record - " . $fields);
            }
        } else {
            $result = (object) [];
            foreach ($fields as $field) {
                if (property_exists($this->companyrecord, $field)) {
                    $result->$field = $this->companyrecord->$field;
                } else {
                    throw new Exception("Field not found in company record - " . $field);
                }
            }
            return $result;
        }
    }

    /**
     * Return an instance of the class using the company shortname
     *
     * Paramters -
     *             $userid = int;
     *
     * Returns class object or false
     *
     **/
    public static function by_userid(int $userid, bool $login = false): company|bool {
        global $DB, $SESSION;

        if (!$login && !empty($SESSION->currenteditingcompany)) {
            return new company($SESSION->currenteditingcompany);
        } else {
            if ($companies = $DB->get_records_sql("SELECT DISTINCT companyid,lastused
                                                   FROM {local_iomad_company_users}
                                                   WHERE userid = :userid
                                                   ORDER BY lastused, companyid DESC",
                                                  ['userid' => $userid])) {
                $company = array_shift($companies);
                return new company($company->companyid);
            } else {
                return false;
            }
        }
    }

    /**
     * Gets the company name for the current instance
     *
     * Returns text;
     *
     **/
    public function get_name(): string {
        return $this->companyrecord->name;
    }

    /**
     * Gets the company name for the current instance
     *
     * Returns text;
     *
     **/
    public function get_payment_account(): int {
        global $CFG;

        if (!empty($this->companyrecord->paymentaccountid)) {
            return $this->companyrecord->paymentaccountid;
        } else {
            return $CFG->commerce_admin_paymentaccount;
        }
    }

    /**
     * Gets the company dashboard page from the list of company pages.
     *
     * @return moodle_url|bool
     */
    public function get_dashboard_url(): moodle_url|bool {
        global $CFG, $DB;

        if (!empty($this->id) &&
            $url = $DB->get_record('local_iomad_company_pages', ['companyid' => $this->id, 'type' => 'dashboard'])) {
            return new moodle_url($CFG->wwwroot . "/local/iomadcustompage/view.php", ['id' => $url->pageid, 'useasmy' => true]);
        }
        return false;
    }

    /**
     * Gets the types of managers available to the class
     *
     * @param bool $full
     * @return array
     */
    public function get_managertypes(bool $full = false): array {
        global $CFG;

        $returnarray = ['0' => get_string('user', 'block_iomad_company_admin')];
        $companycontext = context_company::instance($this->id);
        if ($full ||
            iomad::has_capability('block/iomad_company_admin:assign_company_manager', $companycontext)) {
            $returnarray['1'] = get_string('companymanager', 'block_iomad_company_admin');
        }
        if ($full ||
            iomad::has_capability('block/iomad_company_admin:assign_department_manager', $companycontext)) {
            $returnarray['2'] = get_string('departmentmanager', 'block_iomad_company_admin');
        }
        if ($full ||
            (!get_config('local_iomad', 'iomad_autoenrol_managers') &&
             iomad::has_capability('block/iomad_company_admin:assign_educator', $companycontext))) {
            $returnarray['3'] = get_string('educator', 'block_iomad_company_admin');
        }
        if ($full ||
            iomad::has_capability('block/iomad_company_admin:assign_company_reporter', $companycontext)) {
            $returnarray['4'] = get_string('companyreporter', 'block_iomad_company_admin');
        }
        return $returnarray;
    }

    /**
     * Gets the company short name for the current instance
     *
     * @return string;
     *
     **/
    public function get_shortname(): string {
        return $this->companyrecord->shortname;
    }

    /**
     * Gets the company theme name for the current instance
     *
     * @return string
     *
     **/
    public function get_theme(): string {
        return $this->companyrecord->theme;
    }

    /**
     * Gets the company parentid name for the current instance
     *
     * @return int|bool
     *
     **/
    public function get_parentid(): int|bool {
        if (!empty($this->companyrecord->parentid)) {
            return $this->companyrecord->parentid;
        } else {
            return false;
        }
    }

    /**
     * Gets the company wwwroot for the current instance
     *
     * @return URL
     *
     **/
    public function get_wwwroot(): string {
        global $CFG;

        // Do we have a hostname for this company?
        if (!empty($this->companyrecord->hostname)) {
            // Parse the current wwwroot.
            $u = parse_url($CFG->wwwroot);
            if (empty($u["path"])) {
                 $u["path"] = "";
            }
            $url = "$u[scheme]://".$this->companyrecord->hostname."$u[path]" . (isset($u["query"]) ? "?$u[query]" : "");

            // Return the parse URL.
            return $url;
        } else {
            // Return the default wwwroot.
            return $CFG->wwwroot;
        }
    }

    /**
     * Gets the relative URL given wwwroot for the current instance
     *
     * @return URL
     *
     **/
    public static function get_relativeurl(string $url): string {
        $u = parse_url($url);
        if (empty($u["path"])) {
             $u["path"] = "";
        }
        // Return the relative URL.
        return $u["path"] . (isset($u["query"]) ? "?$u[query]" : "");
    }

    /**
     * Recurses up the company tree to get the parent company.
     *
     * @return int
     *
     **/
    public function get_topcompanyid(): int {

        // Set the return id to by myself initially.
        $returnid = $this->id;

        // Check if I have a parent id.
        if ($parentid = $this->get_parentid()) {

            // Check it if has a parent id.
            $parentcompany = new company($parentid);
            $returnid = $parentcompany->get_topcompanyid();
        }

        return $returnid;
    }

    /**
     * Gets the file path for the company logo for the current instance
     *
     * @return moodle_url or false
     *
     */
    public static function get_logo_url(int $companyid, $maxwidth = null, $maxheight = 200): string|bool {

        // Get the company logo config settings.
        $logo = get_config('core_admin', 'logo'.$companyid);
        if (!empty($logo)) {
            // Return the company logo URL.
            // 200px high is the default image size which should be displayed at 100px in the page to account for retina displays.
            // It's not worth the overhead of detecting and serving 2 different images based on the device.

            // Hide the requested size in the file path.
            $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

            // Use $CFG->themerev to prevent browser caching when the file changes.
            return moodle_url::make_pluginfile_url(context_system::instance()->id, 'core_admin', 'logo'.$companyid, $filepath,
                theme_get_revision(), $logo);
        } else {
            // Return the default site logo URL if there is one.
            $logo = get_config('core_admin', 'logo');
            if (empty($logo)) {
                return false;
            }

            // 200px high is the default image size which should be displayed at 100px in the page to account for retina displays.
            // It's not worth the overhead of detecting and serving 2 different images based on the device.

            // Hide the requested size in the file path.
            $filepath = ((int) $maxwidth . 'x' . (int) $maxheight) . '/';

            // Use $CFG->themerev to prevent browser caching when the file changes.
            return moodle_url::make_pluginfile_url(context_system::instance()->id, 'core_admin', 'logo', $filepath,
                theme_get_revision(), $logo);
        }
    }

    /**
     * Gets the record set of all companies
     *
     * @param int $page
     * @param int $perpage
     *
     * @return array
     *
     */
    public static function get_companies_rs(int $page=0, int $perpage=0) {
        global $DB;

        return $DB->get_recordset('local_iomad_companies', null, 'name', '*', $page, $perpage);
    }

    /**
     * Creates an array of companies to be used in a Select menu
     *
     * @return array
     *
     */
    public static function get_companies_select(bool $showsuspended=false,
                                                bool $useprepend = true,
                                                bool $showchildren = true,
                                                string $sort = 'name',
                                                string $search = ''): array {
        global $CFG, $DB, $USER;

        // Is this an admin, or a normal user?
        if (iomad::has_capability('block/iomad_company_admin:company_view_all', context_system::instance())) {
            $sqlparams = [];
            $sqlwhere = "";
            if (!empty(get_config('local_iomad', 'show_company_structure'))) {
                $sqlparams['parentid'] = 0;
                $sqlwhere .= " AND parentid = :parentid ";
            }
            if (!$showsuspended) {
                $sqlparams['suspended'] = 0;
                $sqlwhere .= " AND suspended = :suspended ";
            }
            if (!empty($search)) {
                $sqlwhere .= " AND " . $DB->sql_like('name', ':search', false);
                $sqlparams['search'] = '%' . $DB->sql_like_escape($search) . '%';
            }
            $companies = $DB->get_records_sql_menu(
                "SELECT id,
                 CASE WHEN suspended = 0 THEN name
                      ELSE concat(name, ' (S)') END AS name
                 FROM {local_iomad_companies}
                 WHERE 1 = 1
                 $sqlwhere
                 ORDER BY name",
                $sqlparams);
        } else {
            if ($showsuspended) {
                $suspendedsql = '';
            } else {
                $suspendedsql = "AND c.suspended = 0";
            }
            $searchsql = "";
            $companiesparams = ['userid' => $USER->id];
            if (!empty($search)) {
                $searchsql = "AND " . $DB->sql_like('c.name', ':search', false);
                $companiesparams['search'] = '%' . $DB->sql_like_escape($search) . '%';
            }
            // Show the hierarchy if required.
            if (!empty(get_config('local_iomad', 'show_company_structure'))) {
                $companies = $DB->get_records_sql_menu(
                    "SELECT DISTINCT c.id,
                     CASE WHEN c.suspended=0 THEN c.name
                          ELSE concat(c.name, ' (S)') END AS name,
                     cu.lastused
                     FROM {local_iomad_companies} c
                     JOIN {local_iomad_company_users} cu ON (c.id = cu.companyid)
                     WHERE cu.userid = :userid
                     AND cu.suspended = 0
                     $searchsql
                     $suspendedsql
                     ORDER BY $sort",
                     $companiesparams);
            } else {
                $companies = $DB->get_records_sql_menu(
                    "SELECT DISTINCT c.id,
                     CASE WHEN c.suspended=0 THEN c.name
                          ELSE concat(c.name, ' (S)') END AS name,
                     cu.lastused
                     FROM {local_iomad_companies} c
                     JOIN {local_iomad_company_users} cu ON (c.id = cu.companyid)
                     WHERE cu.userid = :userid
                     AND cu.suspended = 0
                     $searchsql
                     $suspendedsql
                     ORDER BY $sort",
                     $companiesparams);
            }
        }

        // Show the hierarchy if required.
        if (!empty(get_config('local_iomad', 'show_company_structure'))) {
            $companyselect = [];
            foreach ($companies as $id => $companyname) {
                $currentcompanycontext = context_company::instance($id);
                $companyselect[$id] = $companyname;
                // Only show children is we are able to.
                if ($showchildren &&
                    iomad::has_capability('block/iomad_company_admin:canviewchildren', $currentcompanycontext)) {
                    $allchildren = self::get_formatted_child_companies_select($id);
                    $companyselect = $companyselect + $allchildren;
                }
            }
            return $companyselect;
        } else {
            return $companies;
        }
    }

    /**
     * Get child companies in a formatted manner
     *
     * @param int $companyid
     * @param bool $useprepend
     * @param array $companyarray
     * @param string $prepend
     * @return array
     */
    private static function get_formatted_child_companies_select(int $companyid,
                                                                 bool $useprepend = true,
                                                                 array &$companyarray = [],
                                                                 string $prepend = ""): array {
        global $DB;

        if ($children = $DB->get_records('local_iomad_companies', ['parentid' => $companyid ], 'name', 'id,name,parentid')) {
            if ($useprepend) {
                $prepend = "--" . $prepend;
            } else {
                $prepend = "";
            }
            foreach ($children as $child) {
                $companyarray[$child->id] = $prepend . format_string($child->name);
                self::get_formatted_child_companies_select($child->id, $useprepend = true, $companyarray, $prepend);
            }
        }
        return $companyarray;
    }

    /**
     * Creates an array of child companies to be used in a Select menu
     *
     * @return array
     *
     */
    public function get_child_companies(): array {
        global $DB;

        return $DB->get_records('local_iomad_companies', ['parentid' => $this->id], 'name');
    }

    /**
     * Creates a recursive array of child companies.
     *
     * Returns array;
     *
     **/
    public function get_child_companies_recursive(): array {
        global $DB;

        $returnarray = [];

        $childcompanies = $this->get_child_companies();
        foreach ($childcompanies as $child) {
            $returnarray[$child->id] = $child;
            $childcompany = new company($child->id);
            $returnarray = $returnarray + $childcompany->get_child_companies_recursive();
        }
        return $returnarray;
    }

    /**
     * Creates a recursive array of parent companies .
     *
     * Returns array;
     *
     **/
    public function get_parent_companies_recursive(): array {
        global $DB;

        $returnarray = [];

        // Check if I have a parent id.
        if ($parentid = $this->get_parentid()) {
            $returnarray[$parentid] = $parentid;

            // Check it if has a parent id.
            $parentcompany = new company($parentid);
            $returnarray = $returnarray + $parentcompany->get_parent_companies_recursive();
        }

        return $returnarray;
    }

    /**
     * Creates an array of child companies to be used in a Select menu
     *
     * Returns array;
     *
     **/
    public function get_child_companies_select(): array {
        global $DB, $USER;

        $companyselect = [];

        // Get all of the child companies.
        $companies = $this->get_child_companies_recursive();

        foreach ($companies as $company) {
            if (empty($company->suspended)) {
                $companyselect[$company->id] = $company->name;
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
    public static function get_companyname_byid(int $companyid): string {
        global $DB;
        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
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
    public static function get_company_byuserid(int $userid): object {
        global $DB;
        if ($companies = (array) $DB->get_records_sql(
            "SELECT c.* FROM {local_iomad_company_users} cu
             INNER JOIN {local_iomad_companies} c ON cu.companyid = c.id
             WHERE cu.userid = :userid
             ORDER BY cu.id",
            ['userid' => $userid], 0, 1)) {
            return array_shift($companies);
        } else {
            return (object) [];
        }
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
    public static function get_category(int $companyid): object|false {
        global $DB;
        if ($category = $DB->get_record_sql(
            "SELECT uic.id, uic.name FROM
             {user_info_category} uic, {local_iomad_companies} c
             WHERE c.id = :companyid
             AND " . $DB->sql_compare_text('c.shortname') .
            " = ". $DB->sql_compare_text('uic.name'),
            ['companyid' => $companyid])) {
            return $category;
        } else {
            return false;
        }
    }

    /**
     * Get company role templates
     *
     **/
    public static function get_role_templates(int $companyid = 0): array {
        global $DB;

        if (empty($companyid)) {
            $companycontext = context_system::instance();
        } else {
            $companycontext = context_company::instance($companyid);
        }

        if (iomad::has_capability('block/iomad_company_admin:company_add', $companycontext)) {
            $templates = $DB->get_records_menu('local_iomad_company_role_templates', [], 'name', 'id,name');
        } else {
            $templates = $DB->get_records_sql_menu(
                "SELECT crt.id, crt.name
                 FROM {company_role_templates} crt
                 JOIN {local_iomad_company_role_templates_ass} crta
                 ON (crt.id = crta.templateid)
                 WHERE crta.companyid = :companyid
                 ORDER BY crt.name",
                ['companyid' => $companyid]);
        }
        $templates = ['i' => get_string('inherit', 'block_iomad_company_admin')] + $templates;

        // Add the default.
        $templates = [0 => get_string('none')] + $templates;

        return $templates;
    }

    /**
     * Apply company role templates
     *
     * @param integer $templateid
     * @return void
     */
    public function apply_role_templates(int $templateid = 0) {
        global $DB;

        if (!empty($templateid)) {
            $restrictions = $DB->get_records('local_iomad_company_role_templates_caps', ['templateid' => $templateid]);
        } else {
            // Get the same role entries as for the parent company id.
            $restrictions = $DB->get_records('local_iomad_company_role_restrictions', ['companyid' => $this->get_parentid()]);
        }

        // Insert the restrictions.
        // Remove them first.
        $DB->delete_records('local_iomad_company_role_restrictions', ['companyid' => $this->id]);

        // Add the template.
        foreach ($restrictions as $restriction) {
            $DB->insert_record(
                'local_iomad_company_role_restrictions',
                [
                    'companyid' => $this->id,
                    'roleid' => $restriction->roleid,
                    'capability' => $restriction->capability,
                ]
            );
        }
    }

    /**
     * Assign company role templates
     *
     * @param array $templates
     * @param bool $clear
     * @return void
     */
    public function assign_role_templates(array $templates = [], bool $clear = false) {
        global $DB;

        // Deal with any children.
        $children = $this->get_child_companies_recursive();
        foreach ($children as $child) {
            $childcompany = new company($child->id);
            $childcompany->assign_role_templates($templates, $clear);
        }

        // Final Deal with our own.
        if ($clear) {
            $DB->delete_records('local_iomad_company_role_templates_ass', ['companyid' => $this->id]);
        }
        foreach ($templates as $templateid) {
            $DB->insert_record('local_iomad_company_role_templates_ass', ['companyid' => $this->id, 'templateid' => $templateid]);
        }
    }

    /**
     * Get company email templates
     *
     * @param integer $companyid
     * @return array
     */
    public static function get_email_templates(int $companyid = 0): array {
        global $DB;

        $templates = $DB->get_records_menu('local_iomad_email_templatesets', [], 'templatesetname', 'id,templatesetname');

        // Add the default.
        $templates = [0 => get_string('none')] + $templates;

        return $templates;
    }

    /**
     * Apply company email templates
     *
     * @param integer $templatesetid
     * @return bool
     */
    public function apply_email_templates(int $templatesetid = 0): bool {
        global $DB;

        if (!empty($templatesetid)) {
            $templates = $DB->get_records('local_iomad_email_templateset_templates', ['templateset' => $templatesetid]);
        } else {
            return false;
        }

        // Insert the restrictions.
        // Remove them first - starting with the strings table.
        $currenttemplates = $DB->get_records('local_iomad_email_templates', ['companyid' => $this->id]);
        foreach ($currenttemplates as $currenttemplate) {
            $DB->delete_records('local_iomad_email_template_strings', ['templateid' => $currenttemplate->id]);
        }

        // Delete everything else.
        $DB->delete_records('local_iomad_email_templates', ['companyid' => $this->id]);

        // Add the template.
        foreach ($templates as $template) {
            $templatesetid = $template->id;
            unset($template->templateset);
            $template->companyid = $this->id;
            $templateid = $DB->insert_record('local_iomad_email_templates', $template);

            // Get all of the lang strings too.
            $langstrings = $DB->get_records('local_iomad_email_templateset_template_strings', ['templatesetid' => $templatesetid]);
            foreach ($langstrings as $langstring) {
                $langstring->templateid = $templateid;
                unset($langstring->templatesetid);
                $DB->insert_record('local_iomad_email_template_strings', $langstring);
            }
        }

        return true;
    }

    /**
     * Associates a course to a company
     *
     * @param object $course
     * @param integer $departmentid
     * @param bool $own
     * @param bool $licensed
     * @return bool
     */
    public function add_course(object $course, int|null $departmentid=0, bool $own=false, bool $licensed=false): bool {
        global $DB, $CFG;

        $coursecontext = context_course::instance($course->id);

        if ($departmentid != 0 ) {
            // Adding to a specified department.
            $companydepartment = $departmentid;
        } else {
            // Put course in default company department.
            $companydepartmentnode = self::get_company_parentnode($this->id);
            $companydepartment = $companydepartmentnode->id;
        }
        if (!$DB->record_exists('local_iomad_company_courses', ['companyid' => $this->id,
                                                   'courseid' => $course->id])) {
            $DB->insert_record('local_iomad_company_courses', ['companyid' => $this->id,
                                                  'courseid' => $course->id,
                                                  'departmentid' => $companydepartment]);
        }

        // Set up defaults for course management.
        if (!$DB->get_record('local_iomad_courses', ['courseid' => $course->id])) {
            $DB->insert_record('local_iomad_courses', ['courseid' => $course->id,
                                                 'licensed' => $licensed,
                                                 'shared' => 0]);
        }
        // Set up manager roles.
        if (!$licensed) {
            $companycoursenoneditorrole = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
            $companycourseeditorrole = $DB->get_record('role', ['shortname' => 'companycourseeditor']);
            if (get_config('local_iomad', 'autoenrol_managers')) {
                // Enrol the managers as teacher types.
                if ($companymanagers = $DB->get_records_select(
                    'local_iomad_company_users',
                    "companyid = :companyid
                     AND managertype != 0",
                    ['companyid' => $this->id])) {
                    foreach ($companymanagers as $companymanager) {
                        if ($user = $DB->get_record('user', ['id' => $companymanager->userid,
                                                             'deleted' => 0]) ) {
                            if ($DB->record_exists('course', ['id' => $course->id])) {
                                if (!$own) {
                                    // Not created by a company manager.
                                    company_user::enrol($user,
                                                        [$course->id],
                                                        $this->id,
                                                        $companycoursenoneditorrole->id);
                                } else {
                                    if ($companymanager->managertype == 2) {
                                        // Assign the department manager course access role.
                                        company_user::enrol($user,
                                                            [$course->id],
                                                            $this->id,
                                                            $companycoursenoneditorrole->id);
                                    } else {
                                        // Assign the company manager course access role.
                                        company_user::enrol($user,
                                                            [$course->id],
                                                            $this->id,
                                                            $companycourseeditorrole->id);

                                        // Check if this is a newly delegated course?
                                        if (user_has_role_assignment(
                                            $user->id,
                                            $companycoursenoneditorrole->id,
                                            $coursecontext->id)) {
                                            role_unassign($companycoursenoneditorrole->id,
                                                          $user->id,
                                                          $coursecontext->id);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                // Enrol the educators as teacher types.
                if ($educators = $DB->get_records_select(
                    'local_iomad_company_users',
                    "companyid = :companyid
                     AND educator != 0",
                    ['companyid' => $this->id])) {
                    foreach ($educators as $educator) {
                        if ($user = $DB->get_record('user', ['id' => $educator->userid,
                                                             'deleted' => 0]) ) {
                            if ($DB->record_exists('course', ['id' => $course->id])) {
                                if (!$own) {
                                    // Not created by a company manager.
                                    company_user::enrol($user,
                                                        [$course->id],
                                                        $this->id,
                                                        $companycoursenoneditorrole->id);
                                } else {
                                    // Assign the company manager course access role.
                                    company_user::enrol($user,
                                                        [$course->id],
                                                        $this->id,
                                                        $companycourseeditorrole->id);

                                    // Check if this is a newly delegated course?
                                    if (user_has_role_assignment($user->id,
                                                                 $companycoursenoneditorrole->id,
                                                                 $coursecontext->id)) {
                                        role_unassign($companycoursenoneditorrole->id,
                                                      $user->id,
                                                      $coursecontext->id);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($own && $departmentid == 0) {
            // Add it to the list of company created courses.
            if (!$DB->record_exists('local_iomad_company_created_courses', ['companyid' => $this->id,
                                                                'courseid' => $course->id])) {
                $DB->insert_record('local_iomad_company_created_courses', ['companyid' => $this->id,
                                                               'courseid' => $course->id]);
            }
        }

        cache_helper::purge_by_event('changesincompanycourses');
        return true;
    }

    /**
     * Removes control of a course from a company
     *
     * @param integer $courseid
     * @return bool
     */
    public function remove_control_of_course(int $courseid): bool {
        global $DB, $CFG;

        $coursecontext = context_course::instance($courseid);

        // Set up manager roles.
        $companycoursenoneditorrole = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
        $companycourseeditorrole = $DB->get_record('role', ['shortname' => 'companycourseeditor']);

        if (get_config('local_iomad', 'autoenrol_managers')) {
            // Enrol the managers as teacher types.
            if ($companymanagers = $DB->get_records_select(
                'local_iomad_company_users',
                "companyid = :companyid
                 AND managertype != 0",
                ['companyid' => $this->id])) {
                foreach ($companymanagers as $companymanager) {
                    if ($user = $DB->get_record('user', ['id' => $companymanager->userid,
                                                         'deleted' => 0]) ) {
                        if ($DB->record_exists('course', ['id' => $courseid])) {
                            // Not created by a company manager.
                            company_user::enrol($user,
                                                [$courseid],
                                                $this->id,
                                                $companycoursenoneditorrole->id);

                            // Clean up old roles.
                            if (user_has_role_assignment($user->id, $companycourseeditorrole->id, $coursecontext->id)) {
                                role_unassign($companycourseeditorrole->id, $user->id, $coursecontext->id);
                            }
                        }
                    }
                }
            }
        } else {
            // Enrol the educators as teacher types.
            if ($educators = $DB->get_records_select(
                'local_iomad_company_users',
                "companyid = :companyid
                 AND educator != 0",
                ['companyid' => $this->id])) {
                foreach ($educators as $educator) {
                    if ($user = $DB->get_record('user', ['id' => $educator->userid,
                                                         'deleted' => 0]) ) {
                        if ($DB->record_exists('course', ['id' => $courseid])) {
                            company_user::enrol($user,
                                                [$courseid],
                                                $this->id,
                                                $companycoursenoneditorrole->id);

                            // Clean up old roles.
                            if (user_has_role_assignment($user->id, $companycourseeditorrole->id, $coursecontext->id)) {
                                role_unassign($companycourseeditorrole->id, $user->id, $coursecontext->id);
                            }
                        }
                    }
                }
            }
        }

        // Remove it from the list of company created courses.
        $DB->delete_records('local_iomad_company_created_courses', ['companyid' => $this->id,
                                                        'courseid' => $courseid]);

        cache_helper::purge_by_event('changesincompanycourses');
        return true;
    }

    /**
     * Removes a course from a company
     *
     * @param object $course
     * @param integer $companyid
     * @param integer $departmentid
     * @return bool
     */
    public static function remove_course(object $course, int $companyid, int $departmentid=0): bool {
        global $CFG, $DB, $PAGE;

        $errors = false;
        $transaction = $DB->start_delegated_transaction();

        if (!$course = $DB->get_record('course', ['id' => $course->id])) {
            try {
                throw new Exception(get_string('couldnotdeletecourse', 'block_iomad_Company_admin'));
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
            return false;
        }

        if (!$iomadcourse = $DB->get_record('local_iomad_courses', ['courseid' => $course->id])) {
            try {
                throw new Exception(get_string('couldnotdeletecourse', 'block_iomad_Company_admin'));
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
            return false;
        }

        if ($departmentid == 0) {
            // Deal with the company departments.
            $companydepartments = $DB->get_records('local_iomad_company_departments', ['companyid' => $companyid]);
            // Check if it was a company created course and remove if it was.
            if ($companycourse = $DB->get_record('local_iomad_company_created_courses',
                                                 ['companyid' => $companyid,
                                                  'courseid' => $course->id])) {
                if (!$DB->delete_records('local_iomad_company_created_courses', ['id' => $companycourse->id])) {
                    $errors = true;
                }
            }
            // Check if its an unshared course in iomad.
            if ($iomadcourse->shared == 0) {
                if (!$DB->delete_records('local_iomad_courses', ['courseid' => $course->id, 'shared' => 0])) {
                    $errors = true;
                }
            }
            if (!$DB->delete_records('local_iomad_company_courses', ['companyid' => $companyid,
                                                        'courseid' => $course->id])) {
                $errors = true;
            }

            if (!$DB->delete_records('local_iomad_company_shared_courses', ['companyid' => $companyid,
                                                                'courseid' => $course->id])) {
                $errors = true;
            }

        } else {
            // Put course in default company department.
            $companydepartment = self::get_company_parentnode($companyid);
            if (!self::assign_course_to_department($companydepartment->id, $course->id, $companyid)) {
                $errors = true;
            }
        }

        // Remove the course from any licenses.
        if ($licenses = $DB->get_records_sql(
            "SELECT cl.* FROM {local_iomad_company_licenses} cl
             JOIN {local_iomad_company_license_courses} clc ON (cl.id = clc.licenseid)
             WHERE clc.courseid = :courseid
             AND cl.companyid = :companyid",
            ['courseid' => $course->id,
             'companyid' => $companyid])) {

            foreach ($licenses as $license) {
                // Delete anyone using the license for that course.
                if (!$DB->delete_records('local_iomad_company_license_users', ['licenseid' => $license->id,
                                                                  'courseid' => $course->id])) {
                    $errors = true;
                }
                // Delete the course from the license.
                if (!$DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $license->id,
                                                                    'courseid' => $course->id])) {
                    $errors = true;
                }

                // Fire an event for this.
                $eventother = ['licenseid' => $license->id,
                               'parentid' => $license->parentid];

                $event = company_license_updated::create(
                    [
                        'context' => context_company::instance($companyid),
                        'userid' => $USER->id,
                        'objectid' => $license->id,
                        'other' => $eventother,
                    ]
                );
                $event->trigger();
            }
        }

        // Un-enrol anyone from the course which hasn't already been cleared.
        require_once($CFG->dirroot . '/enrol/locallib.php');
        $courseenrolment = new course_enrolment_manager($PAGE, $course);
        $userlist = $courseenrolment->get_users('u.id', 'ASC', 0, 0);

        // We only want _our_ company users if it's shared.
        if ($iomadcourse->shared != 0) {
            $allcompanyusers = $DB->get_records_sql(
                "SELECT DISTINCT lit.userid
                 FROM {local_iomad_tracks} lit
                 WHERE lit.coursecleared = 0
                 AND lit.courseid = :courseid
                 AND lit.companyid = :companyid
                 AND lit.userid NOT IN (
                     SELECT lit2.userid
                     FROM {local_iomad_tracks} lit2
                     WHERE lit.userid = lit2.userid
                     AND lit.courseid = lit2.courseid
                     AND lit.coursecleared = lit2.coursecleared
                     AND lit.companyid != lit2.companyid
                 )",
                ['courseid' => $course->id,
                 'companyid' => $companyid]);

            $userlist = array_intersect_key($userlist, $allcompanyusers);

            // Remove the company groups.
            self::delete_company_course_group($companyid, $course, false);
        }

        // Remove their enrolments.
        foreach ($userlist as $user) {
            $ues = $courseenrolment->get_user_enrolments($user->id);
            foreach ($ues as $ue) {
                list ($instance, $plugin) = $courseenrolment->get_user_enrolment_components($ue);
                if ($instance && $plugin && $plugin->allow_unenrol_user($instance, $ue)) {
                    $plugin->unenrol_user($instance, $ue->userid);
                }
            }
        }

        if ($errors) {
            try {
                throw new Exception('Could not delete course');
            } catch (Exception $e) {
                $transaction->rollback(get_string('couldnotremovecoursefromcompany', 'block_iomad_company_admin'));
            }
            return false;
        } else {
            $transaction->allow_commit();
            cache_helper::purge_by_event('changesincompanycourses');
            return true;
        }
    }

    /**
     * Deletes a course from a company
     *
     * @param integer $companyid
     * @param integer $courseid
     * @param bool $destroy
     * @return bool
     */
    public static function delete_course(int $companyid,
                                         int $courseid,
                                         bool $destroy = false,
                                         $showfeedback = true): bool {
        global $DB, $USER, $CFG;

        $errors = false;
        $gone = false;
        require_once(__DIR__ . '/../../../course/format/lib.php');

        $transaction = $DB->start_delegated_transaction();

        if (!$course = $DB->get_record('course', ['id' => $courseid])) {
            try {
                throw new Exception(get_string('couldnotdeletecourse', 'block_iomad_Company_admin'));
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
            return false;
        }

        if (!$iomadcourse = $DB->get_record('local_iomad_courses', ['courseid' => $courseid])) {
            try {
                throw new Exception(get_string('couldnotdeletecourse', 'block_iomad_Company_admin'));
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
            return false;
        }

        // Remove the course from the company.
        if ($iomadcourse->shared != 1 && !self::remove_course($course, $companyid)) {
            $errors = true;
        }

        // Is the course a shared course?
        if ($iomadcourse->shared == 0) {
            // Call the moodle course delete function.
            if (!delete_course($courseid, $showfeedback)) {
                $errors = true;
            }
            if (!$DB->delete_records('local_iomad_courses', ['id' => $iomadcourse->id])) {
                $errors = true;
            }
            $gone = true;
        } else {
            // Check if it belongs to a company now?
            if (!$DB->get_records_select(
                'local_iomad_company_courses',
                "courseid = :courseid
                 AND companyid != :companyid",
                ['courseid' => $courseid,
                 'companyid' => $companyid])) {
                // Call the moodle course delete function.
                if (!delete_course($courseid)) {
                    $errors = true;
                }
                if (!$DB->delete_records('local_iomad_courses', ['id' => $iomadcourse->id])) {
                    $errors = true;
                }
                $gone = true;
            }
        }

        // Remove all entries from the {local_iomad_track_table} if destroy is true.
        if ($destroy) {
            if (!$gone) {
                if (!$DB->delete_records('local_iomad_tracks', ['companyid' => $companyid,
                                                               'courseid' => $courseid])) {
                    $errors = true;
                }
            } else {
                if (!$DB->delete_records('local_iomad_tracks', ['courseid' => $courseid])) {
                    $errors = true;
                }
            }
        }

        if ($errors) {
            try {
                throw new Exception('Could not delete course');
            } catch (Exception $e) {
                $transaction->rollback($e);
            }
            return false;
        } else {
            $transaction->allow_commit();
            cache_helper::purge_by_event('changesincompanycourses');
            return true;
        }
    }

    /**
     * Gets the company defined user account default variables
     *
     * @return object
     */
    public function get_user_defaults(): object {
        global $DB;

        $defaultfields = 'city,
                          country,
                          maildisplay,
                          mailformat,
                          maildigest,
                          autosubscribe,
                          trackforums,
                          htmleditor,
                          screenreader,
                          timezone,
                          lang';
        return $DB->get_record('local_iomad_companies', ['id' => $this->id], $defaultfields, MUST_EXIST);
    }

    /**
     * Get the user ids associated to a company
     * does not pass back any managers
     *
     * @return array
     */
    public function get_user_ids(): array {
        global $DB;

        // By default wherecondition retrieves all users except the
        // deleted, not confirmed and guest.
        $params = [
            'companyid' => $this->id,
            'companyidforjoin' => $this->id,
        ];

        $sql = "SELECT u.id, u.id AS mid, u.lastname, u.firstname
                FROM {local_iomad_company_users} cu
                INNER JOIN {user} u ON (cu.userid = u.id)
                WHERE u.deleted = 0
                AND cu.managertype = 0";

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';

        return $DB->get_records_sql($sql . $order, $params);
    }

    /**
     * Get all the user ids associated to a company
     *
     * @return array
     */
    public function get_all_user_ids(): array {
        global $DB;

        // By default wherecondition retrieves all users except the
        // deleted, not confirmed and guest.
        $params = [
            'companyid' => $this->id,
            'companyidforjoin' => $this->id,
        ];

        $sql = "SELECT DISTINCT u.id, u.id AS mid
                FROM {local_iomad_company_users} cu
                INNER JOIN {user} u ON (cu.userid = u.id)
                WHERE u.deleted = 0
                AND cu.companyid = :companyid";

        $order = ' ORDER BY u.lastname ASC, u.firstname ASC';

        return $DB->get_records_sql_menu($sql . $order, $params);
    }

    /**
     * Associates a user to a company
     *
     * @param integer $userid
     * @param integer $departmentid
     * @param integer $managertype
     * @param bool $ws
     * @param bool $import
     * @return bool
     */
    public function assign_user_to_company(int $userid,
                                           int $departmentid = 0,
                                           int $managertype = 0,
                                           bool $ws = false,
                                           bool $import = false): bool {
        global $CFG, $DB;

        // Is the user valid?
        if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0])) {
            return false;
        }

        // Were we passed a departmentid?
        if (!empty($departmentid)) {
            // Check its a department in this company.
            if (!$DB->get_record('local_iomad_company_departments', ['id' => $departmentid, 'companyid' => $this->id])) {
                $defaultdepartment = self::get_company_parentnode($this->id);
                $departmentid = $defaultdepartment->id;
            }
        } else {
            // Make it the default department id.
            $defaultdepartment = self::get_company_parentnode($this->id);
            $departmentid = $defaultdepartment->id;
        }

        // Were we passed a valid manager type?
        $managertypes = $this->get_managertypes(true);
        if (empty($managertypes[$managertype])) {
            // Default is standard user.
            $managertype = 0;
        }

        // If this is the only company, set the theme and any company profile info.
        if (!$DB->get_records('local_iomad_company_users', ['userid' => $userid])) {
            $DB->set_field('user', 'theme', $this->get_theme(), ['id' => $userid]);
            if (!empty(get_config('local_iomad', 'sync_institution'))) {
                $institution = $this->get('shortname');
                $DB->set_field('user', 'institution', $institution, ['id' => $userid]);
            }
            if (!empty(get_config('local_iomad', 'sync_department'))) {
                $deptrec = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
                $DB->set_field('user', 'department', $deptrec->name, ['id' => $userid]);
            }
        }

        // Create the record.
        $userrecord = [];
        $userrecord['departmentid'] = $departmentid;
        $userrecord['userid'] = $userid;
        $userrecord['managertype'] = $managertype;
        $userrecord['companyid'] = $this->id;

        if ($DB->get_record('local_iomad_company_users', ['companyid' => $this->id,
                                              'userid' => $userid,
                                              'departmentid' => $departmentid])) {
            // Already in this company.  Nothing left to do.
            return true;
        }

        // Moving a user.
        if (get_config('local_iomad', 'autoenrol_managers') && $managertype > 0 ) {
            $educator = true;
        } else {
            $educator = false;
        }

        // Did we get an error?
        if (!self::upsert_company_user($userid, $this->id, $departmentid, $managertype, $educator, $ws)) {
            if ($ws) {
                return false;
            } else {
                throw new moodle_exception(get_string('cantassignusersdb', 'block_iomad_company_admin'));
            }
        }

        // Are we importing their completion data too?
        if ($import) {
            // Create an adhoctask to set up these roles once cron runs again.
            $importtask = new \local_iomad_track\task\importusertask();
            $importtask->set_custom_data(['companyid' => $this->id, 'userid' => $userid]);

            // Queue the task.
            \core\task\manager::queue_adhoc_task($importtask);
        }

        // Deal with auto enrolments.
        if (get_config('local_iomad', 'signup_autoenrol')) {
            $user->companyid = $this->id;
            $this->autoenrol($user);
        }

        return true;
    }

    /**
     * Update/insert company user.
     *
     * @param int $userid
     * @param int $companyid
     * @param int $departmentid
     * @param int $managertype
     * @param bool $educator
     * @param bool $ws
     * @param bool $move
     * @return bool
     */
    public static function upsert_company_user(int $userid,
                                               int $companyid,
                                               int $departmentid,
                                               int $managertype,
                                               bool $educator=false,
                                               bool $ws=false,
                                               bool $move=false): bool {
        global $DB, $CFG;

        $assign = [
            'companyid' => $companyid,
            'userid' => $userid,
            'departmentid' => $departmentid,
            ];

        $success = true;
        $company = new company($companyid);
        $managertypes = $company->get_managertypes(true);

        // Is this a real user?
        if (!$userrec = $DB->get_record('user', ['id' => $userid])) {
            return false;
        }

        // Get the company context.
        $companycontext = context_company::instance($companyid);

        // Get the manager roles.
        $companymanagerrole = $DB->get_record('role', ['shortname' => 'companymanager']);
        $departmentmanagerrole = $DB->get_record('role', ['shortname' => 'companydepartmentmanager']);
        $companycoursenoneditorrole = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
        $companycourseeditorrole = $DB->get_record('role', ['shortname' => 'companycourseeditor']);
        $companyreporterrole = $DB->get_record('role', ['shortname' => 'companyreporter']);

        // Get the full company tree as we may need it.
        $topcompanyid = $company->get_topcompanyid();
        $topcompany = new company($topcompanyid);
        $companytree = $topcompany->get_child_companies_recursive();
        $parentcompanies = $company->get_parent_companies_recursive();
        $companytree[$topcompanyid] = $topcompanyid;
        $sqlparams = ['companyid' => $companyid];
        if (!empty($companytree)) {
            [$notinsql, $notinparams] = $DB->get_in_or_equal(array_keys($companytree),
                                                             SQL_PARAMS_NAMED,
                                                             'parentcid',
                                                             false);
            $parentcompanysql = " AND companyid {$notinsql}";
            $sqlparams = $sqlparams + $notinparams;
        } else {
            $parentcompanysql = " AND companyid != :companyid";
        }

        // Get the list of non-licensed company courses.
        $companyassignedcourses = $DB->get_records_sql(
            "SELECT cc.* FROM {local_iomad_company_courses} cc
             JOIN {local_iomad_courses} ic
             ON cc.courseid = ic.courseid
             WHERE cc.companyid = :companyid
             AND ic.licensed = 0",
            $sqlparams);
        $sharedcourses = $DB->get_records('local_iomad_courses', ['shared' => 1, 'licensed' => 0]);
        $companycourses = [];
        foreach ($companyassignedcourses as $companyassignedcourse) {
            $companycourses[$companyassignedcourse->courseid] = $companyassignedcourse;
        }
        foreach ($sharedcourses as $sharedcourse) {
            $sharedcourse->companyid = $companyid;
            $companycourses[$sharedcourse->courseid] = $sharedcourse;
        }

        // Does the user exist in the department?
        if (!$user = $DB->get_record('local_iomad_company_users', $assign)) {
            if (($managertype == 1 || $managertype == 2) && get_config('local_iomad', 'autoenrol_managers')) {
                $assign['educator'] = 1;
            } else {
                $assign['educator'] = $educator;
            }

            // Add the user to the new department.
            $success = $DB->insert_record('local_iomad_company_users',
                array_merge($assign, ['managertype' => $managertype, 'departmentid' => $departmentid]));

            // Are we moving the user?
            if ($move) {
                $selectsql = "companyid = :companyid
                              AND userid = :userid
                              AND departmentid != :departmentid";
                $selectparams = [
                    'companyid' => $companyid,
                    'userid' => $userid,
                    'departmentid' => $departmentid,
                ];
                $DB->delete_records_select('local_iomad_company_users', $selectsql, $selectparams);
            }
            if ($managertype == 0 &&
                $DB->get_records_select(
                     'local_iomad_company_users',
                     "userid = :userid
                      AND managertype != 0
                      AND companyid = :companyid",
                     ['userid' => $userid,
                      'companyid' => $companyid])) {
                // We are demoting a manager type.
                role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                role_unassign($companymanagerrole->id, $userid, $companycontext->id);

                // Deal with course permissions.
                if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                    // Fire the adhoc task to deal with their enrolments.
                    $enroltask = new enroleducatortask();
                    $enroltask->queue_task($userid, $companycourses, $managertype);
                }

                // Make sure all department records in the company match this.
                $DB->set_field('local_iomad_company_users', 'managertype', 0, ['companyid' => $companyid, 'userid' => $userid]);

            } else if ($managertype == 1 &&
                       $DB->get_records_select(
                        'local_iomad_company_users',
                        "userid = :userid
                         AND managertype = :roletype
                         $parentcompanysql",
                        $sqlparams +
                        ['userid' => $userid,
                         'roletype' => 1])) {
                // We have a company manager from another company.
                // Deal with company courses.
                if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                    // Fire the adhoc task to deal with their enrolments.
                    $enroltask = new enroleducatortask();
                    $enroltask->queue_task($userid, $companycourses, $managertype);

                    // External company managers don't go down the child company tree.
                    role_assign($companymanagerrole->id, $userid, $companycontext->id);
                }
            } else if ($managertype == 1) {
                // Give them the company manager role.
                role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                role_assign($companymanagerrole->id, $userid, $companycontext->id);

                // Deal with course permissions.
                if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                    // Fire the adhoc task to deal with their enrolments.
                    $enroltask = new enroleducatortask();
                    $enroltask->queue_task($userid, $companycourses, $managertype);
                }

                $selectsql = "userid = :userid
                              AND managertype IN (1,2)";
                $selectparams = ['userid' => $userid];
                $companycount = $DB->count_records_select('local_iomad_company_users', $selectsql, $selectparams);
                if ($companycount == 0) {
                    // Fire an email for this.
                    emailtemplate::send(
                        'user_promoted',
                        [
                            'company' => $company,
                            'user' => $userrec,
                        ]
                    );
                }
            } else if ($managertype == 2) {
                // Give them the department manager role.
                role_unassign($companymanagerrole->id, $userid, $companycontext->id);
                role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                role_assign($departmentmanagerrole->id, $userid, $companycontext->id);

                // Deal with company course roles.
                if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                    // Fire the adhoc task to deal with their enrolments.
                    $enroltask = new enroleducatortask();
                    $enroltask->queue_task($userid, $companycourses, $managertype);
                }

                // Make sure all department records in the company match this.
                $DB->set_field('local_iomad_company_users', 'managertype', 2, ['companyid' => $companyid, 'userid' => $userid]);

                $selectsql = "userid = :userid
                              AND managertype IN (1,2)";
                $selectparams = ['userid' => $userid];
                $companycount = $DB->count_records_select('local_iomad_company_users', $selectsql, $selectparams);
                if ($companycount == 0) {
                    // Fire an email for this.
                    emailtemplate::send(
                        'user_promoted',
                        [
                            'company' => $company,
                            'user' => $userrec,
                        ]
                    );
                }
            } else if ($managertype == 4 ) {
                // Give them the company reporter role.
                role_unassign($companymanagerrole->id, $userid, $companycontext->id);
                role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                role_assign($companyreporterrole->id, $userid, $companycontext->id);

                // Make sure all department records in the company match this.
                $DB->set_field('local_iomad_company_users', 'managertype', 4, ['companyid' => $companyid, 'userid' => $userid]);
            }
        } else {
            // Changing a user that is currently in the department.
            $s = [];
            if ($user->departmentid != $departmentid) {
                $s['departmentid'] = $departmentid;
            }
            if ($user->managertype != $managertype && $managertype != 3) {
                $s['managertype'] = $managertype;
            }
            if (($managertype == 1 || $managertype == 2) && get_config('local_iomad', 'autoenrol_managers')) {
                $s['educator'] = 1;
            } else if (get_config('local_iomad', 'autoenrol_managers')) {
                $s['educator'] = 0;
            } else if ($managertype == 3) {
                $s['educator'] = $educator;
            } else {
                $s['educator'] = $educator;
            }

            // Deal with any management role changes.
            if ($managertype != 0) {
                if ($managertype == 1) {
                    // Give them the company manager role.
                    role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                    role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                    role_assign($companymanagerrole->id, $userid, $companycontext->id);

                    // Deal with course permissions.
                    if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                        // Fire the adhoc task to deal with their enrolments.
                        $enroltask = new enroleducatortask();
                        $enroltask->queue_task($userid, $companycourses, $managertype);
                    }

                    if ($user->managertype == 0) {
                        $selectsql = "userid = :userid
                                      AND managertype IN (1,2)";
                        $selectparams = ['userid' => $userid];
                        $companycount = $DB->count_records_select('local_iomad_company_users', $selectsql, $selectparams);
                        if ($companycount == 0) {
                            // Fire an email for this.
                            emailtemplate::send(
                                'user_promoted',
                                [
                                    'company' => $company,
                                    'user' => $userrec,
                                ]);
                        }
                    }
                } else if ($managertype == 2) {
                    // Give them the department manager role.
                    role_unassign($companymanagerrole->id, $userid, $companycontext->id);
                    role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                    role_assign($departmentmanagerrole->id, $userid, $companycontext->id);

                    // Deal with company course roles.
                    if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                        // Fire the adhoc task to deal with their enrolments.
                        $enroltask = new enroleducatortask();
                        $enroltask->queue_task($userid, $companycourses, $managertype);
                    }
                    if ($user->managertype == 0) {
                        // Fire an email for this.
                        emailtemplate::send(
                            'user_promoted',
                            [
                                'company' => $company,
                                'user' => $userrec,
                            ]
                        );
                    }
                } else if ($managertype == 3 && !get_config('local_iomad', 'autoenrol_managers')) {
                    // Deal with company course roles.
                    if (get_config('local_iomad', 'autoenrol_managers') && !empty($companycourses)) {

                        // Fire the adhoc task to deal with their enrolments.
                        $enroltask = new enroleducatortask();
                        $enroltask->queue_task($userid, $companycourses, $educator);
                    }
                } else if ($managertype == 4 ) {
                    // Give them the company reporter role.
                    role_unassign($companymanagerrole->id, $userid, $companycontext->id);
                    role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                    role_assign($companyreporterrole->id, $userid, $companycontext->id);
                }

                if ($managertype == 1 || $user->managertype == 1) {
                    // Deal with child companies.
                    foreach ($company->get_child_companies_recursive() as $childcompany) {
                        // Get the top level department of the child company.
                        $childdepartment = self::get_company_parentnode($childcompany->id);
                        self::upsert_company_user($userid,
                                                  $childcompany->id,
                                                  $childdepartment->id,
                                                  $managertype,
                                                  $educator);
                    }
                }
            }
            if (($user->managertype == 1 ||
                 $user->managertype == 2 ||
                 $user->managertype == 4)
                 && $managertype == 0) {
                // Demoting a manager to a user.
                // Deal with company course roles.
                $multidepartment = $DB->get_records_select(
                    'local_iomad_company_users',
                    "companyid = :companyid
                     AND departmentid != :departmentid",
                    ['companyid' => $companyid,
                     'departmentid' => $departmentid]);
                if (get_config('local_iomad', 'autoenrol_managers') &&
                    !empty($companycourses) &&
                    empty($multidepartment)) {
                    foreach ($companycourses as $companycourse) {
                        if ($DB->record_exists('course', ['id' => $companycourse->courseid])) {
                            company_user::unenrol($userid,
                                                  [$companycourse->courseid],
                                                  $companycourse->companyid,
                                                  false);
                        }
                    }
                }
                if (empty($multidepartment)) {
                    role_unassign($companymanagerrole->id, $userid, $companycontext->id);
                    role_unassign($departmentmanagerrole->id, $userid, $companycontext->id);
                    role_unassign($companyreporterrole->id, $userid, $companycontext->id);
                }
                if ($user->managertype == 1) {
                    // Deal with child companies.
                    $childcompanies = $company->get_child_companies_recursive();
                    foreach ($childcompanies as $childcompany) {
                        // Get the top level department of the child company.
                        $childdepartment = self::get_company_parentnode($childcompany->id);
                        self::upsert_company_user($userid, $childcompany->id, $childdepartment->id, $managertype, $educator);
                        $DB->delete_records('local_iomad_company_users', ['companyid' => $childcompany->id, 'userid' => $userid]);
                    }
                }

                if ($user->managertype == 1 || $user->managertype == 2) {
                    $selectsql = "userid = :userid
                                  AND managertype IN (1,2)";
                    $selectparams = ['userid' => $userid];
                    $companycount = $DB->count_records_select('local_iomad_company_users', $selectsql, $selectparams);
                    if ($companycount == 1) {
                        // Fire an email for this.
                        emailtemplate::send(
                            'admin_deleted',
                            [
                                'company' => $company,
                                'user' => $userrec,
                            ]
                        );
                    }
                }
                if (empty($multidepartment)) {
                    // Make sure all department records in the company match this.
                    $DB->set_field('local_iomad_company_users', 'managertype', 0, ['companyid' => $companyid, 'userid' => $userid]);
                }
            }

            // Deal with any educator changes.
            if (get_config('local_iomad', 'autoenrol_managers') &&
                !empty($companycourses)) {

                // Fire the adhoc task to deal with their enrolments.
                $enroltask = new enroleducatortask();
                $enroltask->queue_task($userid, $companycourses, $s['educator']);
            }

            // Are we updating the user record?
            if (count($s)) {
                $s['id'] = $user->id;
                $success = $DB->update_record('local_iomad_company_users', array_merge($assign, $s));
            }
        }
        if (!$success) {
            throw new moodle_exception(get_string('cantassignusersdb', 'block_iomad_company_admin'));
        }

        // Create an event for this.
        $eventother = [
            'companyname' => $company->get_name(),
            'companyid' => $company->id,
            'departmentid' => $departmentid,
            'usertype' => $managertype,
            'usertypename' => $managertypes[$managertype],
            'moved' => $move,
        ];
        $event = company_user_assigned::create([
            'context' => $companycontext,
            'objectid' => $company->id,
            'userid' => $userid,
            'other' => $eventother,
        ]);

        // Fire the event.
        $event->trigger();

        if ($ws) {
            return $success;
        }

        return true;
    }

    /**
     * Removes a user from a company
     *
     * @param integer $userid
     * @param bool $ws
     * @return bool
     */
    public function unassign_user_from_company(int $userid, bool $ws = false): bool {
        global $CFG, $DB;

        $timestamp = time();

        // Moving a user.
        if (!$userrecords = $DB->get_records('local_iomad_company_users', ['companyid' => $this->id,
                                                                    'userid' => $userid])) {
            if ($ws) {
                return false;
            } else {
                throw new moodle_exception(get_string('cantassignusersdb', 'block_iomad_company_admin'));
            }
        }

        // Deal with company courses.
        if ($companycourses = $this->get_menu_courses(true, false, false, false)) {
            foreach ($companycourses as $courseid => $name) {
                $coursecontext = \context_course::instance($courseid);
                $selectsql = "userid = :userid
                              AND courseid = :courseid
                              AND companyid = :companyid
                              AND coursecleared = 0
                              AND timecompleted > 0";
                $selectparams = [
                    'userid' => $userid,
                    'companyid' => $this->id,
                    'courseid' => $courseid,
                ];
                if ($licrecs = $DB->get_records_select('local_iomad_tracks', $selectsql, $selectparams, '', 'id')) {
                    // Clear down the user from the courses.
                    foreach ($licrecs as $licrec) {
                        // Remove this specific record.
                        company_user::delete_user_course($userid, $courseid, 'autodelete', $licrec->id);
                    }
                }
            }
        }

        // Get licenses which are reusable and can be removed.
        if ($reusablelicenses = $DB->get_records_sql(
            "SELECT clu.*
             FROM {local_iomad_company_license_users} clu
             JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
             WHERE cl.companyid = :companyid
             AND (cl.type = 1 OR cl.type = 3)
             AND cl.expirydate > :timestamp
             AND clu.userid = :userid",
            ['timestamp' => $timestamp,
             'userid' => $userid,
             'companyid' => $this->id])) {
            foreach ($reusablelicenses as $reusablelicense) {
                $DB->delete_records('local_iomad_company_license_users', ['id' => $reusablelicense->id]);

                // Fire the license deleted event.
                $eventother = ['licenseid' => $reusablelicense->licenseid,
                                    'duedate' => 0];
                $event = user_license_unassigned::create([
                    'context' => context_course::instance($reusablelicense->courseid),
                    'objectid' => $reusablelicense->licenseid,
                    'courseid' => $reusablelicense->courseid,
                    'userid' => $reusablelicense->userid,
                    'other' => $eventother,
                ]);
                $event->trigger();

                // Update the license usage.
                self::update_license_usage($reusablelicense->licenseid);
            }
        }

        // Get licenses which are unused, non-program and can be removed.
        if ($nonprogramlicenses = $DB->get_records_sql(
            "SELECT clu.*
             FROM {local_iomad_company_license_users} clu
             JOIN {local_iomad_company_licenses} cl ON (clu.licenseid = cl.id)
             WHERE cl.companyid = :companyid
             AND (cl.type = 0 OR cl.type = 2)
             AND cl.program = 0
             AND clu.isusing = 0
             AND cl.expirydate > :timestamp
             AND clu.userid = :userid",
            ['timestamp' => $timestamp,
             'userid' => $userid,
             'companyid' => $this->id])) {
            foreach ($nonprogramlicenses as $nonprogramlicense) {
                $DB->delete_records('local_iomad_company_license_users', ['id' => $nonprogramlicense->id]);

                // Fire the license deleted event.
                $eventother = ['licenseid' => $nonprogramlicense->licenseid,
                                    'duedate' => 0];
                $event = user_license_unassigned::create([
                    'context' => context_course::instance($nonprogramlicense->courseid),
                    'objectid' => $nonprogramlicense->licenseid,
                    'courseid' => $nonprogramlicense->courseid,
                    'userid' => $nonprogramlicense->userid,
                    'other' => $eventother,
                ]);
                $event->trigger();

                // Update the license usage.
                self::update_license_usage($nonprogramlicense->licenseid);
            }
        }

        // Deal with program licenses.
        if ($programlicenses = $DB->get_records_sql(
            "SELECT DISTINCT cl.id
             FROM {local_iomad_company_licenses} cl
             JOIN {local_iomad_company_license_users} clu ON (cl.id = clu.licenseid)
             WHERE cl.companyid = :companyid
             AND cl.program = 1
             AND clu.userid = :userid
             AND cl.expirydate > :timestamp",
            ['timestamp' => $timestamp,
             'userid' => $userid,
             'companyid' => $this->id])) {

            foreach ($programlicenses as $programlicense) {
                // Check if there is a used course here.
                if ($DB->get_records(
                    'local_iomad_company_license_users',
                    [
                        'userid' => $userid,
                        'licenseid' => $programlicense->id,
                        'isusing' => 1,
                    ])) {
                    continue;
                } else {
                    $licenserecords = $DB->get_records(
                        'local_iomad_company_license_users',
                        [
                            'userid' => $userid,
                            'licenseid' => $programlicense->id,
                        ]);

                    foreach ($licenserecords as $licenserecord) {
                        // Fire the license deleted event.
                        $eventother = ['licenseid' => $licenserecord->licenseid,
                                            'duedate' => 0];
                        $event = user_license_unassigned::create([
                            'context' => context_course::instance($licenserecord->courseid),
                            'objectid' => $licenserecord->licenseid,
                            'courseid' => $licenserecord->courseid,
                            'userid' => $licenserecord->userid,
                            'other' => $eventother,
                        ]);
                        $event->trigger();
                    }

                    // Update the license usage.
                    self::update_license_usage($programlicense->id);
                }
            }
        }

        // Deal with any course reminders.
        $DB->set_field('local_iomad_tracks', 'notstartedstop', true, ['userid' => $userid, 'companyid' => $this->id]);
        $DB->set_field('local_iomad_tracks', 'completedstop', true, ['userid' => $userid, 'companyid' => $this->id]);
        $DB->set_field('local_iomad_tracks', 'expiredstop', true, ['userid' => $userid, 'companyid' => $this->id]);
        $DB->set_field('local_iomad_tracks', 'modifiedtime', time(), ['userid' => $userid, 'companyid' => $this->id]);

        // Delete the records.
        foreach ($userrecords as $userrecord) {
            // Are they something other than an ordinary user?
            if ($userrecord->managertype > 0) {
                // Deal with that.
                self::upsert_company_user($userid, $this->id, $userrecord->departmentid, 0, 0, $ws);
            }

            $DB->delete_records('local_iomad_company_users', ['id' => $userrecord->id]);
        }

        if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            // Fire off the payload to the external site.
            require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
            $user = $DB->get_record('user', ['id' => $userid]);
            iomad_commerce::delete_user($user->username, $this->id);
        }

        // Deal with the company theme.
        $DB->set_field('user', 'theme', '', ['id' => $userid]);

        return true;
    }

    /**
     * Assign managers in parent tenant to this tenant
     *
     * @param int $parentid
     * @param integer $finalcompanyid
     * @return void
     */
    public function assign_parent_managers(int $parentid, int $finalcompanyid = 0) {
        global $DB;

        if (empty($finalcompanyid)) {
            $finalcompanyid = $this->id;
        }
        $parentcompany = new company($parentid);
        $parentmanagers = $parentcompany->get_company_managers();
        $finalcompany = new company($finalcompanyid);
        foreach ($parentmanagers as $managerid) {
            $finalcompany->assign_user_to_company($managerid->userid, 0, 1, true);
        }
        // Is there any more?
        $grandparentid = $parentcompany->get_parentid();
        if (!empty($grandparentid)) {
            $parentcompany->assign_parent_managers($grandparentid, $finalcompanyid);
        }
    }

    /**
     * Unassign managers in parent tenant from this tenant
     *
     * @param int $parentid
     * @param integer $finalcompanyid
     * @return void
     */
    public function unassign_parent_managers(int $parentid, int $finalcompanyid = 0) {
        global $DB;

        if (empty($finalcompanyid)) {
            $finalcompanyid = $this->id;
        }
        $parentcompany = new company($parentid);
        $parentmanagers = $parentcompany->get_company_managers();

        $finalcompany = new company($finalcompanyid);
        foreach ($parentmanagers as $managerid) {
            $finalcompany->unassign_user_from_company($managerid->userid, true);
        }
        // Is there any more?
        $grandparentid = $parentcompany->get_parentid();
        if (!empty($grandparentid)) {
            $parentcompany->unassign_parent_managers($grandparentid, $finalcompanyid);
        }
    }

    /**
     * Get the managers in a tenant
     *
     * @param integer $managertype
     * @return array
     */
    public function get_company_managers(int $managertype=1): array {
        global $DB;

        return $DB->get_records(
            'local_iomad_company_users',
            ['companyid' => $this->id, 'managertype' => $managertype],
            null,
            'userid'
        );
    }

    // Department functions.

    /**
     * Set up default company department.
     *
     * @param integer $companyid
     * @return void
     */
    public static function initialise_departments(int $companyid) {
        global $DB;
        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
        $parentnode = [];
        $parentnode['shortname'] = $company->shortname;
        $parentnode['name'] = $company->name;
        $parentnode['companyid'] = $company->id;
        $parentnode['parentid'] = 0;
        $parentnodeid = $DB->insert_record('local_iomad_company_departments', $parentnode);
        // Get the company user's ids.
        if ($userids = $DB->get_records('local_iomad_company_users', ['companyid' => $companyid])) {
            foreach ($userids as $userid) {
                $userid->departmentid = $parentnodeid;
                $DB->update_record('local_iomad_company_users', $userid);
            }
        }
        // Get the company courses.
        if ($companycourses = $DB->get_records('local_iomad_company_courses', ['companyid' => $company->id])) {
            foreach ($companycourses as $companycourse) {
                $companycourse->departmentid = $parentnodeid;
                $DB->update_record('local_iomad_company_courses', $companycourse);
            }
        }
    }

    /**
     * Import company departments
     *
     * @param int $companyid
     * @param object $currentdepartment
     * @param object $importtree
     * @param bool $toplevel
     * @return void
     */
    public static function import_departments(int $companyid,
                                              object $currentdepartment,
                                              object $importtree,
                                              bool $toplevel = false) {
        global $DB;

        if (!$toplevel) {
            // Creating a new department.
            $newdepartment = (object) [];
            $newdepartment->name = $importtree->name;
            $newdepartment->shortname = $importtree->shortname;
            $newdepartment->companyid = $companyid;
            $newdepartment->parentid = $currentdepartment->id;

            // Sanity checking.
            if (!preg_match('/^[A-Za-z0-9_]+$/', trim($newdepartment->shortname))) {
                notification::warning(get_string('departmentnotimported', 'block_iomad_company_admin', $newdepartment));
                return;
            } else {
                $newdepartment->id = $DB->insert_record('local_iomad_company_departments', $newdepartment);
            }
        } else {
            // Already created so pass it.
            $newdepartment = $currentdepartment;
        }
        // Are there any children?
        if (empty($importtree->children)) {
            return;
        } else {
            // Create them.
            foreach ($importtree->children as $child) {
                self::import_departments($companyid, $newdepartment, $child, false);
            }
        }
    }

    /**
     * Get the department a user is associated to.
     *
     * @param object $user
     * @return array
     */
    public function get_userlevel(object $user): array {

        global $DB;

        // Get the company context.
        $companycontext = context_company::instance($this->id);

        // Can the user see the whole department tree?
        if (is_siteadmin() ||
            iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext) ||
            iomad::has_capability('block/iomad_company_admin:company_add', $companycontext) ||
            iomad::has_capability('block/iomad_company_admin:company_add_child', $companycontext)) {

            $topdepartment = self::get_company_parentnode($this->id);
            return [$topdepartment->id => $topdepartment];
        }

        // If not, get the department the user is assigned to in this company.
        return $DB->get_records_sql(
            "SELECT d.*
             FROM {local_iomad_company_departments} d
             JOIN {local_iomad_company_users} cu ON (
                 d.companyid = cu.companyid
                 AND d.id = cu.departmentid
             )
             WHERE cu.userid = :userid
             AND cu.companyid = :companyid
             ORDER BY d.name",
            ['userid' => $user->id,
             'companyid' => $this->id]);
    }

    /**
     * Get the list of user supervisors
     *
     * @param integer $userid
     * @return array|bool
     */
    public static function get_usersupervisor(int $userid): array|bool {
        global $DB, $CFG;

        // Get the company info.
        $companyinfo = self::get_company_byuserid($userid);
        if (!empty($companyinfo->emailprofileid)) {
            // Does the user have one defined by the company field?
            if (!$supervisor = $DB->get_record(
                'user_info_data',
                [
                    'userid' => $userid,
                    'fieldid' => $companyinfo->emailprofileid,
                ])) {
                return false;
            }
        } else if (!empty($CFG->companyemailprofileid)) {
            // Does the user have one defined by the default field?
            if (!$supervisor = $DB->get_record(
                'user_info_data',
                [
                    'userid' => $userid,
                    'fieldid' => $CFG->companyemailprofileid,
                ])) {
                return false;
            }
        }
        if (empty($supervisor)) {
            return false;
        }

        $emaillist = [];
        foreach (explode(',', $supervisor->data) as $testemail) {
            // Is it a valid email address?
            if (validate_email($testemail)) {
                $emaillist[$testemail] = $testemail;
            }
        }

        return $emaillist;
    }

    /**
     * Get the department details given an id.
     *
     * @param integer $departmentid
     * @return object
     */
    public static function get_departmentbyid(int $departmentid): object {
        global $DB;
        return $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
    }

    /**
     * Get the parent departments of the passed department
     *
     * @param object $department
     * @return object
     */
    public static function get_parentdepartments(object $department): object {
        global $DB;

        $returnarray = $department;
        // Check to see if its the top node.
        if (isset($department->id)) {
            if ($department->parentid != 0) {
                $parent = self::get_department_parentnode($department->id);
                if ($parent->parentid != 0 ) {

                    $returnarray->parents[] = self::get_parentdepartments($parent);
                } else {
                    $returnarray->parents[] = $parent;
                }
            }
        }

        return $returnarray;
    }

    /**
     * Get list of departments which are below this on on the tree
     *
     * @param object $parent
     * @param bool $ignorecurrentbranch
     * @return object
     */
    public static function get_subdepartments(object $parent, bool $ignorecurrentbranch = false): object {
        global $DB;

        // Are we trimming a current branch?
        if (isset($parent->id) && $parent->id == $ignorecurrentbranch) {
            return $parent;
        }

        $returnarray = $parent;
        // Check to see if its the top node.
        if (isset($parent->id)) {
            if ($children = $DB->get_records('local_iomad_company_departments', ['parentid' => $parent->id], 'name', '*')) {
                foreach ($children as $child) {
                    $returnarray->children[$child->id] = self::get_subdepartments($child, $ignorecurrentbranch);
                }
            }
        }

        return $returnarray;
    }

    /**
     * Get an array of all subdepartments to be used in a select
     *
     * @param object $parent
     * @return array
     */
    public static function get_subdepartments_list(object $parent): array {
        $subdepartmentstree = self::get_subdepartments($parent);
        $subdepartmentslist = self::get_department_list($subdepartmentstree);
        $returnlist = self::array_flatten($subdepartmentslist);
        unset($returnlist[$parent->id]);
        return $returnlist;
    }

    /**
     * Get a list of all departments
     *
     * @param object $tree
     * @param string $path
     * @return array
     */
    public static function get_department_list(object $tree, string $path=''): array {

        $flatlist = [];
        if (isset($tree->id)) {
            if (!empty($path)) {
                $flatlist[$tree->id] = $path . ' / ' . $tree->name;
            } else {
                $flatlist[$tree->id] = $tree->name;
            }
        }

        if (!empty($tree->children)) {
            foreach ($tree->children as $child) {
                if (!empty($path)) {
                    $flatlist[$child->id] = self::get_department_list($child, $path.' / '.$tree->name);
                } else {
                    $flatlist[$child->id] = self::get_department_list($child, $tree->name);
                }
            }
        }

        return $flatlist;
    }

    /**
     * Get a list of all parent departments
     *
     * @param object $tree
     * @param array $return
     * @return void
     */
    public static function get_parents_list(object $tree, array &$return = []) {

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
     * @param integer $companyid
     * @return object
     */
    public static function get_company_parentnode(int $companyid): object {
        global $DB;
        if (!$parentnode = $DB->get_record('local_iomad_company_departments', ['companyid' => $companyid,
                                                               'parentid' => '0'])) {
            self::initialise_departments($companyid);
            $parentnode = $DB->get_record('local_iomad_company_departments', ['companyid' => $companyid,
                                                               'parentid' => '0']);
        }
        return $parentnode;
    }

    /**
     * The parent department given a departmentid
     *
     * @param integer $departmentid
     * @return object|bool
     */
    public static function get_department_parentnode(int $departmentid): object|bool {
        global $DB;
        if ($department = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid])) {
            $parent = $DB->get_record('local_iomad_company_departments', ['id' => $department->parentid]);
            return $parent;
        } else {
            return false;
        }
    }

    /**
     * All parent departments given a departmentid
     *
     * @param integer $departmentid
     * @return array
     */
    public static function get_department_parentnodes(int $departmentid): array {
        global $DB;

        $parents = [];
        while ($myparent = self::get_department_parentnode($departmentid)) {
            $parents[$myparent->id] = $myparent;
            $departmentid = $myparent->id;
        }
        return $parents;
    }

    /**
     * The top level department id given a department id
     *
     * @param integer $departmentid
     * @return integer
     */
    public static function get_top_department(int $departmentid): int {
        global $DB;
        $department = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);
        $parentnode = self::get_company_parentnode($department->companyid);
        return $parentnode->id;
    }

    /**
     * Get a full department tree listing given a company id
     *
     * @param integer $companyid
     * @return array
     */
    public static function get_all_departments(int $companyid): array {

        $parentlist = [];
        $parentnode = self::get_company_parentnode($companyid);
        $parentlist[$parentnode->id] = [$parentnode->id => $parentnode->name];
        $departmenttree = self::get_subdepartments($parentnode);
        $departmentlist = self::array_flatten($parentlist +
                                              self::get_department_list($departmenttree));
        return $departmentlist;
    }

    /**
     * Get array of all departments given companyid
     * Used to display select tree
     *
     * @param int companyid
     * @return object
     */
    public static function get_all_departments_raw(int $companyid): object {
        $parentlist = [];
        $parentnode = self::get_company_parentnode($companyid);
        $departmenttree = self::get_subdepartments($parentnode);

        return $departmenttree;
    }

    /**
     * Get array of all departments given companyid
     * Used to display select tree
     * @param int companyid
     * @return object|null
     */
    public static function get_all_subdepartments_raw(int $departmentid,
                                                      bool $ignorecurrentbranch = false,
                                                      bool $addchildcompanies = false): object|null {

        // Are we trimming a current branch?
        if ($departmentid == $ignorecurrentbranch) {
            return null;
        }

        $departmentnode = self::get_departmentbyid($departmentid);
        $departmenttree = self::get_subdepartments($departmentnode, $ignorecurrentbranch);

        if ($addchildcompanies) {
            $currentcompany = new company($departmentnode->companyid);
            if ($childcompanies = $currentcompany->get_child_companies_recursive()) {
                foreach ($childcompanies as $childcompany) {
                    $childnode = self::get_company_parentnode($childcompany->id);
                    $departmenttree->children[] = self::get_subdepartments($childnode, $ignorecurrentbranch);

                }
            }
        }

        return $departmenttree;
    }

    /**
     * function to flatten a multi-dimension array to a single dimension array
     *
     * @param array $array
     * @param array $result
     * @return array|null
     */
    public static function array_flatten(array $array, &$result = null): array|null {

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
        return null;
    }

    /**
     * function to flatten a multi-dimension array to a single dimension array.
     *
     * @param array $array
     * @param [type] $result
     * @return array|null
     */
    public static function array_flatten_children(array $array, &$result = null): array|null {

        $r = null === $result;
        $i = 0;
        foreach ($array as $key => $value) {
            $i++;
            if (!empty($value->children) && is_array($value->children)) {
                self::array_flatten_children($value->children, $result);
            }
            $result[$key] = $value;
        }
        if ($r) {
            return $result;
        }

        return null;
    }

    /**
     * Gets a list of the sub department tree list given a department id
     * including the passed department.
     *
     * @param integer $parentnodeid
     * @param bool $addchildcompanies
     * @return array
     */
    public static function get_all_subdepartments(int $parentnodeid, bool $addchildcompanies = false): array {
        global $PAGE;

        // Format_string() references $PAGE context so nee to set that if it's not already set.
        $options = [];
        if (empty($PAGE->context)) {
            // Format string needs the context.
            $options['context'] = context_system::instance();
        }

        $parentnode = self::get_departmentbyid($parentnodeid);
        $parentlist = [];
        $parentlist[$parentnodeid] = format_string($parentnode->name, true, $options);
        $departmenttree = self::get_subdepartments($parentnode);
        if ($addchildcompanies) {
            $currentcompany = new company($parentnode->companyid);
            if ($childcompanies = $currentcompany->get_child_companies_recursive()) {
                foreach ($childcompanies as $childcompany) {
                    $childnode = self::get_company_parentnode($childcompany->id);
                    $childtree = self::get_subdepartments($childnode);
                    $childlist[$childnode->id] = format_string($childnode->name, true, $options);
                    $departmenttree->children[] = $childtree;

                }
            }
        }

        $departmentlist = self::array_flatten($parentlist +
                          self::get_department_list($departmenttree));
        return $departmentlist;
    }

    /**
     * Gets a list of all users from this department down
     * including the passed department.
     *
     * @param integer $departmentid
     * @param bool $addchildcompanies
     * @return array
     */
    public static function get_recursive_department_users(int $departmentid, bool $addchildcompanies = false): array {
        global $DB;

        $departmentlist = self::get_all_subdepartments($departmentid, $addchildcompanies);
        $userlist = [];
        if (!empty($departmentlist)) {
            foreach ($departmentlist as $id => $value) {
                $departmentusers = self::get_department_users($id);
                $userlist = $userlist + $departmentusers;
            }
        }
        return $userlist;
    }

    /**
     * Gets all of the users that manager is responsible for
     *
     * @param integer $companyid
     * @param integer $departmentid
     * @return array
     */
    public static function get_my_users(int $companyid=0, int $departmentid=0): array {
        global $USER;

        if (empty($companyid)) {
            return [];
        }
        $company = new company($companyid);
        if (empty($departmentid)) {
            if (is_siteadmin($USER->id)) {
                $department = self::get_company_parentnode($companyid);
                $departmentids = [$department->id];
            } else {
                $departments = $company->get_userlevel($USER);
                $departmentids = array_keys($departments);
            }
        }
        $users = [];
        foreach ($departmentids as $departmentid) {
            $users = $users + self::get_recursive_department_users($departmentid);
        }
        return $users;
    }

    /**
     * Gets a list of the company managers for the company
     *
     * @return array
     */
    public function get_managers(): array {
        global $DB;

        $parentsql = "";
        $sqlparams = [];
        if ($parentslist = $this->get_parent_companies_recursive()) {
            [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                        SQL_PARAMS_NAMED,
                                                        'pids');
            $parentsql = "AND u.id NOT IN (
                            SELECT userid FROM {local_iomad_company_users}
                            WHERE companyid {$insql}
                          )";
        }
        $sqlparams['companyid'] = $this->id;

        // Get the managers in that list of departments.
        return $DB->get_records_sql(
            "SELECT u.*
             FROM {user} u
             JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
             WHERE cu.managertype = 1
             AND cu.companyid = :companyid
             $parentsql",
            $sqlparams);
    }

    /**
     * Gets a list of the company managers for the company that
     * can be used in a form select
     *
     * @return array
     */
    public function get_managers_select(): array {

        // Set up the initial array.
        $managerlist = ['0' => get_string('none')];

        // Get any company managers.
        if ($managers = $this->get_managers()) {
            foreach ($managers as $manager) {
                $managerlist[$manager->id] = fullname($manager);
            }
        }

        return $managerlist;
    }

    /**
     * Gets a list of the managers for the passed userid
     *
     * @param integer $userid
     * @param integer $managertype
     * @return array
     */
    public function get_my_managers(int $userid, int $managertype): array {
        global $DB, $USER;

        // Get the users department.
        $userdepartments = $DB->get_records('local_iomad_company_users', ['userid' => $userid, 'companyid' => $this->id]);

        // Set the initial return array.
        $managers = [];
        $departments = [];
        // Get the list of parent departments.
        foreach ($userdepartments as $companyuserrec) {
            if ($userdepartment = $this->get_departmentbyid($companyuserrec->departmentid)) {
                $departmentlist = $this->get_parentdepartments($userdepartment);
                self::get_parents_list($departmentlist, $departments);
            }
        }
        if (!empty($departments)) {
            // Get the managers in that list of departments.
            [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($departments),
                                                        SQL_PARAMS_NAMED,
                                                        'deptids');
            $sqlparams['managertype'] = $managertype;
            $sqlparams['userid'] = $USER->id;
            $managers = $DB->get_records_select(
                'local_iomad_company_users',
                "managertype = :managertype
                 AND userid != :userid
                 AND departmentid {$insql}",
                $sqlparams,
                '',
                'userid');
        }

        // Return them.
        return $managers;
    }

    /**
     * Gets a list of the users that manager is responsible for
     *
     * @param integer $companyid
     * @param integer $departmentid
     * @return string
     */
    public static function get_my_users_list(int $companyid=0, int $departmentid=0): string {
        global $USER;

        if (empty($companyid)) {
            return [];
        }
        $userlist = self::get_my_users($companyid, $departmentid);
        $users = [];
        foreach ($userlist as $user) {
            $users[] = $user->userid;
        }
        return implode(',', $users);
    }

    /**
     * Gets a list of the users at this department id
     *
     * @param integer $departmentid
     * @return array
     */
    public static function get_department_users(int $departmentid): array {
        global $DB;
        return $DB->get_records('local_iomad_company_users',
                                ['departmentid' => $departmentid],
                                null,
                                'userid,id,companyid,managertype,departmentid,suspended');
    }

    /**
     * Assign a user to a department.
     *
     * @param integer $departmentid
     * @param integer $userid
     * @param integer $managertype
     * @param bool $ws
     * @return bool
     */
    public static function assign_user_to_department(int $departmentid,
                                                     int $userid,
                                                     int $managertype = 0,
                                                     bool $ws = false): bool {
        global $DB;

        $userrecord = [];
        $userrecord['departmentid'] = $departmentid;
        $userrecord['userid'] = $userid;

        // We need the company.
        $departmentrec = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid]);

        // Moving a user.
        if ($currentuser = $DB->get_record(
            'local_iomad_company_users',
            ['userid' => $userid, 'companyid' => $departmentrec->companyid])) {
            $currentuser->departmentid = $departmentid;
            if ($ws && !empty($managertype)) {
                $currentuser->managertype = $managertype;
            }
            if (!$DB->update_record('local_iomad_company_users', $currentuser)) {
                if ($ws) {
                    return false;
                } else {
                    throw new moodle_exception(get_string('cantupdatedepartmentusersdb', 'block_iomad_company_admin'));
                }
            }
        }
        return true;
    }

    /**
     * Creates a new department
     *
     * @param integer $departmentid
     * @param integer $companyid
     * @param string $fullname
     * @param string $shortname
     * @param integer $parentid
     * @return bool
     */
    public static function create_department(int $departmentid,
                                             int $companyid,
                                             string $fullname,
                                             string $shortname,
                                             int $parentid=0): bool {
        global $DB;
        $newdepartment = [];
        if (!empty($departmentid)) {
            if ($departmentid == $parentid) {
                return false;
            }
            $newdepartment['id'] = $departmentid;
        }
        if ($parentid) {
            $newdepartment['parentid'] = $parentid;
        }
        $newdepartment['companyid'] = $companyid;
        $newdepartment['name'] = $fullname;
        $newdepartment['shortname'] = $shortname;
        if (isset($newdepartment['id'])) {
            // We are editing a current department.
            if (!$DB->update_record('local_iomad_company_departments', $newdepartment)) {
                throw new moodle_exception(get_string('cantupdatedepartmentdb', 'block_iomad_company_admin'));
            }
        } else {
            // Adding a new department.
            if (!$DB->insert_record('local_iomad_company_departments', $newdepartment)) {
                throw new moodle_exception(get_string('cantinsertdepartmentdb', 'block_iomad_company_admin'));
            }
        }

        return true;
    }

    /**
     * Delete a department.
     *
     * @param integer $departmentid
     * @return bool
     */
    public static function delete_department(int $departmentid): bool {
        global $DB;
        if (!$DB->delete_records('local_iomad_company_departments', ['id' => $departmentid])) {
            throw new moodle_exception(get_string('cantdeletedepartmentdb', 'blocks_iomad_company_admin'));
        }
        return true;
    }

    /**
     * Delete the passed department and all of it's children
     *
     * @param integer $departmentid
     * @param integer $targetdepartment
     * @return void
     */
    public static function delete_department_recursive(int $departmentid, int $targetdepartment=0) {
        // Get all the users from here and below.
        $userlist = self::get_recursive_department_users($departmentid);
        $departmentlist = self::get_all_subdepartments($departmentid);
        if ($targetdepartment == 0) {
            // Moving users to the parent node of the current department.
            $parentnode = self::get_department_parentnode($departmentid);
            $targetdepartment = $parentnode->id;
        }
        foreach ($userlist as $user) {
            // Move the users.
            self::assign_user_to_department($targetdepartment, $user->id);
        }
        foreach ($departmentlist as $id => $value) {
            self::delete_department($id);
        }
    }

    /**
     * Check if a user is a manger of this department.
     *
     * @param integer $departmentid
     * @return bool
     */
    public static function can_manage_department(int $departmentid): bool {
        global $DB, $USER;

        // Get the department record.
        $departmentrec = $DB->get_record('local_iomad_company_departments', ['id' => $departmentid], '*', MUST_EXIST);

        // And the context.
        $companycontext = context_company::instance($departmentrec->companyid);

        // Can we manage it?
        if (iomad::has_capability('block/iomad_company_admin:edit_all_departments', $companycontext)) {
            return true;
        } else if (!iomad::has_capability('block/iomad_company_admin:edit_departments', $companycontext)) {
            return false;
        } else {
            $company = new company($departmentrec->companyid);
            // Get the list of departments at and below the user assignment.
            $userhierarchylevels = $company->get_userlevel($USER);
            $subhierarchytree = [];
            foreach ($userhierarchylevels as $userhierarchylevel) {
                $subhierarchytree = $subhierarchytree + self::get_all_subdepartments($userhierarchylevel->id);
            }
            if (isset($subhierarchytree[$departmentid])) {
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
     * Get a list of all courses in this department and it's children
     *
     * @param integer $departmentid
     * @return array
     */
    public static function get_recursive_department_courses(int $departmentid): array {
        global $DB;

        $departmentlist = self::get_all_subdepartments($departmentid);
        $courselist = [];
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
        // Get the shared courses.
        $sharedcourses = $DB->get_records('local_iomad_courses', ['shared' => 1]);
        return $courselist + $sharedcourses;
    }

    /**
     * Gets a list of all courses in this department
     *
     * @param integer $departmentid
     * @return array
     */
    public static function get_department_courses(int $departmentid): array {
        global $DB;
        return $DB->get_records('local_iomad_company_courses', ['departmentid' => $departmentid]);
    }

    /**
     * Assign a course to this department
     *
     * @param integer $departmentid
     * @param integer $courseid
     * @param integer $companyid
     * @return bool
     */
    public static function assign_course_to_department(int $departmentid, int $courseid, int $companyid): bool {
        global $DB;

        // Moving a course.
        // Get all the department assignments which may exist taking
        // shared courses into consideration.
        if ($currentcourses = $DB->get_records('local_iomad_company_courses',
                                                ['courseid' => $courseid])) {
            $foundcourse = false;
            foreach ($currentcourses as $currentcourse) {
                // Check if the found record belongs to the current company.
                if ($DB->get_record('local_iomad_company_departments', ['companyid' => $companyid,
                                                   'id' => $departmentid])) {
                    $foundcourse = true;
                    // Update it.
                    $currentcourse->departmentid = $departmentid;
                    if (!$DB->update_record('local_iomad_company_courses', $currentcourse)) {
                        throw new moodle_exception(get_string('cantupdatedepartmentcoursesdb',
                                               'block_iomad_company_admin'));
                    }
                    break;
                }
            }
            if (!$foundcourse) {
                // Assigning a shared course to a new company.
                $courserecord = [];
                $courserecord['departmentid'] = $departmentid;
                $courserecord['courseid'] = $courseid;
                $courserecord['companyid'] = $companyid;
                if (!$DB->insert_record('local_iomad_company_courses', $courserecord)) {
                    throw new moodle_exception(get_string('cantinsertdepartmentcoursesdb',
                                           'block_iomad_company_admin'));
                }
            }
        } else {
            // Assigning a new course to a company.
            $courserecord = [];
            $courserecord['departmentid'] = $departmentid;
            $courserecord['courseid'] = $courseid;
            $courserecord['companyid'] = $companyid;
            if (!$DB->insert_record('local_iomad_company_courses', $courserecord)) {
                throw new moodle_exception(get_string('cantinsertdepartmentcoursesdb',
                                       'block_iomad_company_admin'));
            }
        }
        return true;
    }

    /**
     * Get a list of departments a course is associated to
     *
     * @param integer $courseid
     * @return array
     */
    public static function get_departments_by_course(int $courseid): array {
        global $DB;
        if ($depts = $DB->get_records('local_iomad_company_courses', ['courseid' => $courseid],
                                                         null,
                                                         'departmentid')) {
            return array_keys($depts);
        } else {
            return [];
        }
    }

    // Licenses stuff.

    /**
     * Gets a list of all licenses in this department and it's children
     *
     * @param integer $departmentid
     * @return array
     */
    public static function get_recursive_departments_licenses(int $departmentid): array {

        // Get all the courses for this department down.
        $courses = self::get_recursive_department_courses($departmentid);
        $licenselist = [];
        foreach ($courses as $course) {
            $courselicenses = self::get_course_licenses($course->courseid);
            $licenselist = $licenselist + $courselicenses;
        }
        return $licenselist;
    }

    /**
     * Gets a list of all licenses for this course id
     *
     * @param integer $courseid
     * @return array
     */
    public static function get_course_licenses(int $courseid): array {
        global $DB;
        return $DB->get_records('local_iomad_company_license_courses', ['courseid' => $courseid], null, 'licenseid');
    }

    /**
     * Gets a list of all courses for this license id
     *
     * @param integer $licenseid
     * @param bool $visible
     * @return array
     */
    public static function get_courses_by_license(int $licenseid, bool $visible = true): array {
        global $DB;

        // Show all or only visible courses?
        $visiblesql = "";
        if ($visible) {
            $visiblesql = " AND c.visible = 1 ";
        }

        // Do we have any?
        if ($courses = $DB->get_records_sql(
            "SELECT c.id, c.fullname
             FROM {course} c
             JOIN {local_iomad_company_license_courses} clc ON (c.id = clc.courseid)
             WHERE clc.licenseid = :licenseid
             $visiblesql
             ORDER BY c.fullname",
            ['licenseid' => $licenseid])) {

            // Format multi-language course full name.
            foreach ($courses as $key => $course) {
                $courses[$key]->fullname = format_string($course->fullname, true, 1);
            }

            return $courses;
        }

        // We don't - return an empty array.
        return [];
    }

    /**
     * Update license usage.
     *
     * @param integer $licenseid
     * @return void
     */
    public static function update_license_usage(int $licenseid) {
        global $DB;

        // Get the allocation from any child licenses.
        if ($childusage = $DB->get_records_sql("SELECT sum(allocation) AS total
                                                FROM {local_iomad_company_licenses}
                                                WHERE parentid = :parentid",
                                                ['parentid' => $licenseid])) {
            $child = array_pop($childusage);
            $childtotal = $child->total;
        } else {
            $childtotal = 0;
        }

        // Get the number of user assigned licenses for this license.
        $usertotal = $DB->count_records('local_iomad_company_license_users', ['licenseid' => $licenseid]);

        // If we have a license, update it.
        if ($license = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            $license->used = $childtotal + $usertotal;
            $DB->update_record('local_iomad_company_licenses', $license);
        }
    }

    /**
     * Check if a license is assigned to a child company.
     *
     * @param integer $licenseid
     * @return bool
     */
    public function is_child_license(int $licenseid): bool {
        global $DB;

        if (!$licenseinfo = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            return false;
        }
        // Get the child companies.
        $childcompanies = $this->get_child_companies_recursive();

        // Check if they match the license company?
        foreach ($childcompanies as $childcompany) {
            if ($licenseinfo->companyid == $childcompany->id) {
                // If so then it is.
                return true;
            }
        }

        // Return false as a default.
        return false;
    }

    /**
     * Get a menu list of courses based on the parameters passed.
     *
     * @param bool $shared include shared courses
     * @param bool $unlicensed include only unlicensed courses
     * @param bool $groups include courses without groups enabled
     * @param bool $default include a default menu item
     * @param bool $licenseonly include only licensed courses
     * @param bool $noncompany include courses that are unassigned to a tenant
     * @param bool $includehidden include courses which are hidde
     * @return array
     */
    public function get_menu_courses(bool $shared = false,
                                     bool $unlicensed = false,
                                     bool $groups = false,
                                     bool $default = true,
                                     bool $licenseonly = false,
                                     bool $noncompany = false,
                                     bool $includehidden = false): array {
        global $DB;

        // Set some defaults.
        $unlicensesql = "";
        $sharedlicsql = "";
        $licenseonlysql = "";
        $sharedsql = "";
        $groupsql = "";
        $noncompanysql = "";
        $hiddensql = " AND c.visible = 1 ";
        $showhidden = false;

        // Can we view hidden courses or do we want them anyway?
        $hiddenstring = " (" . get_string('hidden', 'grades') . ")";
        if (iomad::has_capability('block/iomad_company_admin:hideshowcourses', $this->context) ||
            iomad::has_capability('block/iomad_company_admin:hideshowallcourses', $this->context) ||
            $includehidden) {
            $hiddensql = "";
            $showhidden = true;
        }

        // Deal with license options.
        if ($unlicensed) {
            $unlicensesql = "c.id NOT IN (
                             SELECT courseid FROM {local_iomad_courses}
                             WHERE licensed = 1
                           )
                           AND";
            $sharedlicsql = " AND licensed != 1 ";

        }
        if ($licenseonly) {
            $licenseonlysql = "c.id IN (
                             SELECT courseid FROM {local_iomad_courses}
                             WHERE licensed = 1
                           )
                           AND";
        }

        // Deal with shared option.
        if ($shared) {
            $sharedsql = " OR
                           c.id IN (
                              SELECT courseid FROM {local_iomad_courses}
                              WHERE shared = 1
                              $sharedlicsql
                              AND courseid NOT IN (
                                  SELECT courseid FROM {local_iomad_company_courses}
                                  WHERE companyid = :companyid2
                              )
                          )";
        }

        // Deal with groups option.
        if ($groups) {
            $groupsql = "c.groupmode != 0 AND";
        }

        // Deal with any courses which don't belong to any company.
        if ($noncompany) {
            $noncompanysql = " OR
                               c.id IN (
                                  SELECT id FROM {course}
                                  WHERE id NOT IN (
                                      SELECT courseid FROM {local_iomad_company_courses}
                                  )
                              )";
        }

        // Get the courses.
        $retcourses = $DB->get_records_sql("SELECT c.id, c.fullname, c.visible
                                            FROM {course} c
                                            WHERE
                                            $groupsql
                                            $unlicensesql
                                            $licenseonlysql
                                            c.id IN (
                                                SELECT courseid FROM {local_iomad_company_courses}
                                                WHERE companyid = :companyid
                                            )
                                            $sharedsql
                                            $noncompanysql
                                            $hiddensql
                                            ORDER BY c.fullname",
                                           ['companyid' => $this->id,
                                            'companyid2' => $this->id]);

        // Take care of any text filters.
        foreach ($retcourses as $courseid => $course) {
            $displayname = format_string($course->fullname, true, 1);
            if ($course->visible == 0) {
                if ($showhidden) {
                    $displayname = format_string($displayname . $hiddenstring, true, 1);
                } else {
                    unset($retcourses[$courseid]);
                    continue;
                }
            }
            $retcourses[$courseid] = $displayname;
        }

        // Add a default entry and return the courses.
        if ($default) {
            return ['0' => get_string('noselection', 'form')] + $retcourses;
        } else {
            return $retcourses;
        }
    }

    /**
     * Get a menu of course groups.
     *
     * @param int $courseid
     * @return void
     */
    public function get_course_groups_menu(int $courseid): array {
        global $DB;

        $retgroups = $DB->get_records_sql_menu("SELECT g.id, g.description
                                                FROM {groups} g
                                                JOIN {local_iomad_company_course_groups} ccg
                                                ON (g.id = ccg.groupid)
                                                WHERE ccg.companyid = :companyid
                                                AND ccg.courseid = :courseid",
                                               ['companyid' => $this->id,
                                                'courseid' => $courseid]);

        return ['0' => get_string('noselection', 'form')] + $retgroups;
    }

    /**
     * Check if a user can use this license to access a course..
     *
     * @param integer $licenseid
     * @param integer $courseid
     * @param integer $userid
     * @return bool
     */
    public static function license_ok_to_use(int $licenseid, int $courseid, int $userid): bool {
        global $DB, $CFG;

        // Check if the course is associated to any learning path.
        if (!$DB->get_records('block_iomad_learningpath_courses', ['courseid' => $courseid])) {
            return true;
        }

        // Check if the license is associated to a learning path.
        if (!$learningpath = $DB->get_record_sql("SELECT lp.*
                                                  FROM {block_iomad_learningpath} lp
                                                  JOIN {block_iomad_learningpath_users} lpu ON (lp.id = lpu.pathid)
                                                  WHERE lp.licenseid = :licenseid
                                                  AND lpu.userid = :userid",
                                                 ['licenseid' => $licenseid,
                                                  'userid' => $userid])) {
            return true;
        }

        // Check if the group is sequenced.
        if (!$groupinfo = $DB->get_record_sql(
            "SELECT lpc.*
             FROM {iomad_learninpathcourse}
             JOIN {block_iomad_learningpath_groups} lpg ON (lpc.groupid = lpg.id)
             WHERE lpc.courseid = :courseid
             AND lpc.path = :learningpath
             AND lpg.sequence = 1",
            ['courseid' => $courseid,
             'learningpath' => $learningpath->id])) {
            return true;
        }

        // Check if the user has met all the conditions.
        $groupcourses = $DB->get_records('block_iomad_learningpath_courses', ['groupid' => $groupinfo->groupid], 'sequence ASC');
        foreach ($groupcourses as $groupcourse) {
            // Is this the next course?
            if ($groupcourse->courseid == $courseid) {
                return true;
            }
            // If not, is it completed?
            if ($DB->get_record(
                'local_iomad_tracks',
                [
                    'userid' => $userid,
                    'courseid' => $courseid,
                    'licenseid' => $licenseid,
                    'timecompleted' => null,
                ])) {
                return false;
            }
        }
        // Return true by default.
        return true;
    }

    // Shared course stuff.

    /**
     * Create a company group for this course id
     *
     * @param integer $companyid
     * @param integer $courseid
     * @param [type] $groupdata
     * @return integer
     */
    public static function create_company_course_group(int $companyid, int $courseid, $groupdata = null ): int {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/group/lib.php');

        // Creates a company group within a shared course.
        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
        if (empty($groupdata)) {
            $data = (object) [];
            $data->timecreated  = time();
            $data->timemodified = $data->timecreated;
            $data->name = $company->shortname;
            $data->description = get_string('coursegroup', 'block_iomad_company_admin') . $company->name;
            $data->courseid = $courseid;
        } else if (!empty($groupdata->groupid)) {
            // Already exists so we are updating it.
            $grouprecord = $DB->get_record('groups', ['id' => $groupdata->groupid], '*', MUST_EXIST);
            $DB->set_field('groups', 'description', $groupdata->description, ['id' => $grouprecord->id]);
            return $grouprecord->id;
        } else {
            $data = (object) [];
            $data->timecreated  = time();
            $data->timemodified = $data->timecreated;
            $data->name = $company->shortname . ' - ' . $groupdata->description;
            $data->description = $groupdata->description;
            $data->courseid = $courseid;
        }

        // Create the group record.
        $groupid = groups_create_group($data);

        // Create the pivot table entry.
        $grouppivot = [];
        $grouppivot['companyid'] = $companyid;
        $grouppivot['courseid'] = $courseid;
        $grouppivot['groupid'] = $groupid;

        // Write the data to the DB.
        if (!$DB->insert_record('local_iomad_company_course_groups', $grouppivot)) {
            throw new moodle_exception(get_string('cantcreatecompanycoursegroup', 'block_iomad_company_admin'));
        }
        return $groupid;
    }

    /**
     * Get the course group name for the company in this course id
     *
     * @param integer $companyid
     * @param integer $courseid
     * @return string
     */
    public static function get_company_groupname(int $companyid, int $courseid): string {
        global $DB;
        // Gets the company course groupname.
        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
        if (!$companygroup = $DB->get_record('local_iomad_company_course_groups', ['companyid' => $companyid,
                                                                          'courseid' => $courseid,
                                                                          'name' => $company->shortname])) {
            // Not got one, create a default.
            $companygroup->groupid = self::create_company_course_group($companyid, $courseid);
        }
        // Get the group information.
        $groupinfo = $DB->get_record('groups', ['id' => $companygroup->groupid]);
        return $groupinfo->name;
    }

    /**
     * Get the course group for the company for the course id
     *
     * @param integer $companyid
     * @param integer $courseid
     * @return object
     */
    public static function get_company_group(int $companyid, int $courseid): object {
        global $DB;

        $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);

        // Gets the company course groupname.
        if (!$companygroup = $DB->get_record_sql(
            "SELECT ccg.*
             FROM {local_iomad_company_course_groups} ccg
             JOIN {groups} g
             ON (ccg.groupid = g.id)
             WHERE ccg.companyid = :companyid
             AND ccg.courseid = :courseid
             AND g.name = :name",
            ['companyid' => $companyid,
             'courseid' => $courseid,
             'name' => $company->shortname])) {
            // Not got one, create a default.
            $companygroup = (object) [];
            $companygroup->groupid = self::create_company_course_group($companyid, $courseid);
        }
        // Get the group information.
        $groupinfo = $DB->get_record('groups', ['id' => $companygroup->groupid]);
        return $groupinfo;
    }

    /**
     * Add a company user to a shared course company group
     *
     * @param integer $courseid
     * @param integer $userid
     * @param integer $companyid
     * @param integer $groupid
     * @param bool $clear
     * @return void
     */
    public static function add_user_to_shared_course(int $courseid,
                                                     int $userid,
                                                     int $companyid,
                                                     int $groupid = 0,
                                                     bool $clear = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        if (!empty($clear)) {
            // Clear the user from all groups.
            self::remove_user_from_shared_course($courseid, $userid, $companyid);
        }

        // Adds a user to a shared course.
        if (empty($groupid)) {
            $company = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
            // Get the group id.
            if (!$groupinfo = $DB->get_record_sql(
                "SELECT ccg.*
                 FROM {local_iomad_company_course_groups} ccg
                 JOIN {groups} g
                 ON (ccg.groupid = g.id)
                 WHERE ccg.companyid = :companyid
                 AND ccg.courseid = :courseid
                 AND g.name = :name",
                ['companyid' => $companyid,
                 'courseid' => $courseid,
                 'name' => $company->shortname])) {
                $groupid = self::create_company_course_group($companyid, $courseid);
            } else {
                $groupid = $groupinfo->groupid;
            }
        }

        // Add the user to the group.
        groups_add_member($groupid, $userid);
    }

    /**
     * Remove a company user from a shared course company group
     *
     * @param integer $courseid
     * @param integer $userid
     * @param integer $companyid
     * @return void
     */
    public static function remove_user_from_shared_course(int $courseid, int $userid, int $companyid) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/group/lib.php');

        // Removes a user from a shared course.
        // Get the group id.
        if (!$groups = $DB->get_records_sql(
            "SELECT gm.groupid
             FROM {groups_members} gm
             JOIN {groups} g
             ON (gm.groupid = g.id)
             WHERE g.courseid = :courseid
             AND gm.userid = :userid",
            ['userid' => $userid,
             'courseid' => $courseid])) {
            return;  // Dont need to remove them.
        } else {
            foreach ($groups as $group) {
                // Remove the user from the group.
                groups_remove_member($group->groupid, $userid);
            }
        }
    }

    /**
     * Delete a shared course company group.
     *
     * @param integer $companyid
     * @param object $course
     * @param bool $oktounenroll
     * @param integer $groupid
     * @return string|bool
     */
    public static function delete_company_course_group(int $companyid,
                                                       object $course,
                                                       bool $oktounenroll=false,
                                                       int $groupid = 0): string|bool {
        global $DB;
        // Removes a company group within a shared course.
        // Get the group.
        if ($group = self::get_company_group($companyid, $course->id)) {
            if (empty($groupid) || $groupid == $group->id) {
                // Check there are no members of the group unless oktounenroll.
                if (!$DB->get_records('local_iomad_company_course_groups', ['groupid' => $group->id]) ||
                    $oktounenroll) {
                    // Delete the group.
                    $DB->delete_records('groups', ['id' => $group->id]);
                    $DB->delete_records('local_iomad_company_course_groups', ['companyid' => $companyid,
                                                                  'groupid' => $group->id,
                                                                  'courseid' => $course->id]);
                    self::remove_course($course, $companyid);
                    return true;
                } else {
                    return "usersingroup";
                }
            } else {
                // Move everyone to the default company group.
                if ($groupusers = $DB->get_records('groups_members', ['groupid' => $groupid])) {
                    foreach ($groupusers as $user) {
                        groups_add_member($group->id, $user->userid);
                        groups_remove_member($groupid, $user->userid);
                    }
                }
                $DB->delete_records('groups', ['id' => $groupid]);
                $DB->delete_records('local_iomad_company_course_groups', ['groupid' => $groupid]);
            }
        }

        return true;
    }

    /**
     * Add all company users to a shared course company group
     *
     * @param integer $companyid
     * @param integer $courseid
     * @return void
     */
    public static function company_users_to_company_course_group(int $companyid, int $courseid) {
        global $DB, $CFG;
        // Adds all the users to a company group within a shared course.

        require_once($CFG->dirroot.'/group/lib.php');

        // Get the group.
        if (!$groupid = self::get_company_group($companyid, $courseid)) {
            $groupid = self::create_company_course_group($companyid, $courseid);
        }
        // This is used for a course which is becoming shared.
        // All current course enrolled users to this company group.
        if ($users = $DB->get_records_sql("SELECT ue.userid
                                           FROM {user_enrolments} ue
                                           JOIN {enrol} e ON (ue.enrolid = e.id)
                                           WHERE e.courseid = :courseid",
                                          ['courseid' => $courseid])) {
            foreach ($users as $user) {
                if ($DB->get_record('user', ['id' => $user->userid])) {
                    groups_add_member($groupid, $user->userid);
                }
            }
        }
    }

    /**
     * Remove all company users and groups from a course
     *
     * @param integer $companyid
     * @param integer $courseid
     * @return void
     */
    public static function unenrol_company_from_course(int $companyid, int $courseid) {
        global $DB;

        // Store the current time.
        $timenow = time();

        // Get the company users.
        $companydepartment = self::get_company_parentnode($companyid);
        $companyusers = self::get_recursive_department_users($companydepartment->id);

        // Is theer a company course group?
        if ($group = self::get_company_group($companyid, $courseid)) {
            // End all enrolments now..
            [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($companyusers),
                                                        SQL_PARAMS_NAMED,
                                                        'compuids');
            $sqlparams['courseid'] = $courseid;

            // Do we have any users in the course?
            if ($users = $DB->get_records_sql(
                "SELECT *
                 FROM {user_enrolments} ue
                 JOIN {enrol} e ON (ue.enrolid = e.id)
                 WHERE e.courseid = :courseid
                 AND ue.userid {$insql}",
                $sqlparams)) {

                // End their enrolment.
                foreach ($users as $user) {
                    $user->timeend = $timenow;
                    $DB->update_record('user_enrolments', $user);
                }
            }
            // Get rid of any company course groups.
            $DB->delete_records('local_iomad_company_course_groups', ['groupid', $group]);
        }

        // Remove the shared course from the company.
        $DB->delete_records('local_iomad_company_shared_courses', ['courseid' => $courseid,
                                                       'companyid' => $companyid]);
    }

    /**
     * Set the company user theme
     *
     * @param string $theme
     * @return void
     */
    public function update_theme(string $theme) {
        global $DB;

        // Get the company users.
        $users = $this->get_all_user_ids();

        // Update their theme.
        foreach ($users as $userid) {
            if ($user = $DB->get_record('user', ['id' => $userid])) {
                $user->theme = $theme;
                $DB->update_record('user', $user);
            }
        }
    }

    /**
     * Suspend or Unsuspend a company and all of it's users
     *
     * @param bool $suspend
     * @return void
     */
    public function suspend(bool $suspend = true) {
        global $DB;

        // Get the company users.
        $users = $this->get_all_user_ids();

        // Update the users.
        foreach ($users as $userid) {
            if ($user = $DB->get_record('user', ['id' => $userid])) {
                // Does the user belong to another company?
                if ($DB->count_records('local_iomad_company_users', ['userid' => $userid]) > 1 ) {
                    // Belongs to more than one company.  Skip.
                    continue;
                }
                if (! $DB->get_record('local_iomad_company_users', ['userid' => $user->id,
                                                        'companyid' => $this->id,
                                                        'suspended' => 1])) {
                    $user->suspended  = $suspend;
                    $DB->update_record('user', $user);
                }
                if (!empty($suspend)) {
                    \core\session\manager::destroy_user_sessions($user->id);
                }
            }
        }

        // Set the suspend field for the company.
        $DB->set_field('local_iomad_companies', 'suspended', $suspend, ['id' => $this->id]);

        // Deal with child companies.
        $childcompanies = $this->get_child_companies_recursive();
        if (!empty($childcompanies)) {
            foreach ($childcompanies as $childcomprec) {

                $childcompany = new company($childcomprec->id);
                $childcompany->suspend($suspend);
            }
        }
    }

    /**
     * Terminates a company, removing all course access and licenses for
     * all of it's users
     *
     * @return bool
     */
    public function terminate(): bool {
        global $DB;

        $runtime = time();

        try {
            $transaction = $DB->start_delegated_transaction();

            // Update all of the company licenes to have an end-date of now.
            $DB->set_field('local_iomad_company_licenses', 'expirydate', time(), ['companyid' => $this->id]);

            // Get the company users.
            $users = $this->get_all_user_ids();

            // Update the users.
            foreach ($users as $userid) {
                if ($user = $DB->get_record('user', ['id' => $userid])) {
                    // Does the user belong to another company?
                    if ($DB->count_records('local_iomad_company_users', ['userid' => $userid]) > 1 ) {
                        // Belongs to more than one company.  Skip.
                        continue;
                    }
                    // Terminate all of their enrolments.
                    $sqlwhere = "userid = :userid
                                 AND courseid = :courseid
                                 AND companyid = :companyid
                                 AND coursecleared = 0
                                 AND timecompleted > 0";
                    $sqlparams = [
                        'userid' => $userid,
                        'companyid' => $this->id,
                        'courseid' => $courseid,
                    ];
                    $usercourses = $DB->get_records_select('local_iomad_tracks', $sqlwhere, $sqlparams, '', 'id');
                    foreach ($usercourses as $licrec) {
                        // Remove this specific record.
                        company_user::delete_user_course($userid, $courseid, 'autodelete', $licrec->id);
                    }
                }
            }

            // Set the isterminated field for the company.
            $DB->set_field('local_iomad_companies', 'isterminated', true, ['id' => $this->id]);

            // Deal with local_iomad_track lines too.
            $DB->set_field('local_iomad_tracks', 'timeenrolled', $runtime, ['companyid' => $this->id, 'timeenrolled' => null]);
            $DB->set_field('local_iomad_tracks', 'timestarted', $runtime, ['companyid' => $this->id, 'timestarted' => null]);
            $DB->set_field('local_iomad_tracks', 'timecompleted', $runtime, ['companyid' => $this->id, 'timecompleted' => null]);

            // Deal with child companies.
            $childcompanies = $this->get_child_companies_recursive();
            if (!empty($childcompanies)) {
                foreach ($childcompanies as $childcomprec) {

                    $childcompany = new company($childcomprec->id);
                    $childcompany->terminate();
                }
            }

            // All OK commit the transaction.
            $transaction->allow_commit();
            return true;

            // Create an event for this.  This handles the actual lifting.
            $eventother = ['companyid' => $company->id];
            $event = company_terminated::create(
                [
                    'context' => context_company::instance($company->id),
                    'objectid' => $company->id,
                    'userid' => $USER->id,
                    'other' => $eventother,
                ]
            );
            $event->trigger();

        } catch (Exception $e) {
            $transaction->rollback($e);
            return false;
        }
    }

    /**
     * Enable or disable ecommerce access for a company
     *
     * @param bool $ecommerce
     * @return void
     */
    public function ecommerce(bool $ecommerce) {
        global $CFG, $DB;

        // Set the ecommerce field for the company.
        $DB->set_field('local_iomad_companies', 'ecommerce', $ecommerce, ['id' => $this->id]);

        // Do we have to update it on the external site?
        if (!empty($ecommerce) && $CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            // Let's set up the adhoc task.
            $task = new \block_iomad_company_admin\task\companyenableshop();
            $task->queue_task($this->id);
        }
    }

    /**
     * Checks the passed department id is valid for the company id
     *
     * @param integer $companyid
     * @param integer $departmentid
     * @return bool
     */
    public static function check_valid_department(int $companyid, int $departmentid): bool {
        global $DB;

        if ($DB->get_record('local_iomad_company_departments', ['id' => $departmentid,
                                           'companyid' => $companyid])) {
            return true;
        } else {
            // Is the department within a child company of the currently selected company?
            $thiscompany = new company($companyid);
            if ($childcompanies = $thiscompany->get_child_companies_recursive()) {
                foreach ($childcompanies as $childid => $ignore) {
                    if ($DB->get_record('local_iomad_company_departments', ['id' => $departmentid,
                                                       'companyid' => $childid])) {
                        return true;
                    }
                }
            }
            return false;
        }
        // Shouldn't get here.  Return a false in case.
        return false;
    }

    /**
     * Check that a userid and department id is valid for the companyid
     *
     * @param integer $companyid
     * @param integer $userid
     * @param integer $deparmentid
     * @return bool
     */
    public static function check_valid_user(int $companyid, int $userid, int $deparmentid=0): bool {
        global $DB, $USER;

        // If current user is a site admin or they have appropriate capabilities then they can.
        if (is_siteadmin($userid) ||
            iomad::has_capability('block/iomad_company_admin:company_add', context_company::instance($companyid))) {
            return true;
        }

        // Set default result.
        $result = false;

        if (!empty($departmentid) &&
            $DB->get_record('local_iomad_company_users', ['departmentid' => $departmentid,
                                              'companyid' => $companyid,
                                              'userid' => $userid])) {
            $result = true;
        } else if ($DB->get_records('local_iomad_company_users', ['companyid' => $companyid,
                                                      'userid' => $userid])) {
            $result = true;
        } else {
            // Is the user in a child company?
            $company = new company($companyid);
            $children = $company->get_child_companies_recursive();
            if (!empty($children)) {
                [$insql, $sqlparams] = $DB->get_in_or_equal(array_keys($children),
                                                            SQL_PARAMS_NAMED,
                                                            'cids');
                $sqlwhere = "userid = :userid
                             AND companyid {$insql}";
                $sqlparams['userid'] = $userid;
                if ($DB->get_records_select('local_iomad_company_users', $sqlwhere, $sqlparams, '', 'id')) {
                    $result = true;
                }
            }
        }

        // Return the result.
        return $result;
    }

    /**
     * Check if a userid is suspended the companyid
     *
     * @param integer $companyid
     * @param integer $userid
     * @return bool
     */
    public static function check_user_suspended(int $companyid, int $userid): bool {
        global $DB;

        if ($DB->get_records('local_iomad_company_users', ['companyid' => $companyid,
                                               'userid' => $userid,
                                               'suspended' => 1])) {
            return true;
        }

        return false;
    }

    /**
     * Check if the number of new users to be added to the company brings it above the maximum
     *
     * @param integer $new
     * @return bool
     */
    public function check_usercount(int $new = 0): bool {
        global $DB, $USER;

        // Set the default.
        $result = false;

        // Get the company maximum.
        if (empty($this->companyrecord->maxusers)) {
            $result = true;
        } else {
            // Get the current number of users.
            // Deal with any parent companies.
            // all companies?
            $companysql = "";
            $sqlparams = ['companyid' => $this->id];
            if ($parentslist = $this->get_parent_companies_recursive()) {
                [$insql, $inparams] = $DB->get_in_or_equal(array_keys($parentslist),
                                                           SQL_PARAMS_NAMED,
                                                           'pcids');
                $companysql = "AND u.id NOT IN (
                                   SELECT userid
                                   FROM {local_iomad_company_users}
                                   WHERE companyid {$insql}
                               )";
                $sqlparams = $sqlparams + $inparams;
            }

            $usercount = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id)
                                                 FROM {local_iomad_company_users} cu
                                                 JOIN {user} u ON (cu.userid = u.id)
                                                 WHERE cu.companyid = :companyid
                                                 AND u.deleted = 0
                                                 AND u.suspended = 0
                                                 $companysql",
                                                $sqlparams);
            if ($usercount + $new <= $this->companyrecord->maxusers) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Checks that the current USER can edit a user id in company id
     *
     * @param integer $companyid
     * @param integer $userid
     * @return bool
     */
    public static function check_canedit_user(int $companyid, int $userid): bool {
        global $DB, $USER;

        // Can't edit an admin user here.
        if (is_siteadmin($userid)) {
            return false;
        }

        // If current user is a site admin or they have appropriate capabilities then they can.
        if (is_siteadmin($USER->id) ||
            iomad::has_capability('block/iomad_company_admin:company_add', context_company::instance($companyid))) {
            return true;
        }

        // Get my companyid.
        $mycompanyid = iomad::get_my_companyid(context_system::instance());

        // If it doesn't match then return false.
        if ($mycompanyid != $companyid) {
            return false;
        }

        // Check if the user is in the company.
        if ($userrec = $DB->get_record('local_iomad_company_users', ['companyid' => $companyid,
                                                              'userid' => $userid])) {

            // Check the current user is a manager or not and what levels they can edit.
            if ($manrec = $DB->get_record('local_iomad_company_users', ['companyid' => $companyid,
                                                                 'userid' => $USER->id])) {
                if (empty($manrec->managertype)) {
                    return false;
                } else if ($manrec->managertype == 2 && $userrec->managertype == 1) {
                    return false;
                } else {
                    return true;
                }
            }
        }

        // Return a false by default.
        return false;
    }

    /**
     * Check that a license id is valid for the company id
     *
     * @param integer $companyid
     * @param integer $licenseid
     * @return bool
     */
    public static function check_valid_company_license(int $companyid, int $licenseid): bool {
        global $DB;

        if ($DB->get_record('local_iomad_company_licenses', ['companyid' => $companyid,
                                               'id' => $licenseid])) {
            return true;
        }

        // Is it a child license?
        $company = new company($companyid);

        // Return a false by default.
        return $company->is_child_license($licenseid);
    }

    /**
     * Check if the current USER can manage a passed user id
     *
     * @param integer $userid
     * @return bool
     */
    public static function check_can_manage(int $userid): bool {
        global $DB, $USER;

        // Set the companyid.
        $companyid = iomad::get_my_companyid(context_system::instance(), false);

        if ($companyid > 0) {
            // Get the company context.
            $companycontext = context_company::instance($companyid);
        } else {
            $companycontext = context_system::instance();
        }

        // If this is ourselves or we can see all users then we can see this one.
        if ($USER->id == $userid ||
            iomad::has_capability('block/iomad_company_admin:editallusers', $companycontext)) {
            return true;
        }

        // Get the list of users.
        $myusers = self::get_my_users($companyid);

        // If the user is in the list, return true.
        if (!empty($myusers[$userid])) {
            return true;
        }

        // Return a false by default.
        return false;
    }

    /**
     * Get any auto assigned department ID used on user creation
     *
     * @param object $user
     * @return integer
     */
    public function get_auto_department(object $user): int {
        global $DB;

        $topdepartment = self::get_company_parentnode($this->id);
        $departmentid = $topdepartment->id;

        // Check if there is a different match.
        if (!empty($this->companyrecord->departmentprofileid)) {
            // Get the profile field.
            if ($field = $DB->get_record('user_info_field', ['id' => $this->companyrecord->departmentprofileid])) {
                $fieldname = 'profile_field_' . $field->shortname;
                profile_load_data($user);
                if (!empty($user->$fieldname)) {
                    if ($department = $DB->get_record(
                        'local_iomad_company_departments',
                        ['name' => $user->$fieldname, 'companyid' => $this->id])) {
                        $departmentid = $department->id;
                    }
                }
            }
        }

        // Return departmentid.
        return $departmentid;
    }

    /**
     * Automatically enrol users on defined auto enrolment courses
     *
     * @param object $user
     * @param integer $due
     * @return void
     */
    public function autoenrol(object $user, int $due = 0) {
        global $DB, $CFG, $SESSION, $SITE, $OUTPUT;

        // Did we get passed a user id?
        if (!is_object($user)) {
            $userrec = $DB->get_record('user', ['id' => $user]);
            $user = $userrec;
        }

        // Get all of the courses the company can see.
        $companycoursesql = "";
        $sqlparams = ['companyid' => $this->id];
        if ($companycourses = $this->get_menu_courses(true, false, false, false, false)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($companycourses),
                                                       SQL_PARAMS_NAMED,
                                                       'cids');
            $companycoursesql = " AND courseid {$insql}";
            $sqlparams = $sqlparams + $inparams;
        }

        // Get the courses which are assigned to the company which are not licensed.
        $courses = $DB->get_records_sql("SELECT DISTINCT courseid
                                         FROM {local_iomad_company_course_options}
                                         WHERE companyid = :companyid
                                         AND autoenrol = 1
                                         $companycoursesql",
                                        $sqlparams);

        // Get all of the licensed courses.
        $licensecourses = $DB->get_records('local_iomad_courses', ['licensed' => 1], '', 'courseid');

        // Are we also enrolling to unattached courses?
        if (!empty(get_config('local_iomad', 'signup_autoenrol_unassigned'))) {
            $unassignedcourses = $DB->get_records_sql("SELECT id AS courseid
                                                       FROM {course}
                                                       WHERE id NOT IN (
                                                        SELECT courseid FROM {local_iomad_company_courses}
                                                       )
                                                       AND id != :siteid",
                                                      ['siteid' => $SITE->id]);
            $courses = $courses + $unassignedcourses;
        }

        // Enrol the user onto them.
        $errors = '';
        foreach ($courses as $addcourse) {
            if ($course = $DB->get_record('course', ['id' => $addcourse->courseid, 'visible' => 1], 'id, fullname')) {

                // Check if this is a licensed course.
                if (!empty($licensecourses[$course->id])) {
                    if ($newlicense = company_user::auto_allocate_license($user->id, $this->id, $course->id)) {

                        // Create an event.
                        $eventother = ['licenseid' => $newlicense->licenseid,
                                       'issuedate' => time(),
                                       'duedate' => $due];
                        $event = user_license_assigned::create([
                            'context' => context_course::instance($course->id),
                            'objectid' => $newlicense->id,
                            'courseid' => $course->id,
                            'userid' => $user->id,
                            'other' => $eventother,
                        ]);
                        $event->trigger();
                    } else {
                        $errors .= format_string($course->fullname) . " ";
                    }
                } else {
                    company_user::enrol($user, [$course->id], $this->id, false, false, $due);

                    // Send an email.
                    emailtemplate::send(
                        'user_added_to_course',
                        [
                            'course' => $course,
                            'user' => $user,
                            'due' => time(),
                        ]
                    );
                }
            }
        }
        if (!empty($errors)) {
            // We only want to be notified of this once as sometimes this gets run multiple times.
            if (empty($SESSION->autoenrolonuser) || $SESSION->autoenrolonuser != $user->id) {
                $notify = new notification(get_string('autoenrolmentfailed', 'block_iomad_company_admin', $errors),
                                           notification::NOTIFY_WARNING);
                echo $OUTPUT->render($notify);
                $SESSION->autoenrolonuser = $user->id;
            }
        }
    }

    /**
     * Add any optional company profile field information to the passed items.
     *
     * @param array $headers
     * @param array $columns
     * @param string $selectsql
     * @param string $fromsql
     * @param array $sqlparams
     * @return void
     */
    public function add_company_extrafields(array &$headers,
                                            array &$columns,
                                            string &$selectsql,
                                            string &$fromsql,
                                            array &$sqlparams) {
        global $CFG, $DB;

        // Set some defaults.
        $extrafields = [];
        if (!empty($CFG->iomad_report_fields)) {
            foreach (explode(',', get_config('local_iomad', 'report_fields')) as $extrafield) {
                $extrafields[$extrafield] = (object) [];
                $extrafields[$extrafield]->name = $extrafield;
                if (strpos($extrafield, 'profile_field') !== false) {

                    // Its an optional profile field.
                    $profilefield = $DB->get_record(
                        'user_info_field',
                        ['shortname' => str_replace('profile_field_', '', $extrafield)]
                    );
                    if ($profilefield->categoryid == $this->companyrecord->profileid ||
                        !$DB->get_record('company', ['profileid' => $profilefield->categoryid])) {
                        $extrafields[$extrafield]->title = $profilefield->name;
                        $extrafields[$extrafield]->fieldid = $profilefield->id;
                    } else {
                        unset($extrafields[$extrafield]);
                    }
                } else {
                    $extrafields[$extrafield]->title = get_string($extrafield);
                }
            }
        }

        // Process any extra fields.
        foreach ($extrafields as $extrafield) {
            $headers[] = $extrafield->title;
            if (empty($extrafield->fieldid)) {

                $selectsql .= ", u." . $extrafield->name . " AS u" . $extrafield->name;
                $columns[] = 'u' . $extrafield->name;
            } else {
                // Its a profile field.
                $selectsql .= ", P" . $extrafield->fieldid . ".data AS " . $extrafield->name;
                $fromsql .= " LEFT JOIN {user_info_data} P" . $extrafield->fieldid .
                            " ON (u.id = P" . $extrafield->fieldid . ".userid
                              AND P" . $extrafield->fieldid . ".fieldid = :p" . $extrafield->fieldid . "fieldid )";
                $sqlparams["p" . $extrafield->fieldid . "fieldid"] = $extrafield->fieldid;
                $columns[] = $extrafield->name;
            }
        }
    }

    // Competencies stuff.

    /**
     * Associates a competency framework to a company
     *
     * @param integer $companyid
     * @param integer $frameworkid
     * @return void
     */
    public static function add_competency_framework(int $companyid, int $frameworkid) {
        global $DB;

        if (!$DB->record_exists('local_iomad_company_comp_frameworks', ['companyid' => $companyid,
                                                            'frameworkid' => $frameworkid])) {
            $DB->insert_record('local_iomad_company_comp_frameworks', ['companyid' => $companyid,
                                                           'frameworkid' => $frameworkid]);
        }
    }

    /**
     * Remove a competency framework from a company
     *
     * @param integer $companyid
     * @param integer $frameworkid
     * @return void
     */
    public static function remove_competency_framework(int $companyid, int $frameworkid) {
        global $DB;

        $DB->delete_records('local_iomad_company_comp_frameworks', ['companyid' => $companyid,
                                                        'frameworkid' => $frameworkid]);
    }

    /**
     * Associates a competency template to a company
     *
     * @param integer $companyid
     * @param integer $templateid
     * @return void
     */
    public static function add_competency_template(int $companyid, int $templateid) {
        global $DB;

        if (!$DB->record_exists('local_iomad_company_comp_templates', ['companyid' => $companyid,
                                                           'templateid' => $templateid])) {
            $DB->insert_record('local_iomad_company_comp_templates', ['companyid' => $companyid,
                                                          'templateid' => $templateid]);
        }
    }

    /**
     * Remove a competency template from a company
     *
     * @param integer $companyid
     * @param integer $templateid
     * @return void
     */
    public static function remove_competency_template(int $companyid, int $templateid) {
        global $DB;

        $DB->delete_records('local_iomad_company_comp_templates', ['companyid' => $companyid,
                                                       'templateid' => $templateid]);
    }

    /**
     * Check if this email template is available for use
     *
     * @param string $templatename
     * @param integer $managertype
     * @return bool
     */
    public function email_template_is_enabled(string $templatename, int $managertype = 0): bool {
        global $DB;

        if ($DB->get_records('local_iomad_email_templates',
        [
            'companyid' => $this->id,
            'name' => $templatename,
            'disabled' => 0,
            'disabledmanager' => 0,
            'disabledsupervisor' => 0,
        ])) {
            // Fully enabled for the company.
            return true;
        }

        if ($DB->get_records('local_iomad_email_templates',
        [
            'companyid' => $this->id,
            'name' => $templatename,
            'disabled' => 1,
        ])) {
            // Disabled for the company.
            return false;
        }

        if ($managertype == 1) {
            if ($DB->get_records('local_iomad_email_templates',
            [
                'companyid' => $this->id,
                'name' => $templatename,
                'disabledmanager' => 1,
            ])) {
                // Disabled for the company.
                return false;
            }
        }

        if ($managertype == 2) {
            if ($DB->get_records('local_iomad_email_templates',
            [
                'companyid' => $this->id,
                'name' => $templatename,
                'disabledsupervisor' => 1,
            ])) {
                // Disabled for the company.
                return false;
            }
        }

        // Default is true as the template may not have been defined outside of defaults.
        return true;
    }

    /**
     * Set the company SMTP settings.
     *
     * @param object $mailer
     * @param integer $companyid
     * @return object
     */
    public static function set_company_mailer(object $mailer, int $companyid = 0): object {
        global $CFG;

        if (empty($companyid)) {
            $companyid = iomad::get_my_companyid(context_system::instance(), false);
        }

        // Did we get anything?
        if (empty($companyid)) {
            return $mailer;
        }

        // Deal withe the potential settings that could be changed.
        $possiblesettings = ['type' => 'smtphosts',
                             'SMTPDebug' => 'debugsmtp',
                             'SMTPSecure' => 'smtpsecure',
                             'AuthType' => 'smtpauthtype',
                             'Username' => 'smtpuser',
                             'Password' => 'smtppass',
                             'noreplyaddress' => 'noreplyaddress',
                             'DKIM_selector' => 'emaildkimselector'];

        // Make the changes.
        foreach ($possiblesettings as $name => $possiblesetting) {
            $value = iomad::get_config('', $possiblesetting);
            if ($name != 'type') {
                if ($name == 'Username' && !empty($value)) {
                    $mailer->SMTPAuth = true;
                }
                $mailer->$name = $value;
            } else {
                if ($possiblesetting == 'qmail') {
                    // Use Qmail system.
                    $mailer->isQmail();
                } else if (empty($value) && empty(iomad::get_config('', 'smpthosts'))) {
                    // Use PHP mail() = sendmail.
                    $mailer->isMail();
                } else {
                    // Use SMTP directly.
                    $mailer->isSMTP();
                    if (!empty($CFG->debugsmtp) && (!empty($CFG->debugdeveloper))) {
                        $mailer->SMTPDebug = 3;
                    }

                    // Specify mail server.
                    $mailer->Host = $value;
                }
            }
        }

        if (empty($mailer->noreplyaddress)) {
            $noreplyaddressdefault = 'noreply@' . get_host_from_url($CFG->wwwroot);
            $mailer->noreplyaddress = empty(iomad::get_config('', 'noreplyaddress')) ?
                                      $noreplyaddressdefault :
                                      iomad::get_config('', 'noreplyaddress');
        }

        return $mailer;
    }

    /**
     * Update plugin settings for given plugin and postfix.
     *
     * @param pluginname
     * @param postfix
     * @return bool
     */
    public static function update_plugin(string $pluginname, string $postfix): bool {
        if (empty($pluginname) || empty ($postfix)) {
            return false;
        }
        $settings = [];
        $currentsettings = [];
        $pluginsettings = get_config($pluginname);
        foreach ($pluginsettings as $setting => $value) {
            if (preg_match('/'.$postfix.'$/', $setting)) {
                $currentsettings[$setting] = $value;
            } else if ($setting == 'version' || preg_match('/_\d+$/', $setting)) {
                continue;
            } else {
                $settings[$setting] = $value;
            }
        }
        // Should have all the defaults - strip any we have config for.
        foreach ($currentsettings as $current => $dump) {
            $current = str_replace($postfix, "", $current);
            unset($settings[$current]);
        }
        // Set any missing.
        foreach ($settings as $setting => $value) {
            set_config($setting . $postfix, $value, $pluginname);
        }

        return true;
    }

    /***  Event Handlers  ***/

    /**
     * Triggered via company_created event.
     *
     * @param company_created $event
     * @return bool true on success.
     */
    public static function company_created(company_created $event): bool {
        global $CFG, $DB, $USER;

        $companyid = $event->other['companyid'];
        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $companyid])) {
            return true;
        }

        if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            // Fire off the payload to the external site.
            if (empty($CFG->commerce_admin_enableall) && empty($company->ecommerce)) {
                return true;
            }

            require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
            iomad_commerce::update_company($company, $company);
        }

        return true;
    }

    /**
     * Triggered via company_suspended event.
     *
     * @param company_suspended $event
     * @return bool true on success.
     */
    public static function company_suspended(company_suspended $event): bool {
        global $DB, $CFG;

        $companyid = $event->other['companyid'];

        if (empty($companyid) || !$companyrecord = $DB->get_record('local_iomad_companies', ['id' => $companyid])) {
            return true;
        }

        $suspendcompany = new company($companyid);
        $suspendcompany->suspend(true);

        // Get the company managers.
        $managers = $DB->get_records('local_iomad_company_users', ['companyid' => $companyid, 'managertype' => 1]);
        foreach ($managers as $manager) {
            $user = $DB->get_record('user', ['id' => $manager->userid]);
            emailtemplate::send(
                'company_suspended',
                [
                    'company' => $suspendcompany,
                    'user' => $user,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via company_unsuspended event.
     *
     * @param company_unsuspended $event
     * @return bool true on success.
     */
    public static function company_unsuspended(company_unsuspended $event): bool {
        global $DB, $CFG;

        $companyid = $event->other['companyid'];

        if (empty($companyid) ||
            !$companyrecord = $DB->get_record('local_iomad_companies', ['id' => $companyid])) {
            return true;
        }

        $suspendcompany = new company($companyid);
        $suspendcompany->suspend(false);

        // Get the company managers.
        $managers = $DB->get_records('local_iomad_company_users', ['companyid' => $companyid, 'managertype' => 1]);
        foreach ($managers as $manager) {
            $user = $DB->get_record('user', ['id' => $manager->userid]);
            emailtemplate::send(
                'company_unsuspended',
                [
                    'company' => $suspendcompany,
                    'user' => $user,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via company_updated event.
     *
     * @param company_updated $event
     * @return bool true on success.
     */
    public static function company_updated(company_updated $event): bool {
        global $CFG, $DB;

        $companyid = $event->other['companyid'];
        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $companyid])) {
            return true;
        }

        $oldcompany = json_decode( $event->other['oldcompany']);

        if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            // Fire off the payload to the external site.
            require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
            iomad_commerce::update_company($company, $oldcompany);
        }

        // Check if the company name has changed.
        if ($company->name != $oldcompany->name) {
            $coursecat = $DB->get_record('course_categories', ['id' => $company->coursecategoryid], '*', MUST_EXIST);
            $coursecat->name = $company->name;
            $DB->update_record('course_categories', $coursecat);
            fix_course_sortorder();
        }

        return true;
    }

    /**
     * Triggered via company_updated event.
     *
     * @param company_deleted $event
     * @return bool true on success.
     */
    public static function company_deleted(company_deleted $event): bool {
        global $CFG, $DB;

        $companyid = $event->other['companyid'];
        if (!$company = $DB->get_record('local_iomad_companies', ['id' => $companyid])) {
            return true;
        }

        // Update the company name to mark it that its being deleted.
        $company->name = get_string('deletingcompany', 'block_iomad_company_admin', $company->name);
        $DB->update_record('local_iomad_companies', $company);

        // Set up the adhoc task to do this.
        // Fire off the adhoc task to populate this new field correctly.
        $task = new task\deletecompanytask();
        $task->set_custom_data(['companyid' => $companyid]);
        \core\task\manager::queue_adhoc_task($task, true);

        return true;
    }

    /**
     * Triggered via competency_framework_created event.
     *
     * @param competency_framework_created $event
     * @return bool true on success.
     */
    public static function competency_framework_created(competency_framework_created $event): bool {
        $data = $event->get_data();
        if (!empty($data['companyid'])) {
            self::add_competency_framework($data['companyid'], $event->objectid);
        }
        return true;
    }

    /**
     * Triggered via competency_framework_deleted event.
     *
     * @param competency_framework_deleted $event
     * @return bool true on success.
     */
    public static function competency_framework_deleted(competency_framework_deleted $event): bool {
        global $DB;
        $DB->delete_records('local_iomad_company_comp_frameworks', ['frameworkid' => $event->objectid]);
        return true;
    }

    /**
     * Triggered via competency_template_created event.
     *
     * @param competency_template_created $event
     * @return bool true on success.
     */
    public static function competency_template_created(competency_template_created $event): bool {

        $data = $event->get_data();
        if (!empty($data['companyid'])) {
            self::add_competency_template($data['companyid'], $event->objectid);
        }
        return true;
    }

    /**
     * Triggered via competency_template_deleted event.
     *
     * @param competency_template_deleted $event
     * @return bool true on success.
     */
    public static function competency_template_deleted(competency_template_deleted $event): bool {
        global $DB;
        $DB->delete_records('local_iomad_company_comp_templates', ['templateid' => $event->objectid]);
        return true;
    }

    /**
     * Triggered via course_completed event.
     *
     * @param course_completed $event
     * @return bool true on success.
     */
    public static function course_completed(course_completed $event): bool {

        // Current processing has been moved elsewhere.
        return true;
    }

    /**
     * Signup event handler for 'user_created'
     * For specified authentication types (only), try and add this user
     * to a company using various logic.
     *
     * @param mixed $user user id or user object
     */
    public static function signup_user_created($user) {
        global $CFG, $DB;

        // Check if we already have the user object.
        if (is_int($user) || is_string($user)) {
            $user = $DB->get_record('user', ['id' => $user], '*', MUST_EXIST);
        }

        // If the user is already in a company then we do nothing more
        // as this came from the self sign up pages.
        if ($usercompanies = $DB->get_records_sql("SELECT DISTINCT companyid,id
                                                   FROM {local_iomad_company_users}
                                                   WHERE userid = :userid
                                                   ORDER BY id DESC",
                                                   ['userid' => $user->id], 0, 1)) {

            $userrecord = array_shift($usercompanies);
            $company = new company($userrecord->companyid);

            // Deal with any auto enrolments.
            if (get_config('local_iomad', 'signup_autoenrol')) {
                $company->autoenrol($user);
            }

            // Need to set the manager type.
            $userrecord->managertype = 0;
            $userrecord->educator = 0;

            // Do we have a company department profile field?
            $autodepartmentid = $company->get_auto_department($user);
            self::upsert_company_user($user->id,
                                      $userrecord->companyid,
                                      $autodepartmentid,
                                      $userrecord->managertype,
                                      $userrecord->educator,
                                      false,
                                      true);

            return true;
        }

        // For the rest of this the plugin needs to be enabled.
        if (!get_config('local_iomad', 'signup_enable')) {
            return true;
        }

        // If not 'email' auth then we are not interested.
        if (empty(get_config('local_iomad', 'signup_auth')) ||
            !in_array($user->auth, explode(',', get_config('local_iomad', 'signup_auth')))) {
            return true;
        }

        // Check if user is already in a company.
        // E.g. if this has already been handled.
        if (!$company = self::by_userid($user->id, true)) {

            // Get context.
            $context = context_system::instance();
            $found = false;

            // Check if we have a company id from the URL or SESSION.
            $companyid = iomad::get_my_companyid($context, false);
            if (!empty($companyid)) {
                $company = new company($companyid);
                $found = true;
            }

            if (!$found) {
                // Check if we have a domain already for this users email address.
                list($dump, $emaildomain) = explode('@', $user->email);
                if ($domaininfo = $DB->get_record_sql("SELECT * FROM {local_iomad_company_domains}
                                                       WHERE " . $DB->sql_compare_text('domain') .
                                                       " = '" .
                                                       $DB->sql_compare_text($emaildomain)."'")) {
                    // Get company.
                    $company = new company($domaininfo->companyid);
                    $found = true;
                }
            }
            if (!$found && !empty(get_config('local_iomad', 'signup_company'))) {
                // Do we have a default company to assign?
                // Get company.
                $company = new company(get_config('local_iomad', 'signup_company'));
                $found = true;
            }
            if ($found) {
                // Get the full user information for department matching.
                profile_load_data($user);

                // Do we have a company department profile field?
                $autodepartmentid = $company->get_auto_department($user);

                // Do we have a role to assign?
                $managertype = 0;
                if (!empty(get_config('local_iomad', 'signup_role'))) {
                    // Get role.
                    if ($role = $DB->get_record('role', ['id' => get_config('local_iomad', 'signup_role')], '*', MUST_EXIST)) {
                        if ($role->shortname == 'companymanager') {
                            $managertype = 1;
                        } else if ($role->shortname == 'companydepartmentmanager') {
                            $managertype = 2;
                        } else if ($role->shortname == 'companyreporter') {
                            $managertype = 4;
                        }
                    }
                }

                // Assign the user to the company.
                $company->assign_user_to_company($user->id, $autodepartmentid, $managertype);

                // Deal with company defaults.
                $defaults = $company->get_user_defaults();
                foreach ($defaults as $index => $value) {
                    $user->$index = $value;
                }

                // Save the user details.
                $DB->update_record('user', $user);
                profile_save_data($user);

                // Force the company theme in case it's not already been done.
                $DB->set_field('user', 'theme', $company->get_theme(), ['id' => $user->id]);
            }
        }

        return true;
    }

    /**
     * Triggered via user_created event.
     *
     * @param user_created $event
     * @return bool true on success.
     */
    public static function user_created(user_created $event): bool {
        global $DB, $CFG;

        $userid = $event->objectid;
        $companyid = $event->companyid;
        $user = $DB->get_record('user', ['id' => $userid]);
        $user->manager = 'no';

        if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            if (!empty($companyid)) {
                $company = new company($companyid);
                if (empty($CFG->commerce_admin_enableall) && empty($company->companyrecord->ecommerce)) {
                    return true;
                }
                if (empty($user->company)) {
                    $user->company = $company->get_name();
                }
            }

            // Fire off the payload to the external site.
            require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
            iomad_commerce::update_user($user, $company->id);
        }

        return true;
    }

    /**
     * Triggered via user_updated event.
     *
     * @param user_updated $event
     * @return bool true on success.
     */
    public static function user_updated(user_updated $event): bool {
        global $DB, $CFG;

        $userid = $event->relateduserid;
        $user = $DB->get_record('user', ['id' => $userid]);

        // Get all of the companies the user is tied to.
        $usercompanies = $DB->get_records_sql("SELECT DISTINCT c.*
                                               FROM {local_iomad_companies} c
                                               JOIN {local_iomad_company_users} cu ON (c.id = cu.companyid)
                                               WHERE cu.userid = :userid",
                                              ['userid' => $userid]);

        foreach ($usercompanies as $usercompany) {
            $company = new company($usercompany->id);

            if ($DB->get_record(
                'local_iomad_company_users',
                ['userid' => $user->id, 'companyid' => $usercompany->id, 'managertype' => 1])) {
                $user->manager = 'yes';
                $user->country = $usercompany->country;
                $user->city = $usercompany->city;
                $user->adress = "";
            } else {
                $user->manager = 'no';
            }
            $user->company = $company->get_name();

            if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
                if (empty($CFG->commerce_admin_enableall) && empty($usercompany->ecommerce)) {
                    continue;
                }

                // Fire off the payload to the external site.
                require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
                iomad_commerce::update_user($user, $company->id);
            }
        }

        // Check if we are assigning department by profile field.
        if (!empty(get_config('local_iomad', 'sync_department')) &&
            get_config('local_iomad', 'sync_department') == 2) {
            // Check if there is a department with the name given.
            $current = $DB->count_records(
                'local_iomad_company_departments',
                ['companyid' => $company->id, 'name' => $user->department]
            );
            if ($current == 1) {
                // Assign them to the department.
                $department = $DB->get_record(
                    'local_iomad_company_departments',
                    ['companyid' => $company->id, 'name' => $user->department]
                );
                if ($currentdepartments = $DB->get_records(
                    'local_iomad_company_users',
                    ['companyid' => $company->id, 'userid' => $user->id])) {
                    // We only do anything if they are in one department.
                    if (count($currentdepartments) == 1) {
                        foreach ($currentdepartments as $currentdepartment) {
                            // Only move them if they are not a company manager.
                            if ($currentdepartment->managertype != 1) {
                                $DB->set_field(
                                    'local_iomad_company_users',
                                    'departmentid',
                                    $department->id,
                                    ['id' => $currentdepartment->id]
                                );
                            }
                        }
                    }
                } else {
                    // Assign them to this department as they aren't in any yet.
                    self::assign_user_to_department($department->id, $user->id);
                }
            } else if ($current == 0) {
                // Department doesn't exist yet. Create it!
                $shortname = str_replace(' ', '-', $user->department);
                $shortname = preg_replace('/[^A-Za-z0-9\-]/', '', $shortname);
                $topdepartment = self::get_company_parentnode($company->id);
                self::create_department(0, $company->id, $user->department, $shortname, $topdepartment->id);
                // Get the new department.
                $department = $DB->get_record(
                    'local_iomad_company_departments',
                    ['companyid' => $company->id, 'shortname' => $shortname]
                );
                if ($currentdepartments = $DB->get_records(
                    'local_iomad_company_users',
                    ['companyid' => $company->id, 'userid' => $user->id])) {
                    // We only do anything if they are in one department.
                    if (count($currentdepartments) == 1) {
                        foreach ($currentdepartments as $currentdepartment) {
                            // Only move them if they are not a company manager.
                            if ($currentdepartment->managertype != 1) {
                                $DB->set_field(
                                    'local_iomad_company_users',
                                    'departmentid',
                                    $department->id,
                                    ['id' => $currentdepartment->id]
                                );
                            }
                        }
                    }
                } else {
                    // Assign them to this department as they aren't in any yet.
                    self::assign_user_to_department($department->id, $user->id);
                }
            }
        }

        return true;
    }

    /**
     * Triggered via user_suspended event.
     *
     * @param user_suspended $event
     * @return bool true on success.
     */
    public static function user_suspended(user_suspended $event): bool {
        global $DB;

        $userid = $event->objectid;
        $timestamp = time();

        $user = $DB->get_record('user', ['id' => $userid]);

        // Get all of the companies the user is tied to.
        $usercompanies = $DB->get_records_sql("SELECT DISTINCT companyid
                                               FROM {local_iomad_company_users}
                                               WHERE userid = :userid",
                                              ['userid' => $userid]);

        foreach ($usercompanies as $usercompany) {
            $company = new company($usercompany->companyid);
            company_user::suspend($userid, $usercompany->companyid);
            emailtemplate::send(
                'user_suspended',
                [
                    'company' => $company,
                    'user' => $user,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via user_suspended event.
     *
     * @param user_suspended $event
     * @return bool true on success.
     */
    public static function user_unsuspended(user_unsuspended $event): bool {
        global $DB;

        $userid = $event->objectid;
        $timestamp = time();

        $user = $DB->get_record('user', ['id' => $userid]);

        // Get all of the companies the user is tied to.
        $usercompanies = $DB->get_records_sql("SELECT DISTINCT companyid
                                               FROM {local_iomad_company_users}
                                               WHERE userid = :userid",
                                              ['userid' => $userid]);

        foreach ($usercompanies as $usercompany) {
            $company = new company($usercompany->companyid);
            company_user::suspend($userid, $usercompany->companyid);
            emailtemplate::send(
                'user_unsuspended',
                [
                    'company' => $company,
                    'user' => $user,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via user_enrolment_created event.
     *
     * @param user_enrolment_created $event
     * @return bool true on success.
     */
    public static function user_enrolment_created(user_enrolment_created $event): bool {
        global $DB, $CFG;

        $userid = $event->relateduserid;
        $timestamp = $event->timecreated;
        $courseid = $event->courseid;
        $companyid = $event->companyid;

        // Were we passed a companyid?
        if (empty($companyid)) {
            return true;
        }

        // Is this a shared course?
        if ($DB->get_record('local_iomad_courses', ['courseid' => $courseid, 'shared' => 0])) {
            // It's not - return.
            return true;
        }

        // Does this course have groups?
        if (!$DB->get_record('course', ['id' => $courseid, 'groupmode' => 1])) {
            // It doesn't - return.
            return true;
        }

        // Add the user to the appropriate course group.
        self::add_user_to_shared_course($courseid, $userid, $companyid);

        return true;
    }

    /**
     * Triggered via user_licensed_used event.
     *
     * @param user_license_used $event
     * @return bool true on success.
     */
    public static function user_license_used(user_license_used $event): bool {
        global $DB, $CFG;

        $userid = $event->userid;
        $timestamp = $event->timecreated;
        $courseid = $event->courseid;
        $licenserecordid = $event->objectid;
        $licenseid = $event->other['licenseid'];

        // Does this record exist?
        if (!$userlicenserecord = $DB->get_record('local_iomad_company_license_users', ['id' => $licenserecordid])) {
            // It's not - return.
            return true;
        }

        // Does this record exist?
        if (!$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            // It's not - return.
            return true;
        }

        // Does this license allocation have a specified group?
        if (empty($userlicenserecord->groupid)) {
            // It doesn't - return.
            return true;
        }

        // Add the user to the specific groupid.
        self::add_user_to_shared_course($courseid, $userid, $licenserecord->companyid, $userlicenserecord->groupid, true);

        return true;
    }

    /**
     * Triggered via user_deleted event.
     *
     * @param user_deleted $event
     * @return bool true on success.
     */
    public static function user_deleted(user_deleted $event): bool {
        global $DB, $CFG;

        $userid = $event->objectid;
        $timestamp = time();

        // Get all of the companies the user is tied to.
        $usercompanies = $DB->get_records_sql("SELECT DISTINCT companyid
                                               FROM {local_iomad_company_users}
                                               WHERE userid = :userid",
                                              ['userid' => $userid]);

        foreach ($usercompanies as $usercompany) {
            $company = new company($usercompany->companyid);
            $company->unassign_user_from_company($userid);

            $user = $DB->get_record('user', ['id' => $userid]);
            emailtemplate::send(
                'user_deleted',
                [
                    'company' => $company,
                    'user' => $user,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via company_user_assigned event.
     *
     * @param company_user_assigned $event
     * @return bool true on success.
     */
    public static function company_user_assigned(company_user_assigned $event): bool {
        global $DB, $CFG;

        $companyid = $event->objectid;
        $userid = $event->userid;
        $company = new company($companyid);
        $companyrec = $DB->get_record('local_iomad_companies', ['id' => $companyid]);
        $user = $DB->get_record('user', ['id' => $userid]);

        // We only care if its a company manager.
        if ($event->other['usertype'] == 1) {
            $childcompanies = $company->get_child_companies_recursive();

            foreach ($childcompanies as $child) {
                $childcompany = new company($child->id);
                $childcompany->assign_user_to_company($userid, 0, $event->other['usertype'], true);
            }
        }

        if ($CFG->commerce_enable_external && !empty($CFG->commerce_externalshop_url)) {
            if (empty($CFG->commerce_admin_enableall) && empty($company->companyrecord->ecommerce)) {
                return true;
            }
            // Fire off the payload to the external site.
            require_once($CFG->dirroot . '/blocks/iomad_commerce/locallib.php');
            iomad_commerce::assign_user($user, $companyrec->name, $companyrec->id);
        }

        return true;
    }

    /**
     * Triggered via company_user_unassigned event.
     *
     * @param company_user_unassigned $event
     * @return bool true on success.
     */
    public static function company_user_unassigned(company_user_unassigned $event): bool {
        global $DB;

        // We only care if its a company manager.
        if ($event->other['usertype'] != 1) {
            return true;
        }
        $companyid = $event->objectid;
        $userid = $event->userid;

        $company = new company($companyid);
        $childcompanies = $company->get_child_companies_recursive();

        foreach ($childcompanies as $child) {
            $childcompany = new company($child->id);
            $childcompany->unassign_user_from_company($userid, true);
        }

        return true;
    }

    /**
     * Triggered via user_license_assigned event.
     *
     * @param user_license_assigned $event
     * @return bool true on success.
     */
    public static function user_license_assigned(user_license_assigned $event): bool {
        global $DB, $CFG;

        $userid = $event->userid;
        $userlicid = $event->objectid;
        $licenseid = $event->other['licenseid'];
        $courseid = $event->courseid;
        $duedate = $event->other['duedate'];
        if (!empty($event->other['noemail'])) {
            $noemail = true;
        } else {
            $noemail = false;
        }

        if (!$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            return true;
        }

        if (!$course = $DB->get_record('course', ['id' => $courseid])) {
            return true;
        }

        if (!$user = $DB->get_record('user', ['id' => $userid])) {
            return true;
        }

        $license = (object) [];
        $license->length = $licenserecord->validlength;
        $license->valid = userdate($licenserecord->expirydate, get_config('local_iomad', 'date_format'));
        $license->startdate = userdate($licenserecord->startdate, get_config('local_iomad', 'date_format'));

        if (!$noemail) {
            // Send out the email.
            $company = new company($licenserecord->companyid);
            emailtemplate::send(
                'license_allocated',
                [
                    'course' => $course,
                    'company' => $company,
                    'user' => $user,
                    'due' => $duedate,
                    'license' => $license,
                ]
            );
        }

        // Update the license usage.
        self::update_license_usage($licenseid);

        // Check if we need to warn about usage.
        $licenserec = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid]);
        if ($licenserec->used / $licenserec->allocation * 100 > 90) {
            // Get the company managers.
            if ($companymanagers = $DB->get_records_sql(
                "SELECT u.*
                 FROM {user} u
                 JOIN {local_iomad_company_users} cu ON (u.id = cu.userid)
                 WHERE u.deleted = 0
                 AND u.suspended = 0
                 AND cu.companyid = :companyid
                 AND cu.managertype = 1",
                ['companyid' => $company->id])) {
                foreach ($companymanagers as $companymanager) {
                    emailtemplate::send(
                        'licensepoolwarning',
                        [
                            'course' => $course,
                            'company' => $company,
                            'user' => $companymanager,
                            'license' => $license,
                        ]
                    );
                }
            }
        }

        // Is this an immediate license?
        if (!empty($licenserecord->instant)) {
            if (self::license_ok_to_use($licenseid, $courseid, $userid)) {
                if ($instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'license'])) {
                    // Enrol the user on the course.
                    $enrol = enrol_get_plugin('license');

                    // Enrol the user in the course.
                    // Is the license available yet and specifed time is before this?
                    if ((!empty($licenserecord->startdate) && $licenserecord->startdate > time()) &&
                        (!empty($duedate) && $licenserecord->startdate > $duedate)) {
                        // If not set up the enrolment from when it is.
                        $timestart = $licenserecord->startdate;
                    } else if (!empty($duedate)) {
                        // Start it when the emails are due to go out.
                        $timestart = $duedate;
                    } else {
                        // Otherwise start it now.
                        $timestart = time();
                    }

                    if ($licenserecord->type == 0 || $licenserecord->type == 2) {
                        // Set the timeend to be time start + the valid length for the license in days.
                        $timeend = $timestart + ($licenserecord->validlength * 24 * 60 * 60 );
                    } else {
                        // Set the timeend to be when the license runs out.
                        $timeend = $licenserecord->expirydate;
                    }

                    if ($licenserecord->type < 2) {
                        if (!is_enrolled(context_course::instance($instance->courseid), $user->id)) {
                            $enrol->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);
                        } else if ($completedrecords = $DB->get_records_select('local_iomad_tracks',
                                                                               "userid = :userid
                                                                                AND courseid = :courseid
                                                                                AND timecompleted IS NOT NULL
                                                                                AND coursecleared = 0
                                                                                AND licenseallocated != :timeallocated",
                                                                               ['userid' => $userid,
                                                                                'courseid' => $course->id,
                                                                                'timeallocated' => $event->timecreated])) {
                            // All previous attempts have been completed so enrol again.
                            foreach ($completedrecords as $completedrecord) {
                                // Complete any license allocations.
                                if ($licenserecord = $DB->get_record(
                                    'local_iomad_company_license_users',
                                    [
                                        'userid' => $completedrecord->userid,
                                        'courseid' => $completedrecord->courseid,
                                        'licenseid' => $completedrecord->licenseid,
                                        'issuedate' => $completedrecord->licenseallocated,
                                    ])) {
                                    if (empty($licenserecord->timecompleted)) {
                                        $DB->set_field(
                                            'local_iomad_company_license_users',
                                            'timecompleted',
                                            $timestart,
                                            [
                                                'id' => $licenserecord->id,
                                            ]
                                        );
                                    }
                                }
                                $DB->set_field('local_iomad_tracks', 'completedstop', 1, ['id' => $completedrecord->id]);
                            }
                            // Clear them from the course.
                            company_user::delete_user_course($user->id, $course->id, 'autodelete');

                            // Then re-enrol them.
                            $enrol->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend);
                        }
                    } else {
                        // Educator role.
                        if ($DB->get_record('local_iomad_courses', ['courseid' => $course->id, 'shared' => 0])) {
                            // Not shared.
                            $role = $DB->get_record('role', ['shortname' => 'companycourseeditor']);
                        } else {
                            // Shared.
                            $role = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
                        }
                        $enrol->enrol_user($instance, $user->id, $role->id, $timestart, $timeend);
                    }

                    // Get the userlicense record.
                    $userlicense = $DB->get_record('local_iomad_company_license_users', ['id' => $userlicid]);

                    // Update the userlicense record to mark it as in use.
                    $DB->set_field('local_iomad_company_license_users', 'isusing', 1, ['id' => $userlicense->id]);

                    // Fire an event to record this.
                    $eventother = ['licenseid' => $licenseid];
                    $event = user_license_used::create(
                        [
                            'context' => \context_course::instance($courseid),
                            'objectid' => $userlicense->id,
                            'courseid' => $instance->courseid,
                            'userid' => $user->id,
                            'other' => $eventother,
                        ]
                    );
                    $event->trigger();
                }
            }
        }

        return true;
    }

    /**
     * Triggered via user_license_unassigned event.
     *
     * @param user_license_unassigned $event
     * @return bool true on success.
     */
    public static function user_license_unassigned(user_license_unassigned $event): bool {
        global $DB, $CFG, $PAGE;

        require_once($CFG->dirroot . '/enrol/locallib.php');

        $userid = $event->userid;
        $licenseid = $event->other['licenseid'];
        $courseid = $event->courseid;

        if (!$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            return true;
        }

        if (!$course = $DB->get_record('course', ['id' => $courseid])) {
            return true;
        }

        if (!$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0, 'suspended' => 0])) {
            self::update_license_usage($licenseid);
            return true;
        }

        // Check if there is an enrolment in the course for this user/license.
        $manager = new course_enrolment_manager($PAGE, $course);
        if ($enrolments = $manager->get_user_enrolments($userid)) {
            foreach ($enrolments as $ue) {
                $manager->unenrol_user($ue);
            }
        }

        $license = (object) [];
        $license->length = $licenserecord->validlength;
        $license->valid = userdate($licenserecord->expirydate, get_config('local_iomad', 'date_format'));

        if ($emailrecs = $DB->get_records('local_iomad_emails', ['userid' => $user->id,
                                                    'courseid' => $course->id,
                                                    'templatename' => 'license_allocated',
                                                    'sent' => null])) {
            // Delete the email as it hasn't been sent.
            foreach ($emailrecs as $emailrec) {
                $DB->delete_records('local_iomad_emails', ['id' => $emailrec->id]);
            }
        } else {
            // Send out the email.
            emailtemplate::send('license_removed', [
                'course' => $course,
                'user' => $user,
                'license' => $license,
            ]);
        }

        // Update the license usage.
        self::update_license_usage($licenseid);

        return true;
    }

    /**
     * Triggered via company_license_created event.
     *
     * @param company_license_created $event
     * @return bool true on success.
     */
    public static function company_license_created(company_license_created $event): bool {
        global $DB, $CFG;

        $licenseid = $event->other['licenseid'];
        $parentid = $event->other['parentid'];

        if (!$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            return true;
        }

        // Deal with the human allocation.
        if (empty($licenserecord->program)) {
            $DB->set_field('local_iomad_company_licenses', 'humanallocation', $licenserecord->allocation, ['id' => $licenseid]);
        } else {
            $coursecount = $DB->count_records('local_iomad_company_license_courses', ['licenseid' => $licenseid]);
            $DB->set_field(
                'local_iomad_company_licenses',
                'humanallocation',
                $licenserecord->allocation / $coursecount,
                [
                    'id' => $licenserecord->id,
                ]);
        }

        // Update the license usage.
        if (!empty($parentid)) {
            self::update_license_usage($parentid);
        }

        // Get the company managers.
        $company = new company($licenserecord->companyid);
        $managers = $company->get_managers();
        foreach ($managers as $manager) {
            // Fire the email.
            emailtemplate::send(
                'company_licenseassigned',
                [
                    'user' => $manager,
                    'company' => $company,
                ]
            );
        }

        return true;
    }

    /**
     * Triggered via company_license_updated event.
     *
     * @param company_license_updated $event
     * @return bool true on success.
     */
    public static function company_license_updated(company_license_updated $event): bool {
        global $DB, $CFG;

        $licenseid = $event->other['licenseid'];
        $parentid = $event->other['parentid'];

        if (!$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $licenseid])) {
            return true;
        }

        if (!empty($licenserecord->program)) {
            // This is a program of courses.
            // If it's been updated we need to deal with any course changes.
            $currentcourses = $DB->get_records(
                'local_iomad_company_license_courses',
                ['licenseid' => $licenseid],
                null,
                'courseid'
            );
            $oldcourses = (array) json_decode($event->other['oldcourses'], true);

            // Check for courses being removed.
            foreach ($oldcourses as $oldcourse) {
                $oldcourseid = $oldcourse['courseid'];

                if (empty($currentcourses[$oldcourseid])) {
                    // Deal with enrolments.
                    if ($enrolments = $DB->get_records_sql(
                        "SELECT e.id
                         FROM {enrol} e
                         JOIN {local_iomad_company_license_courses} clc ON (
                             e.courseid = clc.courseid
                             AND e.status = 0
                         )
                         WHERE clc.licenseid = :licenseid
                         AND e.courseid = :courseid",
                        ['licenseid' => $licenseid, 'courseid' => $oldcourseid])) {
                        foreach ($enrolments as $enrolid) {
                            $DB->delete_records('user_enrolments', ['enrolid' => $enrolid->id]);
                        }
                    }
                    $DB->delete_records(
                        'local_iomad_company_license_users',
                        ['courseid' => $oldcourseid, 'licenseid' => $licenseid]
                    );
                }
            }

            foreach ($currentcourses as $currentcourse) {
                $currcourseid = $currentcourse->courseid;
                if (empty($oldcourses[$currcourseid])) {
                    // We have a new course.  Add everyone.
                    if ($licusers = $DB->get_records_sql(
                        "SELECT DISTINCT userid
                         FROM {local_iomad_company_license_users}
                         WHERE licenseid = :licenseid",
                        ['licenseid' => $licenseid])) {

                        foreach ($licusers as $licuser) {
                            $userlic = [
                                'licenseid' => $licenseid,
                                'userid' => $licuser->userid,
                                'isusing' => 0,
                                'courseid' => $currentcourse->courseid,
                                'issuedate' => time(),
                            ];
                            $userlicid = $DB->insert_record('local_iomad_company_license_users', $userlic);

                            // Is this an immediate license?
                            if (!empty($licenserecord->instant)) {
                                if (self::license_ok_to_use($licenseid, $currcourseid, $licuser->userid)) {
                                    if ($instance = $DB->get_record(
                                        'enrol',
                                        [
                                            'courseid' => $currentcourse->courseid,
                                            'enrol' => 'license',
                                        ])) {
                                        // Enrol the user on the course.
                                        $enrol = enrol_get_plugin('license');

                                        // Enrol the user in the course.
                                        // Is the license available yet?
                                        if (!empty($licenserecord->startdate) && $licenserecord->startdate > time()) {
                                            // If not set up the enrolment from when it is.
                                            $timestart = $licenserecord->startdate;
                                        } else {
                                            // Otherwise start it now.
                                            $timestart = time();
                                        }

                                        if ($licenserecord->type == 0 || $licenserecord->type == 2) {
                                            // Set the timeend to be time start + the valid length for the license in days.
                                            $timeend = $timestart + ($licenserecord->validlength * 24 * 60 * 60);
                                        } else {
                                            // Set the timeend to be when the license runs out.
                                            $timeend = $licenserecord->expirydate;
                                        }

                                        if ($licenserecord->type < 2) {
                                            $enrol->enrol_user($instance,
                                                               $licuser->userid,
                                                               $instance->roleid,
                                                               $timestart, $timeend);
                                        } else {
                                            // Educator role.
                                            if ($DB->get_record(
                                                'local_iomad_courses',
                                                [
                                                    'courseid' => $currentcourse->courseid,
                                                    'shared' => 0,
                                                ])) {
                                                // Not shared.
                                                $role = $DB->get_record('role', ['shortname' => 'companycourseeditor']);
                                            } else {
                                                // Shared.
                                                $role = $DB->get_record('role', ['shortname' => 'companycoursenoneditor']);
                                            }
                                            $enrol->enrol_user($instance, $licuser->userid, $role->id, $timestart, $timeend);
                                        }

                                        // Get the userlicense record.
                                        $userlicense = $DB->get_record('local_iomad_company_license_users', ['id' => $userlicid]);

                                        // Update the userlicense record to mark it as in use.
                                        $DB->set_field(
                                            'local_iomad_company_license_users',
                                            'isusing',
                                            1,
                                            ['id' => $userlicense->id]
                                        );

                                        // Fire an event to record this.
                                        $eventother = ['licenseid' => $licenseid];
                                        $event = user_license_used::create([
                                            'context' => \context_course::instance($currcourseid),
                                            'objectid' => $userlicense->id,
                                            'courseid' => $instance->courseid,
                                            'userid' => $licuser->userid,
                                            'other' => $eventother,
                                        ]);
                                        $event->trigger();
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (isset($event->other['programchange']) && $licenserecord->program == 1) {
                // We have switched from an ordinary license to a program license.
                // Get the users who have courses in this licenses.
                if ($licusers = $DB->get_records_sql(
                    "SELECT DISTINCT userid
                     FROM {local_iomad_company_license_users}
                     WHERE licenseid = :licenseid",
                    ['licenseid' => $licenseid])) {

                    foreach ($licusers as $licuser) {
                        foreach ($currentcourses as $currentcourse) {
                            // Check if they have a license allocated.
                            if (!$DB->get_record('local_iomad_company_license_users', [
                                'userid' => $licuser->userid,
                                'courseid' => $currentcourse->courseid,
                                'licenseid' => $licenseid,
                            ])) {
                                // If not, allocate it to them.
                                $userlic = [
                                    'licenseid' => $licenseid,
                                    'userid' => $licuser->userid,
                                    'isusing' => 0,
                                    'courseid' => $currentcourse->courseid,
                                    'issuedate' => time(),
                                ];
                                $DB->insert_record('local_iomad_company_license_users', $userlic);
                            }
                        }
                    }
                }
            }
        }

        // Update the license usage.
        self::update_license_usage($licenseid);

        // Deal with the parent.
        if (!empty($parentid)) {
            self::update_license_usage($parentid);
        }

        // Update the timeend for any users using this license.
        if (!empty($licenserecord->type)) {
            // This is a subscription license.
            // Update the enrolment end.
            if ($enrolments = $DB->get_records_sql(
                "SELECT ue.id
                 FROM {enrol} e
                 JOIN {user_enrolments} ue ON (e.id = ue.enrolid)
                 JOIN {local_iomad_company_license_courses} clc ON (
                     e.courseid = clc.courseid
                     AND e.status = 0
                 )
                 JOIN {local_iomad_company_license_users} clu ON (
                     ue.userid = clu.userid
                     AND clu.licenseid = clc.licenseid
                 )
                 WHERE clc.licenseid = :licenseid",
                ['licenseid' => $licenseid])) {
                foreach ($enrolments as $enrolid) {
                    $DB->set_field(
                        'user_enrolments',
                        'timeend',
                        $licenserecord->expirydate,
                        ['id' => $enrolid->id]
                    );
                }
            }
        }

        // Deal with any children.
        if ($children = $DB->get_records('local_iomad_company_licenses', ['parentid' => $licenseid])) {
            foreach ($children as $child) {
                // If not a program of courses, check if child courses are all still present in parent courses.
                if (!empty($currentcourses) && empty($licenserecord->program)) {
                    $childcourses = $DB->get_records(
                        'local_iomad_company_license_courses',
                        ['licenseid' => $child->id],
                        '',
                        'courseid'
                    );
                    $childparentcourses = array_intersect_key($childcourses, $currentcourses);
                    // Clear down all of them initially.
                    $DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $child->id]);
                    foreach ($childparentcourses as $selectedcourse) {
                        $DB->insert_record(
                            'local_iomad_company_license_courses',
                            [
                                'licenseid' => $child->id,
                                'courseid' => $selectedcourse->courseid,
                            ]);
                    }
                }
                // If parent license is for a program of courses, overwrite child license with parent course license allocations.
                if (!empty($currentcourses) && !empty($licenserecord->program)) {
                    // Clear down all of them initially.
                    $DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $child->id]);
                    foreach ($currentcourses as $selectedcourse) {
                        $DB->insert_record(
                            'local_iomad_company_license_courses',
                            [
                                'licenseid' => $child->id,
                                'courseid' => $selectedcourse->courseid,
                            ]);
                    }
                }

                // Deal with the allocation amount if courses changed.
                if (!empty($child->program)) {
                    $old = count($oldcourses);
                    $new = count($currentcourses);
                    if ($old != $new) {
                        $allocation = $child->allocation / $old * $new;
                        $child->allocation = $allocation;
                    }
                }

                // Deal with the human allocation.
                if (empty($child->program)) {
                    $child->humanallocation  = $child->allocation;
                } else {
                    $child->humanallocation  = $child->allocation / $new;
                }

                // Did we change anything else about the license?
                $child->validlength = $licenserecord->validlength;
                $child->expirydate = $licenserecord->expirydate;
                $child->type = $licenserecord->type;
                $child->startdate = $licenserecord->startdate;
                $child->instant = $licenserecord->instant;
                $DB->update_record('local_iomad_company_licenses', $child);

                // Create an event to deal with any child license allocations.
                $eventother = $event->other;
                $eventother['licenseid'] = $child->id;
                $eventother['parentid'] = $licenseid;
                $eventother['oldcourses'] = json_encode($oldcourses);

                $event = company_license_updated::create([
                    'context' => context_company::instance($licenserecord->companyid),
                    'userid' => $event->userid,
                    'objectid' => $child->id,
                    'other' => $eventother,
                ]);
                $event->trigger();
            }
        }

        return true;
    }

    /**
     * Triggered via company_license_deleted event.
     *
     * @param company_license_deleted $event
     * @return bool true on success.
     */
    public static function company_license_deleted(company_license_deleted $event): bool {
        global $DB, $CFG;

        $parentid = $event->other['parentid'];

        if (empty($parentid) || !$licenserecord = $DB->get_record('local_iomad_company_licenses', ['id' => $parentid])) {
            return true;
        }
        $DB->delete_records('local_iomad_company_license_courses', ['licenseid' => $event->other['licenseid']]);

        // Update the license usage.
        self::update_license_usage($parentid);

        return true;
    }

    /**
     * Triggered via user_course_expired event.
     *
     * @param company_license_created $event
     * @return bool true on success.
     */
    public static function user_course_expired(user_course_expired $event): bool {
        global $DB, $CFG;

        $userid = $event->userid;
        $courseid = $event->courseid;
        $action = 'autodelete';

        $companyid = $event->companyid;
        if ($DB->count_records_select('local_iomad_tracks',
                                      "userid = :userid
                                       AND courseid = :courseid
                                       AND coursecleared = 0
                                       AND timecompleted IS NULL",
                                      ['userid' => $userid,
                                       'courseid' => $courseid]) > 0) {

            // Get the specific record for this company.
            $licrecs = $DB->get_records_select('local_iomad_tracks',
                                               "userid = :userid
                                                AND courseid = :courseid
                                                AND companyid = :companyid
                                                AND coursecleared = 0
                                                AND timecompleted > 0",
                                               ['userid' => $userid,
                                                'companyid' => $companyid,
                                                'courseid' => $courseid]);
            foreach ($licrecs as $licrec) {

                // Remove this specific record.
                company_user::delete_user_course($userid, $courseid, $action, $licrec->id);
            }
        } else {
            // Delete them.
            company_user::delete_user_course($userid, $courseid, $action);
        }

        return true;
    }

    /**
     * Triggered via iomadcustompage_deleted event.
     *
     * @param iomadcustompage_deleted $event
     * @return bool true on success.
     */
    public static function iomadcustompage_deleted(iomadcustompage_deleted $event): bool {
        global $DB, $CFG;
        // Delete any company pages which match this event object id.
        $DB->delete_records('local_iomad_company_pages', ['pageid' => $event->objectid]);
        return true;
    }

    /**
     * Send a user's supervisor a warning email that a user hasn't completed a course.
     *
     * @param user object
     * @param course object
     * @return bool true on success.
     */
    public static function send_supervisor_warning_email(object $user, object $course): bool {
        global $DB, $CFG;

        $companyinfo = self::get_company_byuserid($user->id);
        $company = new company($companyinfo->id);
        $template = new emailtemplate(
            'completion_warn_supervisor',
            [
                'course' => $course,
                'user' => $user,
                'company' => $company,
            ]
        );

        // Is this enabled for this company?
        if (!$company->email_template_is_enabled('completion_warn_supervisor', 2)) {
            return true;
        }

        // Do we have a supervisor?
        if ($supervisoremails = self::get_usersupervisor($user->id)) {
            foreach ($supervisoremails as $supervisoremail) {
                $params = (object) [];
                $params->fullname = $course->fullname;
                $params->firstname = $user->firstname;
                $params->lastname = $user->lastname;
                $mail = get_mailer();

                $supportuser = core_user::get_support_user();
                if (!empty($CFG->supportemail)) {
                    $supportuser->email = $CFG->supportemail;
                }
                if ($CFG->supportname) {
                    $supportuser->firstname = $CFG->supportname;
                }

                $subject = $user->email . ": " . $template->subject();
                $messagetext = $template->body();

                $mail->Sender = iomad::get_config('', 'noreplyaddress', $company->id);
                $mail->FromName = $supportuser->firstname;
                $mail->From     = iomad::get_config('', 'noreplyaddress', $company->id);
                if (empty($CFG->divertallemailsto)) {
                    $mail->Subject = substr($subject, 0, 900);
                } else {
                    $mail->Subject = substr('[DIVERTED ' . $supervisoremail . '] ' . $subject, 0, 900);
                    $supervisoremail = $CFG->divertallemailsto;
                }

                $mail->addAddress($supervisoremail, '');

                // Set word wrap.
                $mail->WordWrap = 79;

                $mail->Body = "\n$messagetext\n";
                $mail->IsHTML();

                if (empty($CFG->noemailever)) {
                    $mail->send();
                }
            }
        }
        return true;
    }

    /**
     * Send a user's supervisor a warning email that a user hasn't started a course.
     *
     * @param user object
     * @param course object
     * @return bool true on success.
     */
    public static function send_supervisor_not_started_warning_email(object $user, object $course): bool {
        global $DB, $CFG;

        $companyinfo = self::get_company_byuserid($user->id);
        $company = new company($companyinfo->id);
        $template = new emailtemplate(
            'course_not_started_warning',
            [
                'course' => $course,
                'user' => $user,
                'company' => $company,
            ]
        );

        // Is this enabled for this company?
        if (!$company->email_template_is_enabled('course_not_started_warning', 2)) {
            return true;
        }

        // Do we have a supervisor?
        if ($supervisoremails = self::get_usersupervisor($user->id)) {
            foreach ($supervisoremails as $supervisoremail) {
                $params = (object) [];
                $params->fullname = $course->fullname;
                $params->firstname = $user->firstname;
                $params->lastname = $user->lastname;
                $mail = get_mailer();

                $supportuser = core_user::get_support_user();
                if (!empty($CFG->supportemail)) {
                    $supportuser->email = $CFG->supportemail;
                }
                if ($CFG->supportname) {
                    $supportuser->firstname = $CFG->supportname;
                }

                $subject = $user->email . ": " . $template->subject();
                $messagetext = $template->body();

                $mail->Sender = $CFG->noreplyaddress;
                $mail->FromName = $supportuser->firstname;
                $mail->From     = $CFG->noreplyaddress;
                if (empty($CFG->divertallemailsto)) {
                    $mail->Subject = substr($subject, 0, 900);
                } else {
                    $mail->Subject = substr('[DIVERTED ' . $supervisoremail . '] ' . $subject, 0, 900);
                    $supervisoremail = $CFG->divertallemailsto;
                }

                $mail->addAddress($supervisoremail, '');

                // Set word wrap.
                $mail->WordWrap = 79;

                $mail->Body = "\n$messagetext\n";
                $mail->IsHTML();
                if (empty($CFG->noemailever)) {
                    $mail->send();
                }
            }
        }
        return true;
    }

    /**
     * Send a user's supervisor a warning email that a user's training is expiring.
     *
     * @param user object
     * @param course object
     * @return bool true on success.
     */
    public static function send_supervisor_expiry_warning_email(object $user, object $course): bool {
        global $DB, $CFG;

        $companyinfo = self::get_company_byuserid($user->id);
        $company = new company($companyinfo->id);
        $supervisortemplate = new emailtemplate(
            'completion_expiry_warn_supervisor',
            [
                'course' => $course,
                'user' => $user,
                'company' => $company,
            ]);

        // Is this enabled for this company?
        if (!$company->email_template_is_enabled('completion_expiry_warn_supervisor', 2)) {
            return true;
        }

        // Do we have a supervisor?
        if ($supervisoremails = self::get_usersupervisor($user->id)) {
            foreach ($supervisoremails as $supervisoremail) {
                $params = (object) [];
                $params->fullname = $course->fullname;
                $params->firstname = $user->firstname;
                $params->lastname = $user->lastname;
                $mail = get_mailer();

                $supportuser = core_user::get_support_user();
                if (!empty($CFG->supportemail)) {
                    $supportuser->email = $CFG->supportemail;
                }
                if ($CFG->supportname) {
                    $supportuser->firstname = $CFG->supportname;
                }

                $subject = $user->email . ": " . $template->subject();
                $messagetext = $template->body();

                $mail->Sender = $CFG->noreplyaddress;
                $mail->FromName = $supportuser->firstname;
                $mail->From     = $CFG->noreplyaddress;
                if (empty($CFG->divertallemailsto)) {
                    $mail->Subject = substr($subject, 0, 900);
                } else {
                    $mail->Subject = substr('[DIVERTED ' . $supervisoremail . '] ' . $subject, 0, 900);
                    $supervisoremail = $CFG->divertallemailsto;
                }

                $mail->addAddress($supervisoremail, '');

                // Set word wrap.
                $mail->WordWrap = 79;

                $mail->Body = "\n$messagetext\n";
                $mail->IsHTML();

                if (empty($CFG->noemailever)) {
                    $mail->send();
                }

            }
        }
        return true;
    }
}
