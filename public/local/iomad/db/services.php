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
 * Local IOMAD external functions and service declaration
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    local_iomad
 * @category   webservice
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'local_iomad_restrict_email_template' => [
        'classname' => local_iomad\external\restrict_email_template::class,
        'description' => 'Restrict email template',
        'type' => 'write',
        'ajax' => true,
    ],

    'local_iomad_clear_user_course' => [
        'classname' => local_iomad\external\clear_user_course::class,
        'description' => 'Clear down a user from a course and remove the tracked report data',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/report_users:clearentries',
    ],

    'local_iomad_purge_user_course' => [
        'classname' => local_iomad\external\purge_user_course::class,
        'description' => 'Purge a users course tracked reporting data',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/report_users:deleteentriesfull',
    ],

    'local_iomad_regencert_user_course' => [
        'classname' => local_iomad\external\regencert_user_course::class,
        'description' => 'Regenerate a saved users course certificate',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/report_users:redocertificates',
    ],

    'local_iomad_reset_user_course' => [
        'classname' => local_iomad\external\reset_user_course::class,
        'description' => 'Reset a user in a course',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/report_users:clearentries',
    ],

    'local_iomad_licenserevoke_user_course' => [
        'classname' => local_iomad\external\licenserevoke_user_course::class,
        'description' => 'Revoke an allocated license to user for course',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/report_users:deleteentries',
    ],
];

$services = [
];
