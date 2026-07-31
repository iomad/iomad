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

namespace local_iomadcustompage\local\services;

use coding_exception;
use stdClass;

/**
 * Navigation-related helpers for local_iomadcustompage.
 *
 * @package    local_iomadcustompage
 * @copyright  2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation {
    /**
     * Build page hierarchy from a flat list of page IDs.
     *
     * @param array $pageids
     * @return array
     */
    public static function build_page_hierarchy(array $pageids): array {
        global $DB;

        if (empty($pageids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($pageids);
        $pages = $DB->get_records_select('local_iomadcustompages', "id $insql", $params, 'parent ASC, sortthread ASC');

        $hierarchy = [];
        $pagesbyparent = [];

        foreach ($pages as $page) {
            $parentid = $page->parent ?? 0;
            if (!isset($pagesbyparent[$parentid])) {
                $pagesbyparent[$parentid] = [];
            }
            $pagesbyparent[$parentid][] = $page;
        }

        if (isset($pagesbyparent[0])) {
            foreach ($pagesbyparent[0] as $rootpage) {
                $hierarchy[] = self::build_page_tree_recursive($rootpage, $pagesbyparent);
            }
        }

        return $hierarchy;
    }

    /**
     * Recursively build page tree from grouped pages by parent ID.
     *
     * @param stdClass $page
     * @param array $pagesByParent
     * @return array
     */
    public static function build_page_tree_recursive(stdClass $page, array $pagesbyparent): array {
        $node = [
            'page' => $page,
            'children' => [],
        ];

        if (isset($pagesbyparent[$page->id])) {
            foreach ($pagesbyparent[$page->id] as $childpage) {
                $node['children'][] = self::build_page_tree_recursive($childpage, $pagesbyparent);
            }
        }

        return $node;
    }

    /**
     * Add hierarchical menu items to the custom menu string (legacy behavior).
     *
     * Note: Prefer Moodle navigation nodes where possible.
     *
     * @param array $hierarchy
     * @param int $level
     * @return void
     */
    public static function add_bootstrap_hierarchical_menu_items(array $hierarchy, int $level = 0): void {
        global $CFG;
        // Lazy-load the persistent class only when needed to keep this service generic.
        $pageclass = '\\local_iomadcustompage\\local\\models\\page';

        foreach ($hierarchy as $node) {
            $page = $node['page'];
            if (empty($page->showinprimarynav)) {
                continue;
            }
            // Instantiate page persistent from record.
            $iomadcustompage = new $pageclass(0, $page);
            $pagename = $iomadcustompage->get_formatted_name();
            $pagetitle = $iomadcustompage->get_formatted_title() ?: $pagename;

            $indent = str_repeat('-', $level);
            $menuitem = $indent . $pagetitle . '|/local/iomadcustompage/view.php?id=' . $page->id;

            if ($level > 0) {
                $menuitem .= '|submenu-item level-' . $level;
            } else {
                $menuitem .= '|top-level-item';
            }

            $CFG->custommenuitems .= "\n" . $menuitem . "\n";

            if (!empty($node['children'])) {
                self::add_bootstrap_hierarchical_menu_items($node['children'], $level + 1);
            }
        }
    }
}
