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
use core_external\external_multiple_structure;
use local_iomadcustompage\local\helpers\vancode;
use local_iomadcustompage\manager;
use local_iomadcustompage\permission;
use moodle_exception;

/**
 * External API for updating page sort order
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_sort_order extends external_api {
    /**
     * Parameters for execute method
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Page ID'),
                'Array of page IDs in the desired order'
            ),
            'parentid' => new external_value(PARAM_INT, 'Parent page ID (0 for root pages)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Update the sort order of pages
     *
     * @param array $pageids Array of page IDs in desired order
     * @param int $parentid Parent page ID
     * @return array
     * @throws moodle_exception
     */
    public static function execute(array $pageids, int $parentid = 0): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'pageids' => $pageids,
            'parentid' => $parentid,
        ]);

        $pageids = $params['pageids'];
        $parentid = $params['parentid'];

        // Validate that all pages exist and user has permission to edit them.
        $pages = [];
        foreach ($pageids as $pageid) {
            $page = manager::get_page_from_id($pageid);
            permission::require_can_edit_page($page);

            // Verify the page belongs to the specified parent.
            $actualparent = $page->get('parent') ?? 0;
            if ($actualparent != $parentid) {
                throw new moodle_exception('invalidparent', 'local_iomadcustompage');
            }

            $pages[$pageid] = $page;
        }

        // Start transaction for atomic updates.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Compute target sortthreads for each reordered page.
            $targetsortthreads = [];
            $counter = 1;
            $parentsortthread = null;
            if ($parentid != 0) {
                $parentpage = manager::get_page_from_id($parentid);
                $parentsortthread = $parentpage->get('sortthread');
            }

            foreach ($pageids as $pageid) {
                // Generate new sortthread value using vancode system.
                if ($parentid == 0) {
                    // Root page.
                    $targetsortthreads[$pageid] = vancode::int2vancode($counter);
                } else {
                    // Child page - use parent's sortthread as prefix.
                    $targetsortthreads[$pageid] = $parentsortthread . '.' . vancode::int2vancode($counter);
                }
                $counter++;
            }

            // Phase 1: move each subtree to a unique temporary prefix to avoid collisions.
            $token = bin2hex(random_bytes(4));
            $temporarysortthreads = [];
            $counter = 1;
            foreach ($pageids as $pageid) {
                $page = $pages[$pageid];
                $oldsortthread = (string) $page->get('sortthread');
                $temporarysortthreads[$pageid] = "__tmp_sortthread_{$token}_{$counter}__";
                $page->move_sortthread($oldsortthread, $temporarysortthreads[$pageid]);
                $counter++;
            }

            // Phase 2: move from temporary prefixes to final sortthreads.
            foreach ($pageids as $pageid) {
                $page = $pages[$pageid];
                $page->move_sortthread($temporarysortthreads[$pageid], $targetsortthreads[$pageid]);
            }

            $transaction->allow_commit();

            return [
                'success' => true,
                'message' => get_string('sortorderupdated', 'local_iomadcustompage'),
            ];
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw new moodle_exception('errorsortingpages', 'local_iomadcustompage', '', null, $e->getMessage());
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
