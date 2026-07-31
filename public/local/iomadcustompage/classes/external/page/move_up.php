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

declare(strict_types=1);

namespace local_iomadcustompage\external\page;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_iomadcustompage\constants;
use local_iomadcustompage\local\iomadcustompage\page as page_helper;
use local_iomadcustompage\manager;
use local_iomadcustompage\permission;
use moodle_exception;

/**
 * External API for moving a page up in sort order
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class move_up extends external_api {
    /**
     * Parameters for execute method
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page ID to move up'),
        ]);
    }

    /**
     * Move a page up in the sort order
     *
     * @param int $pageid Page ID to move up
     * @return array
     * @throws moodle_exception
     */
    public static function execute(int $pageid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
        ]);

        $pageid = $params['pageid'];

        // Get the page and validate permissions.
        $page = manager::get_page_from_id($pageid);
        self::validate_context($page->get_context());
        permission::require_can_edit_page($page);

        // Use the page helper to reorder the page.
        $pagehelper = new page_helper($page);
        $success = $pagehelper->reorder_page($pageid, constants::PAGE_UP);

        if ($success) {
            return [
                'success' => true,
                'message' => get_string('movedsuccess', 'local_iomadcustompage'),
            ];
        } else {
            return [
                'success' => false,
                'message' => get_string('cannotmoveup', 'local_iomadcustompage'),
            ];
        }
    }

    /**
     * Return structure for execute method
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'message' => new external_value(PARAM_TEXT, 'Success or error message'),
        ]);
    }
}
