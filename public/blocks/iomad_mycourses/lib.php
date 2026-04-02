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
 * IOMAD my courses library functions
 *
 * @package   block_iomad_mycourses
 * @copyright 2026 Derick Turner
 * @author    Derick Turner
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Constants for the available tabs
 */
define('BLOCK_IOMAD_MYCOURSES_AVAILABLE_VIEW', 'available');
define('BLOCK_IOMAD_MYCOURSES_INPROGRESS_VIEW', 'inprogress');
define('BLOCK_IOMAD_MYCOURSES_COMPLETED_VIEW', 'completed');
define('BLOCK_IOMAD_MYCOURSES_MANDATORY_VIEW', 'mandatory');

/**
 * Constants for the user preferences sorting options
 * timeline
 */
define('BLOCK_IOMAD_MYCOURSES_SORTING_TITLE', 'coursefullname');
define('BLOCK_IOMAD_MYCOURSES_SORTING_LASTACCESSED', 'timeenrolled');
define('BLOCK_IOMAD_MYCOURSES_SORT_ASC', 'ASC');
define('BLOCK_IOMAD_MYCOURSES_SORT_DESC', 'DESC');

/**
 * Constants for the user preferences view options
 */
define('BLOCK_IOMAD_MYCOURSES_VIEW_CARD', 'card');
define('BLOCK_IOMAD_MYCOURSES_VIEW_LIST', 'list');

/**
 * Get the current user preferences that are available
 *
 * @uses core_user::is_current_user
 *
 * @return array[] Array representing current options along with defaults
 */
function block_iomad_mycourses_user_preferences(): array {
    $preferences['block_iomad_mycourses_user_last_tab'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_IOMAD_MYCOURSES_INPROGRESS_VIEW,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_IOMAD_MYCOURSES_AVAILABLE_VIEW,
            BLOCK_IOMAD_MYCOURSES_INPROGRESS_VIEW,
            BLOCK_IOMAD_MYCOURSES_COMPLETED_VIEW,
            BLOCK_IOMAD_MYCOURSES_MANDATORY_VIEW,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_iomad_mycourses_user_sort_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_IOMAD_MYCOURSES_SORTING_TITLE,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_IOMAD_MYCOURSES_SORTING_TITLE,
            BLOCK_IOMAD_MYCOURSES_SORTING_LASTACCESSED,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_iomad_mycourses_user_sortdir_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_IOMAD_MYCOURSES_SORT_ASC,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_IOMAD_MYCOURSES_SORT_ASC,
            BLOCK_IOMAD_MYCOURSES_SORT_DESC,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_iomad_mycourses_user_view_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => BLOCK_IOMAD_MYCOURSES_VIEW_CARD,
        'type' => PARAM_ALPHA,
        'choices' => [
            BLOCK_IOMAD_MYCOURSES_VIEW_CARD,
            BLOCK_IOMAD_MYCOURSES_VIEW_LIST,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    $preferences['block_iomad_mycourses_user_mandatory_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => false,
        'type' => PARAM_BOOL,
        'choices' => [
            true,
            false,
        ],
        'permissioncallback' => [core_user::class, 'is_current_user'],
    ];

    return $preferences;
}
