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
 * @push date 2025/06/13
 */

$plugin->release  = '4.5.3 (Build: 20250317)'; // Human-friendly version name
$plugin->component  = 'local_iomad';
$plugin->requires = 2019052000;   // Requires this Moodle version.
$plugin->version  = 2024100700;   // The (date) version of this plugin.
$plugin->dependencies = [
        'tool_checklearningrecords' => 2024100700,
        'tool_iomadmerge' => 2024100700,
        'tool_iomadpolicy' => 2024100700,
        'tool_iomadsite' => 2024100700,
        'tool_redocerts' => 2024100700,
        'auth_iomadoidc' => 2023100920,
        'auth_iomadsaml2' => 2024090901,
        'availability_company' => 2024100700,
        'availability_trainingevent' => 2024100700,
        'block_iomad_approve_access' => 2024100700,
        'block_iomad_commerce' => 2025061000,
        'block_iomad_company_admin' => 2024100700,
        'block_iomad_company_selector' => 2024100700,
        'block_iomad_html' => 2024100700,
        'block_iomad_learningpath' => 2024100700,
        'block_iomad_link' => 2024100700,
        'block_iomad_microlearning' => 2025011500,
        'block_iomad_onlineusers' => 2024100700,
        'block_iomad_reports' => 2024100700,
        'block_iomad_welcome' => 2024100700,
        'block_mycourses' => 2024100700,
        'enrol_license' => 2024100700,
        'local_course_selector' => 2024100700,
        'local_email' => 2024111902,
        'local_email_reports' => 2024111900,
        'local_framework_selector' => 2024100700,
        'local_iomad_learningpath' => 2024121100,
        'local_iomad_settings' => 2025011800,
        'local_iomad_signup' => 2024100700,
        'local_iomad_track' => 2025050200,
        'local_report_attendance' => 2024100700,
        'local_report_companies' => 2024100700,
        'local_report_completion' => 2024100700,
        'local_report_completion_monthly' => 2024100700,
        'local_report_completion_overview' => 2024100700,
        'local_report_emails' => 2024100700,
        'local_report_license_usage' => 2024100700,
        'local_report_user_license_allocations' => 2024100700,
        'local_report_user_logins' => 2024100700,
        'local_report_users' => 2024100700,
        'local_template_selector' => 2024100700,
        'mod_iomadcertificate' => 2024100700,
        'mod_trainingevent' => 2025012301,
        'theme_iomad' => 2024100700,
        'theme_iomadboost' => 2024100700,
        'theme_iomadbootstrap' => 2024100700];
$plugin->supported = [405, 405];
$plugin->maturity = MATURITY_STABLE;
