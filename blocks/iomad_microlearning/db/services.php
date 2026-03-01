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
 * External functions and service declaration for IOMAD microlearning threads
 *
 * @package    block_iomad_microlearning
 * @category   webservice
 * @copyright  2026 E-Learn Design https://www.e-learndesign.co.uk
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    'block_iomad_microlearning_clone_thread' => [
        'classname' => block_iomad_microlearning\external\clone_thread::class,
        'description' => 'Clone IOMAD microlearning thread',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_microlearning:thread_clone',
    ],

    'block_iomad_microlearning_delete_group' => [
        'classname' => block_iomad_microlearning\external\delete_group::class,
        'description' => 'Delete IOMAD microlearning group',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_microlearning:manage_groups',
    ],

    'block_iomad_microlearning_delete_nugget' => [
        'classname' => block_iomad_microlearning\external\delete_nugget::class,
        'description' => 'Delete an IOMAD microlearning nugget',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_microlearning:edit_nuggets',
    ],

    'block_iomad_microlearning_delete_thread' => [
        'classname' => block_iomad_microlearning\external\delete_thread::class,
        'description' => 'Delete IOMAD microlearning thread',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_microlearning:thread_delete',
    ],

    'block_iomad_microlearning_move_nugget' => [
        'classname' => block_iomad_microlearning\external\move_nugget::class,
        'description' => 'IOMAD microlearning change nugget order',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'block/iomad_microlearning:edit_nuggets',
    ],
];

$services = [
];
