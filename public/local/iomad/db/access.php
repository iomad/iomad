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
 * Local IOMAD capabilities
 *
 * @package   local_iomad
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/iomad:importtrackfrommoodle' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_list' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_edit' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'companymanager' => CAP_ALLOW,
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_delete' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_add' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_send' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'local/iomad:email_templateset_list' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
];
