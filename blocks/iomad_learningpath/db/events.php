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
 * @package    block_iomad_learningpath
 * @copyright  2016 E-Learn Design (http://www.e-learndesign.co.uk)
 * @author     Derick Turner
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// List of observers.
$observers = [

    [
        'eventname'   => '\block_iomad_company_admin\event\company_license_deleted',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::company_license_deleted',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],

    [
        'eventname'   => '\block_iomad_company_admin\event\company_license_updated',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::company_license_updated',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],

    [
        'eventname'   => '\block_iomad_company_admin\event\user_license_assigned',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::user_license_assigned',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],

    [
        'eventname'   => '\block_iomad_company_admin\event\user_license_unassigned',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::user_license_unassigned',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],

    [
        'eventname'   => '\block_iomad_learningpath\event\user_assigned',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::user_assigned',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],

    [
        'eventname'   => '\block_iomad_learningpath\event\course_added',
        'callback'    => '\block_iomad_learningpath\block_iomad_learningpath_observer::course_added',
        'includefile' => '/blocks/iomad_learningpath/classes/iomad_learningpath_observer.php',
        'internal'    => false,
    ],
];
