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
 * Page wrapper class for high-level page operations.
 *
 * This class wraps the page persistent model and provides additional
 * functionality for rendering and managing pages.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_iomadcustompage\local\iomadcustompage;

use local_iomadcustompage\constants;
use local_iomadcustompage\local\helpers\page as page_helper;
use local_iomadcustompage\local\models\page as page_persistent;
use local_iomadcustompage\output\page_contents;
use local_iomadcustompage\output\page_details;
use stdClass;

/**
 * Page wrapper class.
 *
 * Provides high-level operations on custom pages including rendering,
 * deletion, and reordering.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {
    /** @var page_persistent The underlying page persistent model. */
    private page_persistent $pagepersistent;

    /**
     * Constructor.
     *
     * @param page_persistent $page The page persistent model.
     */
    public function __construct(page_persistent $page) {
        $this->page_persistent = $page;
    }

    /**
     * Returns the underlying page persistent model.
     *
     * @return page_persistent The page persistent.
     */
    final public function get_page_persistent(): page_persistent {
        return $this->page_persistent;
    }

    /**
     * Renders the page details output.
     *
     * @return string The rendered HTML for page details.
     */
    public function details_output(): string {
        global $PAGE;

        /** @var \local_iomadcustompage\output\renderer $renderer */
        $renderer = $PAGE->get_renderer('local_iomadcustompage');
        $pagedetails = new page_details($this->get_page_persistent());

        return $renderer->render($pagedetails);
    }

    /**
     * Renders the page content output.
     *
     * @return string The rendered HTML for page contents.
     */
    public function content_output(): string {
        global $PAGE;

        $renderer = $PAGE->get_renderer('local_iomadcustompage');
        $pagecontents = new page_contents($this->get_page_persistent());

        return $renderer->render($pagecontents);
    }

    /**
     * Deletes this page and all associated data.
     *
     * Delegates to page_helper::delete_page() which handles:
     * - Deleting associated audiences
     * - Orphaning child pages
     * - Deleting the page context (blocks)
     * - Deleting the page record
     *
     * All operations are wrapped in a transaction for atomicity.
     *
     * @return bool True if deletion was successful.
     */
    public function delete(): bool {
        return page_helper::delete_page((int) $this->page_persistent->get('id'));
    }

    /**
     * Reorders a page by moving it in the specified direction.
     *
     * This function moves a page to a new position by swapping its sortthread
     * value with the nearest neighbor in the specified direction.
     *
     * @param int $id The ID of the page to be reordered.
     * @param int $direction The direction to move (constants::PAGE_UP or constants::PAGE_DOWN).
     * @return bool True if the reordering was successful, false otherwise.
     */
    public function reorder_page(int $id, int $direction): bool {
        $source = $this->get_page_persistent()->to_record();

        // Get nearest neighbour in direction of move.
        $destid = $this->get_page_adjacent_peer($source, $direction);
        if (!$destid) {
            return false;
        }

        // Delegate to the model's swap method.
        return $this->page_persistent->swap_item_sortthreads($id, $destid);
    }

    /**
     * Retrieves the adjacent peer page in the specified direction.
     *
     * This function finds the next or previous page at the same level in the
     * page hierarchy, based on the sortthread value.
     *
     * @param stdClass $item The page item containing depth, sortthread, parent and id properties.
     * @param int $direction The direction to search (constants::PAGE_UP or constants::PAGE_DOWN).
     * @return int|false Returns the ID of the adjacent page if found, false otherwise.
     */
    public function get_page_adjacent_peer(stdClass $item, int $direction): int|false {
        global $DB;

        // Check that item has required properties.
        if (!isset($item->depth) || !isset($item->sortthread) || !isset($item->id)) {
            return false;
        }

        $depth = $item->depth;
        $sortthread = $item->sortthread;
        $parent = $item->parent ?? null;

        // Are we looking above or below for a peer?
        $sqlop = ($direction == constants::PAGE_UP) ? '<' : '>';
        $sqlsort = ($direction == constants::PAGE_UP) ? 'DESC' : 'ASC';

        // Handle NULL parent correctly.
        if ($parent === null || $parent == 0) {
            $parentsql = "(page.parent IS NULL OR page.parent = 0)";
            $params = [
                'depth' => $depth,
                'sortthread' => $sortthread,
            ];
        } else {
            $parentsql = "page.parent = :parent";
            $params = [
                'depth' => $depth,
                'parent' => $parent,
                'sortthread' => $sortthread,
            ];
        }

        $sql = "SELECT id
                  FROM {local_iomadcustompages} page
                 WHERE page.depth = :depth
                   AND {$parentsql}
                   AND page.sortthread {$sqlop} :sortthread
              ORDER BY page.sortthread {$sqlsort}";

        // Only return first match.
        $dest = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

        return $dest ? (int) $dest->id : false;
    }
}
