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
 * Add event handlers for the quiz
 *
 * @package    local_iomad
 * @copyright  2016 E-Learn Design (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = [
    [
        'eventname' => 'block_iomad_company_admin\event\company_course_updated',
        'callback' => 'local_iomad\observer::company_course_updated',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_created',
        'callback' => 'local_iomad\observer::company_created',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_deleted',
        'callback' => 'local_iomad\observer::company_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_license_created',
        'callback' => 'local_iomad\observer::company_license_created',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_license_deleted',
        'callback' => 'local_iomad\observer::company_license_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_license_updated',
        'callback' => 'local_iomad\observer::company_license_updated',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_suspended',
        'callback' => 'local_iomad\observer::company_suspended',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_unsuspended',
        'callback' => 'local_iomad\observer::company_unsuspended',
        'internal' => false,
    ],

    [
        'eventname' => 'local_custompage\event\custompage_deleted',
        'callback' => 'local_iomad\observer::custompage_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_updated',
        'callback' => 'local_iomad\observer::company_updated',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_user_assigned',
        'callback' => 'local_iomad\observer::company_user_assigned',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\company_user_unassigned',
        'callback' => 'local_iomad\observer::company_user_unassigned',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\competency_framework_created',
        'callback' => 'local_iomad\observer::competency_framework_created',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\competency_framework_deleted',
        'callback' => 'local_iomad\observer::competency_framework_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\competency_template_created',
        'callback' => 'local_iomad\observer::competency_template_created',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\competency_template_deleted',
        'callback' => 'local_iomad\observer::competency_template_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\course_completed',
        'callback' => 'local_iomad\observer::course_completed',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\course_updated',
        'callback' => 'local_iomad\observer::course_updated',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_course_expired',
        'callback' => 'local_iomad\observer::user_course_expired',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_created',
        'callback' => 'local_iomad\observer::user_created',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_deleted',
        'callback' => 'local_iomad\observer::user_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_enrolment_created',
        'callback' => 'local_iomad\observer::user_enrolment_created',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_enrolment_deleted',
        'callback' => 'local_iomad\observer::user_enrolment_deleted',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_graded',
        'callback' => 'local_iomad\observer::user_graded',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_license_assigned',
        'callback' => 'local_iomad\observer::user_license_assigned',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_license_unassigned',
        'callback' => 'local_iomad\observer::user_license_unassigned',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_license_used',
        'callback' => 'local_iomad\observer::user_license_used',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_suspended',
        'callback' => 'local_iomad\observer::user_suspended',
        'internal' => false,
    ],

    [
        'eventname' => 'block_iomad_company_admin\event\user_unsuspended',
        'callback' => 'local_iomad\observer::user_unsuspended',
        'internal' => false,
    ],

    [
        'eventname' => 'core\event\user_updated',
        'callback' => 'local_iomad\observer::user_updated',
        'internal' => false,
    ],

    [
     'eventname' => 'tool_langimport\event\langpack_removed',
     'callback' => 'local_iomad\observer::langpack_removed',
     'internal' => false,
    ],

    [
     'eventname' => 'tool_langimport\event\langpack_imported',
     'callback' => 'local_iomad\observer::langpack_imported',
     'internal' => false,
    ],
];
