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

// Define the Iomad menu items that are defined by this plugin

function block_iomad_company_admin_menu() {

        return array(
            'addcompany' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('createcompany', 'block_iomad_company_admin'),
                'url' => 'company_edit_form.php?createnew=1',
                'cap' => 'block/iomad_company_admin:company_add',
                'icondefault' => 'newcompany',
                'style' => 'company',
                'icon' => 'fa-building',
                'iconsmall' => 'fa-plus-square'
            ),
            'editcompany' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('editcompany', 'block_iomad_company_admin'),
                'url' => 'company_edit_form.php',
                'cap' => 'block/iomad_company_admin:company_edit',
                'icondefault' => 'editcompany',
                'style' => 'company',
                'icon' => 'fa-building',
                'iconsmall' => 'fa-edit'
            ),
            'managecompanies' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('managecompanies', 'block_iomad_company_admin'),
                'url' => 'editcompanies.php',
                'cap' => 'block/iomad_company_admin:company_add_child',
                'icondefault' => 'editcompany',
                'style' => 'company',
                'icon' => 'fa-building',
                'iconsmall' => 'fa-gear'
            ),
            'editdepartments' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('editdepartment', 'block_iomad_company_admin'),
                'url' => 'company_departments.php',
                'cap' => 'block/iomad_company_admin:edit_departments',
                'icondefault' => 'managedepartment',
                'style' => 'department',
                'icon' => 'fa-group',
                'iconsmall' => 'fa-gear'
            ),
            'userprofiles' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('userprofiles', 'block_iomad_company_admin'),
                'url' => 'company_user_profiles.php',
                'cap' => 'block/iomad_company_admin:company_user_profiles',
                'icondefault' => 'optionalprofiles',
                'style' => 'user',
                'icon' => 'fa-user',
                'iconsmall' => 'fa-info-circle'
            ),
            'restrictcapabilities' => array(
                'category' => 'CompanyAdmin',
                'tab' => 1,
                'name' => get_string('restrictcapabilities', 'block_iomad_company_admin'),
                'url' => 'company_capabilities.php',
                'cap' => 'block/iomad_company_admin:restrict_capabilities',
                'icondefault' => 'useredit',
                'style' => 'user',
                'icon' => 'fa-user',
                'iconsmall' => 'fa-gear'
            ),
            'createuser' => array(
                'category' => 'UserAdmin',
                'tab' => 2,
                'name' => get_string('createuser', 'block_iomad_company_admin'),
                'url' => 'company_user_create_form.php',
                'cap' => 'block/iomad_company_admin:user_create',
                'icondefault' => 'usernew',
                'style' => 'user',
                'icon' => 'fa-user',
                'iconsmall' => 'fa-plus-square',
            ),
            'edituser' => array(
                'category' => 'UserAdmin',
                'tab' => 2,
                'name' => get_string('edituser', 'block_iomad_company_admin'),
                'url' => 'editusers.php',
                'cap' => 'block/iomad_company_admin:user_create',
                'icondefault' => 'useredit',
                'style' => 'user',
                'icon' => 'fa-user',
                'iconsmall' => 'fa-gear',
            ),
            'assignmanagers' => array(
                'category' => 'CompanyAdmin',
                'tab' => 2,
                'name' => get_string('assignmanagers', 'block_iomad_company_admin'),
                'url' => 'company_managers_form.php',
                'cap' => 'block/iomad_company_admin:company_manager',
                'icondefault' => 'assigndepartmentusers',
                'style' => 'department',
                'icon' => 'fa-group',
                'iconsmall' => 'fa-chevron-circle-right'
            ),
            'assignusertocompany' => array(
                'category' => 'UserAdmin',
                'tab' => 2,
                'name' => get_string('assigntocompany', 'block_iomad_company_admin'),
                'url' => 'company_users_form.php',
                'cap' => 'block/iomad_company_admin:company_user',
                'icondefault' => '',
                'style' => 'user',
                'icon' => 'fa-building',
                'iconsmall' => 'fa-chevron-circle-left',
            ),
            'uploadfromfile' => array(
                'category' => 'UserAdmin',
                'tab' => 2,
                'name' => get_string('user_upload_title', 'block_iomad_company_admin'),
                'url' => 'uploaduser.php',
                'cap' => 'block/iomad_company_admin:user_upload',
                'icondefault' => 'up',
                'style' => 'user',
                'icon' => 'fa-file',
                'iconsmall' => 'fa-upload',

            ),
            'downloadusers' => array(
                'category' => 'UserAdmin',
                'tab' => 2,
                'name' => get_string('users_download', 'block_iomad_company_admin'),
                'url' => 'user_bulk_download.php',
                'cap' => 'block/iomad_company_admin:user_upload',
                'icondefault' => 'down',
                'style' => 'user',
                'icon' => 'fa-group',
                'iconsmall' => 'fa-download',
            ),
            'createcourse' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('createcourse', 'block_iomad_company_admin'),
                'url' => 'company_course_create_form.php',
                'cap' => 'block/iomad_company_admin:createcourse',
                'icondefault' => 'createcourse',
                'style' => 'course',
                'icon' => 'fa-file-text',
                'iconsmall' => 'fa-plus-square',
            ),
            'enroluser' => array(
                'category' => 'UserAdmin',
                'tab' => 3,
                'name' => get_string('enroluser', 'block_iomad_company_admin'),
                'url' => 'company_course_users_form.php',
                'cap' => 'block/iomad_company_admin:company_course_users',
                'icondefault' => 'userenrolements',
                'style' => 'course',
                'icon' => 'fa-file-text',
                'iconsmall' => 'fa-user',
            ),
            'managecourses' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('iomad_courses_title', 'block_iomad_company_admin'),
                'url' => 'iomad_courses_form.php',
                'cap' => 'block/iomad_company_admin:viewcourses',
                'icondefault' => 'managecoursesettings',
                'style' => 'course',
                'icon' => 'fa-file-text',
                'iconsmall' => 'fa-gear',
            ),
            'assigntocompany' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('assigntocompany', 'block_iomad_company_admin'),
                'url' => 'company_courses_form.php',
                'cap' => 'block/iomad_company_admin:company_course',
                'icondefault' => 'assigntocompany',
                'style' => 'course',
                'icon' => 'fa-building',
                'iconsmall' => 'fa-chevron-circle-left'
            ),
            'managegroups' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('managegroups', 'block_iomad_company_admin'),
                'url' => 'company_groups_create_form.php',
                'cap' => 'block/iomad_company_admin:edit_groups',
                'icondefault' => 'groupsedit',
                'style' => 'group',
                'icon' => 'fa-group',
                'iconsmall' => 'fa-gear',
            ),
            'assigngroups' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('assigncoursegroups', 'block_iomad_company_admin'),
                'url' => 'company_groups_users_form.php',
                'cap' => 'block/iomad_company_admin:assign_groups',
                'icondefault' => 'groupsassign',
                'style' => 'group',
                'icon' => 'fa-group',
                'iconsmall' => 'fa-plus-square',
            ),
            'classrooms' => array(
                'category' => 'CourseAdmin',
                'tab' => 3,
                'name' => get_string('classrooms', 'block_iomad_company_admin'),
                'url' => 'classroom_list.php',
                'cap' => 'block/iomad_company_admin:classrooms',
                'icondefault' => 'teachinglocations',
                'style' => 'company',
                'icon' => 'fa-map-marker',
                'iconsmall' => 'fa-gear',
            ),
            'manageiomadlicenses' => array(
                'category' => 'LicenseAdmin',
                'tab' => 4,
                'name' => get_string('managelicenses', 'block_iomad_company_admin'),
                'url' => 'company_license_list.php',
                'cap' => 'block/iomad_company_admin:edit_my_licenses',
                'icondefault' => 'licensemanagement',
                'style' => 'license',
                'icon' => 'fa-legal',
                'iconsmall' => 'fa-gear',
            ),
            'licenseusers' => array(
                'category' => 'LicenseAdmin',
                'tab' => 4,
                'name' => get_string('licenseusers', 'block_iomad_company_admin'),
                'url' => 'company_license_users_form.php',
                'cap' => 'block/iomad_company_admin:allocate_licenses',
                'icondefault' => 'userlicenseallocations',
                'style' => 'license',
                'icon' => 'fa-legal',
                'iconsmall' => 'fa-user'
            ),
            'iomadframeworksettings' => array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('frameworksettings', 'block_iomad_company_admin'),
                'url' => 'iomad_frameworks_form.php',
                'cap' => 'block/iomad_company_admin:manageframeworks',
                'icondefault' => 'managecoursesettings',
                'style' => 'competency',
                'icon' => 'fa-list',
                'iconsmall' => 'fa-cog'
            ),
            'companytemplates' => array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('companytemplates', 'block_iomad_company_admin'),
                'url' => 'company_competency_templates_form.php',
                'cap' => 'block/iomad_company_admin:company_template',
                'icondefault' => 'assigntocompany',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-chevron-circle-right'
            ),
            'iomadtemplatesettings' => array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('templatesettings', 'block_iomad_company_admin'),
                'url' => 'iomad_templates_form.php',
                'cap' => 'block/iomad_company_admin:managetemplates',
                'icondefault' => 'managecoursesettings',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-cog'
            ),
            'edittemplates' => array(
                'category' => 'CompetencyAdmin',
                'tab' => 5,
                'name' => get_string('templates', 'tool_lp'),
                'url' => '/admin/tool/lp/learningplans.php?pagecontextid=1',
                'cap' => 'block/iomad_company_admin:templateview',
                'icondefault' => 'userenrolements',
                'style' => 'competency',
                'icon' => 'fa-cubes',
                'iconsmall' => 'fa-eye'
            )
        );
}
