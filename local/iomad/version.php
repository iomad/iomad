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
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @push date 2025/08/25
 */

$plugin->release  = '5.0.2 (Build: 20250811)';    // Human-friendly version name
$plugin->component  = 'local_iomad';
$plugin->requires = 2025041400;   // Requires this Moodle version.
$plugin->version  = 2025070200.500;   // The (date) version of this plugin.
$plugin->dependencies = [
        'tool_checklearningrecords' => 2025041400,
        'tool_iomadmerge' => 2025041400,
        'tool_iomadpolicy' => 2025041400,
        'tool_iomadsite' => 2025041400,
        'tool_redocerts' => 2025041400,
        'auth_iomadoidc' => 2023100920,
        'auth_iomadsaml2' => 2024090901,
        'availability_company' => 2025041400,
        'availability_trainingevent' => 2025041400,
        'block_iomad_approve_access' => 2025041400,
        'block_iomad_commerce' => 2025061000,
        'block_iomad_company_admin' => 2025041400,
        'block_iomad_company_selector' => 2025041400,
        'block_iomad_html' => 2025041400,
        'block_iomad_learningpath' => 2025041400,
        'block_iomad_link' => 2025041400,
        'block_iomad_microlearning' => 2025041400,
        'block_iomad_onlineusers' => 2025041400,
        'block_iomad_reports' => 2025041400,
        'block_iomad_welcome' => 2025041400,
        'block_mycourses' => 2025041400,
        'enrol_license' => 2025041400,
        'local_course_selector' => 2025041400,
        'local_email' => 2025041400,
        'local_email_reports' => 2025041400,
        'local_framework_selector' => 2025041400,
        'local_iomad_learningpath' => 2025041400,
        'local_iomad_settings' => 2025041400,
        'local_iomad_signup' => 2025041400,
        'local_iomad_track' => 2025050200,
        'local_report_attendance' => 2025041400,
        'local_report_companies' => 2025041400,
        'local_report_completion' => 2025041400,
        'local_report_completion_monthly' => 2025041400,
        'local_report_completion_overview' => 2025041400,
        'local_report_emails' => 2025041400,
        'local_report_license_usage' => 2025041400,
        'local_report_user_license_allocations' => 2025041400,
        'local_report_user_logins' => 2025041400,
        'local_report_users' => 2025041400,
        'local_template_selector' => 2025041400,
        'mod_iomadcertificate' => 2025041400,
        'mod_trainingevent' => 2025041400,
        'theme_iomad' => 2025041400,
        'theme_iomadboost' => 2025041400,
        'theme_iomadbootstrap' => 2025041400];
$plugin->supported = [500, 500];
$plugin->maturity = MATURITY_STABLE;
