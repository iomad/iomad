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
 * External functions and service declaration for IOMAD Approve Training Events
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    block_iomad_approve_access
 * @category   webservice
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'block_iomad_approve_access_approve' => [
        'classname' => block_iomad_approve_access\external\approve::class,
        'description' => 'Approve request',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_approve_access:approve',
    ],

    'block_iomad_approve_access_deny' => [
        'classname' => block_iomad_approve_access\external\deny::class,
        'description' => 'Deny approval request',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_approve_access:approve',
    ],
];

$services = [
];
