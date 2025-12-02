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
 * This file contains mappings for classes that have been renamed.
 *
 * @package local_iomad
 * @copyright 2025 e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author Derick Turner
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$renamedclasses = [
    'iomad' => 'local_iomad\iomad',
    'company' => 'local_iomad\company',
    'company_user' => 'local_iomad\company_user',
    'all_department_course_selector' => 'local_iomad\course_selector\all_department',
    'any_course_selector' => 'local_iomad\course_selector\any',
    'course_selector_base' => 'local_iomad\course_selector\base',
    'current_company_course_selector' => 'local_iomad\course_selector\current_company',
    'current_company_course_user_selector' => 'local_iomad\course_selector\current_user',
    'current_company_frameworks_selector' => 'local_iomad\framework_selector\current_company',
    'current_company_group_user_selector' => 'local_iomad\user_selector\current_group',
    'current_company_managers_user_selector' => 'local_iomad\user_selector\current_manager',
    'current_company_templates_selector' => 'local_iomad\template_selector\current_company',
    'current_company_thread_user_selector' => 'local_iomad\user_selector\current_thread',
    'current_company_users_user_selector' => 'local_iomad\user_selector\current_company',
    'current_department_user_selector' => 'local_iomad\user_selector\current_department',
    'current_license_user_selector' => 'local_iomad\license_selector\current_user',
    'current_user_course_selector' => 'local_iomad\user_selector\current_course',
    'current_user_license_course_selector' => 'local_iomad\user_selector\current_license',
    'framework_selector_base' => 'local_iomad\framework_selector\base',
    'potential_company_course_selector' => 'local_iomad\course_selector\potential_company',
    'potential_company_course_user_selector' => 'local_iomad\course_selector\potential_user',
    'potential_company_frameworks_selector' => 'local_iomad\framework_selector\potential_company',
    'potential_company_group_user_selector' => 'local_iomad\user_selector\potential_group',
    'potential_company_managers_user_selector' => 'local_iomad\user_selector\potential_manager',
    'potential_company_templates_selector' => 'local_iomad\template_selector\potential_company',
    'potential_company_thread_user_selector' => 'local_iomad\user_selector\potential_thread',
    'potential_company_users_user_selector' => 'local_iomad\user_selector\potential_company',
    'potential_department_user_selector' => 'local_iomad\user_selector\potential_department',
    'potential_license_user_selector' => 'local_iomad\license_selector\potential_user',
    'potential_subdepartment_course_selector' => 'local_iomad\course_selector\potential_subdepartment',
    'potential_user_course_selector' => 'local_iomad\user_selector\potential_course',
    'potential_user_license_course_selector' => 'local_iomad\user_selector\potential_license',
    'template_selector_base' => 'local_iomad\template_selector\base',
];
