<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * IOMAD certificate activity
 *
 * @package   mod_iomadcertificate
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This plugin is based on code originally created as mod_certificate by Mark Nelson <markn@moodle.com>.

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'mod/iomadcertificate:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'companycourseeditor' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    'mod/iomadcertificate:view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'companycourseeditor' => CAP_ALLOW,
            'companycoursenoneditor' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],


    'mod/iomadcertificate:viewother' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'companydepartmentmanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'mod/iomadcertificate:manage' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'companycourseeditor' => CAP_ALLOW,
            'companycoursenoneditor' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    'mod/iomadcertificate:printteacher' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'companycourseeditor' => CAP_ALLOW,
            'companycoursenoneditor' => CAP_ALLOW,
        ],
    ],

];
