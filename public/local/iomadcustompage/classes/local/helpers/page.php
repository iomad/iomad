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

namespace local_iomadcustompage\local\helpers;

use coding_exception;
use core\context;
use context_helper;
use core\invalid_persistent_exception;
use core\persistent;
use dml_exception;
use invalid_parameter_exception;
use local_iomadcustompage\custom_context\context_iomadcustompage;
use local_iomadcustompage\local\models\audience;
use local_iomadcustompage\local\models\page as page_model;
use local_iomadcustompage\manager;
use moodle_url;
use stdClass;
use Throwable;

/**
 * Helper class for manipulating custom pages and their elements
 *
 * @package     local_iomadcustompage
 * @copyright   2021 Paul Holden <paulh@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page {
    /**
     * Create custom page
     *
     * @param stdClass $data
     * @param bool $default If $default is set to true it will populate report with default layout as defined by the selected
     *                      source. These include pre-defined columns, filters and conditions.
     * @return page_model
     */
    public static function create_page(stdClass $data): page_model {
        $data->name = trim($data->name);
        $data->title = trim($data->title);
        $data->iscontainer = (int)($data->iscontainer ?? 0);
        $data->parent = $data->iscontainer ? null : ((int)($data->parent ?? 0) ?: null);

        $pagepersistent = manager::create_page_persistent($data);
        $pageid = $pagepersistent->get('id');
        $context = context_iomadcustompage::instance($pageid);
        $data->id = $pageid;
        $pagepersistent->set('contextid', $context->id);

        $pagepersistent->update();
        return $pagepersistent;
    }

    /**
     * Update custom page
     *
     * @param stdClass $data
     * @return page_model
     * @throws coding_exception
     * @throws invalid_persistent_exception
     * @throws invalid_parameter_exception
     */
    public static function update_page(stdClass $data): page_model {
        $page = page_model::get_record(['id' => $data->id]);
        if ($page === false) {
            throw new invalid_parameter_exception('Invalid page');
        }

        $data->parent = (int)$data->parent;
        // Validate parent page if specified.
        if (!empty($data->parent)) {
            if ($data->parent == $data->id) {
                throw new invalid_parameter_exception('Page cannot be its own parent');
            }

            $parent = page_model::get_record(['id' => $data->parent]);
            if (!$parent) {
                throw new invalid_parameter_exception('Invalid parent page');
            }

            // Check for circular reference.
            if (self::would_create_circular_reference($data->parent, $data->id)) {
                throw new invalid_parameter_exception('Cannot create circular reference');
            }
        }

        $page->set_many([
            'name' => trim($data->name),
            'title' => trim($data->title),
            'parent' => $data->parent ?? null,
            'showinprimarynav' => $data->showinprimarynav ?? 0,
            'iscontainer' => $data->iscontainer,
        ]);
        // Only set parent for content pages.
        if (empty($data->iscontainer)) {
            $page->set('parent', $data->parent ?: null);
        } else {
            $page->set('parent', null);
        }

        $page->update();

        return $page;
    }

    /**
     * Check if setting parent would create circular reference
     *
     * @param int $parentid
     * @param int $pageid
     * @return bool
     * @throws coding_exception
     */
    private static function would_create_circular_reference(int $parentid, int $pageid): bool {
        if ($pageid == 0) {
            return false; // New page, no circular reference possible.
        }

        $current = new page_model($parentid);

        while ($current) {
            if ($current->get('id') == $pageid) {
                return true; // Circular reference found.
            }
            $current = $current->get_page_parent();
        }

        return false;
    }

    /**
     * Get page hierarchy tree
     *
     * @param int|null $parentid
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function get_page_tree(?int $parentid = null): array {
        global $DB;

        $pages = $DB->get_records('local_iomadcustompages', ['parent' => $parentid], 'sortthread ASC');
        $tree = [];

        foreach ($pages as $pagedata) {
            $page = new page_model(0, $pagedata);
            $tree[] = [
            'page' => $page,
            'children' => self::get_page_tree($page->get('id')),
            ];
        }

        return $tree;
    }

    /**
     * Get breadcrumb for a page
     *
     * @param page_model $page
     * @return array
     * @throws \moodle_exception
     * @throws coding_exception
     */
    public static function get_breadcrumb(page_model $page): array {
        $breadcrumb = [];
        $ancestors = $page->get_ancestors();

        foreach ($ancestors as $ancestor) {
            $breadcrumb[] = [
            'name' => $ancestor->get_formatted_name(),
            'url' => new moodle_url('/local/iomadcustompage/view.php', ['id' => $ancestor->get('id')]),
            ];
        }

        $breadcrumb[] = [
          'name' => $page->get_formatted_name(),
          'url' => new moodle_url('/local/iomadcustompage/view.php', ['id' => $page->get('id')]),
        ];

        return $breadcrumb;
    }

    /**
     * Delete page with cascade cleanup.
     *
     * This method handles the complete deletion workflow:
     * 1. Deleting associated audiences
     * 2. Orphaning child pages (setting parent to null)
     * 3. Updating children's hierarchy after orphaning
     * 4. Deleting the context (blocks)
     * 5. Deleting the page record itself
     *
     * All operations are wrapped in a transaction for atomicity.
     *
     * @param int $pageid The page ID to delete
     * @return bool True if deletion was successful
     * @throws invalid_parameter_exception If page does not exist
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function delete_page(int $pageid): bool {
        global $DB;

        $page = page_model::get_record(['id' => $pageid]);
        if ($page === false) {
            throw new invalid_parameter_exception('Invalid page');
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            // 1. Delete associated audiences.
            foreach (audience::get_records(['pageid' => $pageid]) as $audience) {
                $audience->delete();
            }

            // 2. Orphan children and update their hierarchy.
            $children = $DB->get_records('local_iomadcustompages', ['parent' => $pageid]);
            foreach ($children as $child) {
                $DB->set_field('local_iomadcustompages', 'parent', null, ['id' => $child->id]);
                // Reload child with updated parent and recalculate hierarchy.
                $child->parent = null;
                $childpage = new page_model(0, $child);
                $childpage->update_hierarchy();
            }

            // 3. Delete context (blocks attached to this page).
            context_helper::delete_instance(CONTEXT_CUSTOMPAGE, $pageid);

            // 4. Delete the page record.
            $result = $page->delete();

            $transaction->allow_commit();
            return $result;
        } catch (Throwable $e) {
            // Transaction will auto-rollback on exception.
            throw $e;
        }
    }

    /**
     * Helper method for re-ordering given persistents (columns, filters, etc)
     *
     * @param persistent $persistent The persistent we are moving
     * @param persistent[] $persistents The rest of the persistents
     * @param int $position
     * @param string $field The field we need to update
     * @return bool
     */
    private static function reorder_persistents_by_field(
        persistent $persistent,
        array $persistents,
        int $position,
        string $field
    ): bool {

        // Splice into new position.
        array_splice($persistents, $position - 1, 0, [$persistent]);

        $fieldorder = 1;
        foreach ($persistents as $persistent) {
            $persistent->set($field, $fieldorder++)
                ->update();
        }

        return true;
    }

    /**
     * Return editor options for custom page text editors.
     *
     * @param context $context File context for the editor.
     * @return array
     */
    public static function get_page_editor_options(context $context) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/formslib.php');
        return [
          'subdirs' => false,
          'maxbytes' => $CFG->maxbytes,
          'maxfiles' => 0,
          'noclean' => true,
          'changeformat' => 0,
          'trusttext' => true,
          'context' => $context,
        ];
    }
}
