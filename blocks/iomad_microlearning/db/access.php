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
 * IOMAD microlearning block capabilities
 *
 * @package   block_iomad_microlearning
 * @copyright 2021 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    'block/iomad_microlearning:addinstance' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
    ],

    'block/iomad_microlearning:myaddinstance' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
    ],

    'block/iomad_microlearning:view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:thread_clone' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:edit_threads' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:import_threads' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:edit_nuggets' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:thread_delete' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:thread_view' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:assign_threads' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:manage_groups' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],

    'block/iomad_microlearning:importgroupfromcsv' => [

        'captype' => 'read',
        'contextlevel' => CONTEXT_COMPANY,
        'archetypes' => [
            'clientadministrator' => CAP_ALLOW,
        ],
    ],
];
