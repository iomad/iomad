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

namespace local_iomadcustompage\local\models;

use coding_exception;
use core\context;
use context_helper;
use core\context\system;
use core\persistent;
use dml_exception;
use Exception;
use lang_string;
use local_iomadcustompage\constants;
use local_iomadcustompage\event\iomadcustompage_created;
use local_iomadcustompage\event\iomadcustompage_deleted;
use local_iomadcustompage\event\iomadcustompage_updated;
use local_iomadcustompage\local\helpers\vancode;
use local_iomadcustompage\manager;
use moodle_exception;
use moodle_url;
use stdClass;

/**
 * Page persistent model.
 *
 * This class represents a custom page stored in the local_iomadcustompages table.
 *
 * LIFECYCLE HOOKS:
 * - after_create: Computes hierarchy (depth, path) and generates sortthread (atomic transaction)
 * - before_update: Preserves field state for dirty checking
 * - after_update: Re-computes hierarchy if parent changes
 * - before_delete: Empty (cascade cleanup handled by page helper)
 * - after_delete: Triggers deletion event
 *
 * IMPORTANT: For page deletion, use \local_iomadcustompage\local\helpers\page::delete_page()
 * to ensure proper cleanup of audiences, child pages, and context (blocks).
 *
 * @package     local_iomadcustompage
 * @copyright   2018 Alberto Lara Hernández <albertolara@moodle.com>
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page extends persistent {
    /** @var string The table name. */
    public const TABLE = 'local_iomadcustompages';
    /** @var int Maximum supported hierarchy depth for custom pages. */
    public const MAX_DEPTH = constants::PAGE_MAX_DEPTH;
    /** @var stdClass Original database row values captured before an update. */
    private stdClass $fieldsbeforeupdate;

    /**
     * Return the definition of the properties of this model
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'name' => [
                'type' => PARAM_CLEANHTML,
                'null' => NULL_NOT_ALLOWED,
            ],
            'title' => [
                'type' => PARAM_CLEANHTML,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'contextid' => [
                'type' => PARAM_INT,
                'default' => static function (): int {
                    return \core\context\system::instance()->id;
                },
                ],
            'parent' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
                'default' => null,
            ],
            'depth' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 1,
            ],
            'path' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'sortthread' => [
                'type' => PARAM_TEXT,
                'null' => NULL_ALLOWED,
                'default' => '',
            ],
            'iscontainer' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'showinprimarynav' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'usercreated' => [
                'type' => PARAM_INT,
                'default' => static function (): int {
                    global $USER;
                    return (int) $USER->id;
                },
            ],
        ];
    }

    /**
     * Get formatted name with hierarchy indentation for display
     *
     * @return string
     * @throws coding_exception
     */
    public function get_formatted_name_with_indent(): string {
        $name = $this->get_formatted_name();
        $depth = $this->get('depth');

        // Add container indicator.
        if ($this->is_container()) {
            $name = '📁 ' . $name;
        }

        if ($depth > 1) {
            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth - 1);
            $name = $indent . '└─ ' . $name;
        }

        return $name;
    }

    /**
     * Return formatted page name
     *
     * @return string
     * @throws coding_exception
     */
    public function get_formatted_name(): string {
        $name = format_text(
            $this->raw_get('name'),
            FORMAT_HTML,
            [
                'context' => $this->get_context(),
                'noclean' => false,
                'filter' => true,
            ]
        );
        $name = format_string($name, true, [
            'context' => $this->get_context(),
        ]);
        return manager::clean_multilang_text($name);
    }

    /**
     * Return page context, used by exporters
     *
     * @return \core\context
     * @throws coding_exception
     */
    public function get_context(): \core\context {
        return \core\context::instance_by_id($this->raw_get('contextid'));
    }

    /**
     * Check if this is a container page
     *
     * @return bool
     * @throws coding_exception
     */
    public function is_container(): bool {
        return (bool) $this->get('iscontainer');
    }

    /**
     * Returns the formatted title of the page.
     *
     * @return string
     * @throws coding_exception
     */
    public function get_formatted_title(): string {
        $title = format_text(
            $this->raw_get('title'),
            FORMAT_HTML,
            [
              'context' => $this->get_context(),
              'noclean' => false,
              'filter' => true,
            ]
        );
        $title = format_string($title, true, ['context' => $this->get_context()]);

        return manager::clean_multilang_text($title);
    }

    /**
     * Get breadcrumb trail for this page
     *
     * @return array Array of page objects from root to current page
     * @throws coding_exception
     */
    public function get_breadcrumb(): array {
        $breadcrumb = $this->get_ancestors();
        $breadcrumb[] = $this;
        return $breadcrumb;
    }

    /**
     * Swap sortthreads of two pages
     *
     * @param int $itemid1 First page ID
     * @param int $itemid2 Second page ID
     * @return bool Success
     * @throws dml_exception
     */
    public function swap_item_sortthreads(int $itemid1, int $itemid2): bool {
        global $DB;

        // Get the item details.
        [$insql, $inparams] = $DB->get_in_or_equal([$itemid1, $itemid2]);
        $items = $DB->get_records_select('local_iomadcustompages', "id $insql", $inparams);

        // Both items must exist.
        if (!isset($items[$itemid1]) || !isset($items[$itemid2])) {
            return false;
        }

        // Items must have the same parent.
        if ($items[$itemid1]->parent != $items[$itemid2]->parent) {
            return false;
        }

        $sortthread1 = $items[$itemid1]->sortthread;
        $sortthread2 = $items[$itemid2]->sortthread;

        // Check for existing temp sortthread.
        if ($DB->record_exists_select('local_iomadcustompages', $DB->sql_like('sortthread', '?'), ['%swaptemp%'])) {
            return false;
        }

        $transaction = $DB->start_delegated_transaction();

        $status = true;
        // Use placeholder when moving things around.
        $status = $status && $this->move_sortthread($sortthread1, 'swaptemp');
        $status = $status && $this->move_sortthread($sortthread2, $sortthread1);
        $status = $status && $this->move_sortthread('swaptemp', $sortthread2);

        if (!$status) {
            throw new Exception('Error when swapping sortthreads');
        }
        $transaction->allow_commit();

        return true;
    }

    /**
     * Move sortthread and all children to a new position
     *
     * @param string $oldsortthread Old sortthread
     * @param string $newsortthread New sortthread
     * @return bool Success
     * @throws dml_exception
     */
    public function move_sortthread(string $oldsortthread, string $newsortthread): bool {
        global $DB;

        $lengthsql = $DB->sql_length("'$oldsortthread'");
        $substrsql = $DB->sql_substr('sortthread', "$lengthsql + 1");
        $sortthread = $DB->sql_concat(":newsortthread", $substrsql);
        $params = ['newsortthread' => $newsortthread, 'oldsortthread' => $oldsortthread,
            'oldsortthreadmatch' => "{$oldsortthread}%"];
        $sql = "UPDATE {local_iomadcustompages}
            SET sortthread = $sortthread
            WHERE (sortthread = :oldsortthread OR
            " . $DB->sql_like('sortthread', ':oldsortthreadmatch') . ')';

        return $DB->execute($sql, $params);
    }

    /**
     * Check if this is a content page (not a container)
     *
     * @return bool
     * @throws coding_exception
     */
    public function is_content_page(): bool {
        return !$this->is_container();
    }

    /**
     * Returns a moodle_url object pointing to the page view script
     *
     * @return moodle_url
     * @throws moodle_exception
     * @throws coding_exception
     */
    public function get_url(): moodle_url {
        return new moodle_url('/local/iomadcustompage/view.php', ['id' => $this->get('id')]);
    }

    /**
     * Hook executed after page creation.
     *
     * Computes hierarchy (depth, path) and generates sortthread for the new page.
     * All DB operations are wrapped in a transaction for atomicity.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function after_create(): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        try {
            // On create, compute only this node's hierarchy (no children yet).
            $this->update_hierarchy(false);
            $this->generate_sortthread();
            $transaction->allow_commit();
        } catch (\Throwable $e) {
            // Transaction will auto-rollback on exception.
            throw $e;
        }

        // Event triggered outside transaction (events shouldn't be transactional).
        iomadcustompage_created::create_from_object($this)->trigger();
    }

    /**
     * Preserve the current persistent state before an update.
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function before_update(): void {
        // Check if parent or container status is changing.
        $this->preserve_fields_before_update();
    }

    /**
     * Throw report updated event when persistent is updated
     *
     * @param bool $result
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function after_update($result): void {
        if ($this->is_parent_dirty()) {
            // Perform an efficient, targeted move operation.
            $this->move_to_parent($this->get('parent'));
        }
        iomadcustompage_updated::create_from_object($this)->trigger();
    }


    /**
     * Hook executed before page deletion.
     * Cascade cleanup (audiences, children, context) is handled by
     * \local_iomadcustompage\local\helpers\page::delete_page() for explicit control.
     * This hook is intentionally minimal.
     */
    protected function before_delete(): void {
        // Intentionally empty.
        // Cascade cleanup is handled by page_helper::delete_page() which wraps audience deletion,
        // child orphaning, and context cleanup in a transaction.
    }

    /**
     * Throw report deleted event when persistent is deleted
     *
     * @param bool $result
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function after_delete($result): void {
        // No need to update sortthreads - remaining pages maintain their positions.
        // New pages will get the next available sortthread automatically.
        iomadcustompage_deleted::create_from_object($this)->trigger();
    }

    /**
     * Update hierarchy information (depth and path) for this page.
     *
     * Computes the page depth by traversing up the parent chain,
     * and updates the path field accordingly. Optionally cascades
     * updates to all child pages.
     *
     * @param bool $cascade Whether to also update children's hierarchy (default: true)
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update_hierarchy(bool $cascade = true): void {
        $depth = 1;
        $current = $this;
        $visited = [$this->get('id')];

        while ($parent = $current->get_page_parent()) {
            if (in_array($parent->get('id'), $visited)) {
                // Circular reference detected, break the loop.
                break;
            }

            $depth++;
            $visited[] = $parent->get('id');
            $current = $parent;

            if ($depth > constants::PAGE_MAX_DEPTH) {
                break;
            }
        }

        $this->raw_set('depth', $depth);
        $this->update_path();
        if ($cascade) {
            $this->update_children_hierarchy();
        }
    }

    /**
     * Get parent page
     *
     * @return page|null
     * @throws coding_exception
     */
    public function get_page_parent(): ?page {
        if (!$this->get('parent')) {
            return null;
        }
        return new page($this->get('parent'));
    }

    /**
     * Update page hierarchy path
     *
     * @throws coding_exception
     * @throws dml_exception
     */
    public function update_path(): void {
        global $DB;

        $path = '';

        // Use parent path if available, otherwise build from ancestors.
        if ($parent = $this->get_page_parent()) {
            $path = $parent->get('path');
            if (!$path) {
                $ancestors = $this->get_ancestors();

                foreach ($ancestors as $ancestor) {
                    $path .= '/' . $ancestor->get('id');
                }
            }
            $path .= '/' . $this->get('id');
        }

        $this->raw_set('path', $path);

        // Save without triggering events to avoid recursion.
        $DB->set_field('local_iomadcustompages', 'path', $path, ['id' => $this->get('id')]);
        $DB->set_field('local_iomadcustompages', 'depth', $this->raw_get('depth'), ['id' => $this->get('id')]);
    }

    /**
     * Get all ancestor pages
     *
     * @return page[]
     * @throws coding_exception
     */
    public function get_ancestors(): array {
        $ancestors = [];
        $current = $this;

        while ($parent = $current->get_page_parent()) {
            array_unshift($ancestors, $parent);
            $current = $parent;
            // Prevent infinite loops in case of data corruption.
            if (count($ancestors) > constants::PAGE_MAX_DEPTH) {
                break;
            }
        }

        return $ancestors;
    }

    /**
     * Update children hierarchy recursively
     */
    private function update_children_hierarchy(): void {
        try {
            $children = $this->get_children();
            foreach ($children as $child) {
                $child->update_hierarchy();
            }
        } catch (Exception $e) {
            // Log error but don't break the update process.
            debugging('Error updating children hierarchy: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Get child pages ordered by sortthread
     *
     * @return page[]
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_children(): array {
        global $DB;

        $records = $DB->get_records('local_iomadcustompages', ['parent' => $this->get('id')], 'sortthread ASC');
        $children = [];

        foreach ($records as $record) {
            $children[] = new page(0, $record);
        }

        return $children;
    }

    /**
     * Generate sortthread for this page using vancode system
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    private function generate_sortthread(): void {
        global $DB;

        $parentid = $this->get('parent');
        $parentsortthread = null;

        if ($parentid) {
            $parent = new page($parentid);
            $parentsortthread = $parent->get('sortthread');
        }

        // Get existing sortthreads at the same level (excluding current page).
        $existingsortthreads = [];
        if ($parentid) {
            $siblings = $DB->get_records_select(
                'local_iomadcustompages',
                'parent = ? AND id != ?',
                [$parentid, $this->get('id')],
                '',
                'sortthread'
            );
        } else {
            $siblings =
                $DB->get_records_select(
                    'local_iomadcustompages',
                    '(parent IS NULL OR parent = 0) AND id != ?',
                    [$this->get('id')],
                    '',
                    'sortthread'
                );
        }

        foreach ($siblings as $sibling) {
            if (!empty($sibling->sortthread)) {
                $existingsortthreads[] = $sibling->sortthread;
            }
        }

        $newsortthread = vancode::get_next_child_sortthread($parentsortthread, $existingsortthreads);

        $this->raw_set('sortthread', $newsortthread);
        // Save directly to avoid triggering events.
        $DB->set_field('local_iomadcustompages', 'sortthread', $newsortthread, ['id' => $this->get('id')]);
    }

    /**
     * Get the current value of parent and iscontainer
     * to check if they have changed before update
     *
     * @throws dml_exception|coding_exception
     */
    private function preserve_fields_before_update(): void {
        global $DB;
        $sql = "SELECT *
              FROM {local_iomadcustompages}
              WHERE id = ?";
        $data = $DB->get_record_sql($sql, [$this->get('id')]);
        $this->fieldsbeforeupdate = $data;
    }

    /**
     * Check if the parent of this page has changed since the last load
     *
     * @return bool True if the parent has changed, false if not or if the parent does not exist
     * @throws coding_exception
     */
    private function is_parent_dirty(): bool {
        return $this->is_field_dirty('parent');
    }

    /**
     * Check if a given field has changed since the last load
     *
     * @param string $field Name of the field to check
     * @return bool True if the field has changed, false if not or if the field does not exist
     * @throws coding_exception
     */
    private function is_field_dirty(string $field): bool {
        if (!isset($this->fieldsbeforeupdate)) {
            return false;
        }

        if (!property_exists($this->fieldsbeforeupdate, $field)) {
            return false;
        }

        // Must not use ?? here as it treats null as unset.
        // A field changing from NULL to a value (or vice versa) should be detected.
        $oldvalue = $this->fieldsbeforeupdate->{$field};
        $newvalue = $this->raw_get($field);

        // Handle null comparison properly.
        if ($oldvalue === null && $newvalue === null) {
            return false;
        }
        if ($oldvalue === null || $newvalue === null) {
            return true;
        }

        return $oldvalue !== $newvalue;
    }

    /**
     * Update sortthread for a moved page (when parent changes)
     * Only affects this page and its descendants - much more efficient
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    private function update_moved_page_sortthread(): void {
        global $DB;

        $parentid = $this->get('parent');
        $parentsortthread = null;

        if ($parentid) {
            $parent = new page($parentid);
            $parentsortthread = $parent->get('sortthread');
        }

        // Get existing sortthreads at the same level to find next available.
        // Must fetch id field to exclude current page from siblings.
        $existingsortthreads = [];
        if ($parentid) {
            $siblings = $DB->get_records('local_iomadcustompages', ['parent' => $parentid], '', 'id, sortthread');
        } else {
            $siblings = $DB->get_records_select('local_iomadcustompages', 'parent IS NULL OR parent = 0', [], '', 'id, sortthread');
        }

        foreach ($siblings as $sibling) {
            // Exclude current page from sibling list.
            if (!empty($sibling->sortthread) && $sibling->id != $this->get('id')) {
                $existingsortthreads[] = $sibling->sortthread;
            }
        }

        // Generate new sortthread for this page.
        $newsortthread = vancode::get_next_child_sortthread($parentsortthread, $existingsortthreads);

        // Update this page's sortthread.
        $this->raw_set('sortthread', $newsortthread);
        $DB->set_field('local_iomadcustompages', 'sortthread', $newsortthread, ['id' => $this->get('id')]);

        // Update all descendants with new sortthread prefix.
        $this->update_children_sortthreads($this->get('id'), $newsortthread);
    }

    /**
     * Move this page to a new parent efficiently and update hierarchy + sortthread for this subtree only.
     *
     * @param int|null $newparentid
     * @throws dml_exception
     * @throws coding_exception
     */
    public function move_to_parent(?int $newparentid): void {
        global $DB;

        // Get old parent - must handle NULL correctly (can't use ?? as it treats null as unset).
        if (isset($this->fieldsbeforeupdate) && property_exists($this->fieldsbeforeupdate, 'parent')) {
            $oldparentid = $this->fieldsbeforeupdate->parent;
        } else {
            $oldparentid = $this->get('parent');
        }

        // If no actual change, nothing to do.
        // Cast both to int for comparison, treating null as 0.
        if ((int)($oldparentid ?? 0) === (int)($newparentid ?? 0)) {
            return;
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            $id = (int)$this->get('id');

            // Get old path and depth - same null-safe handling.
            if (isset($this->fieldsbeforeupdate) && property_exists($this->fieldsbeforeupdate, 'path')) {
                $oldpath = (string)$this->fieldsbeforeupdate->path;
            } else {
                $oldpath = (string)$this->get('path');
            }

            if (isset($this->fieldsbeforeupdate) && property_exists($this->fieldsbeforeupdate, 'depth')) {
                $olddepth = (int)$this->fieldsbeforeupdate->depth;
            } else {
                $olddepth = (int)$this->get('depth');
            }

            // Compute new depth and path for this node.
            $newdepth = 1;
            $parentpath = '';
            if (!empty($newparentid)) {
                $parent = new page((int)$newparentid);
                $newdepth = (int)$parent->get('depth') + 1;
                $parentpath = (string)$parent->get('path');
            }
            // Root pages have empty path, children append '/id' to parent's path.
            $newpath = $parentpath ? ($parentpath . '/' . $id) : '';

            // Persist current node updates without triggering further hooks.
            $this->raw_set('depth', $newdepth);
            $this->raw_set('path', $newpath);
            $DB->set_field('local_iomadcustompages', 'depth', $newdepth, ['id' => $id]);
            $DB->set_field('local_iomadcustompages', 'path', $newpath, ['id' => $id]);

            // Update descendants: adjust depth by delta and replace path prefix.
            $deltadepth = $newdepth - $olddepth;
            $oldprefix = $oldpath === '' ? '/' . $id . '/' : $oldpath . '/';
            $newprefix = $newpath === '' ? '/' . $id . '/' : $newpath . '/';

            // Fetch all descendants by path prefix.
            $likeparam = $oldpath === ''
                ? '/' . $id . '/%'
                : $oldpath . '/%';
            $sql = 'path IS NOT NULL AND ' . $DB->sql_like('path', ':like');
            $descendants = $DB->get_records_select('local_iomadcustompages', $sql, ['like' => $likeparam], '', 'id, path, depth');

            foreach ($descendants as $desc) {
                $oldchildpath = (string)$desc->path;
                // Replace leading prefix.
                if (str_starts_with($oldchildpath, $oldprefix)) {
                    $newchildpath = $newprefix . substr($oldchildpath, strlen($oldprefix));
                } else {
                    // Fallback: if not matched, keep original.
                    $newchildpath = $oldchildpath;
                }
                $DB->set_field('local_iomadcustompages', 'path', $newchildpath, ['id' => $desc->id]);
                $DB->set_field('local_iomadcustompages', 'depth', ((int)$desc->depth) + $deltadepth, ['id' => $desc->id]);
            }

            // Update sortthread for the moved page and its descendants only.
            $this->update_moved_page_sortthread();

            $transaction->allow_commit();
        } catch (\Throwable $e) {
            // Rollback and rethrow.
            $transaction->rollback($e);
            throw $e;
        }
    }

    /**
     * Update children sortthreads recursively using vancode system
     *
     * @param int $parentid Parent page ID
     * @param string $parentsortthread Parent's sortthread
     * @throws dml_exception
     */
    private function update_children_sortthreads(int $parentid, string $parentsortthread): void {
        global $DB;

        $children = $DB->get_records('local_iomadcustompages', ['parent' => $parentid], 'sortthread ASC');
        $childcounter = 1;

        foreach ($children as $child) {
            $childsortthread = $parentsortthread . '.' . vancode::int2vancode($childcounter);
            $DB->set_field('local_iomadcustompages', 'sortthread', $childsortthread, ['id' => $child->id]);

            // Recursively update grandchildren.
            $this->update_children_sortthreads($child->id, $childsortthread);
            $childcounter++;
        }
    }

    /**
     * Check if container status has changed
     *
     * @return bool
     * @throws coding_exception
     */
    private function container_has_changed(): bool {
        return $this->is_field_dirty('iscontainer');
    }

    /**
     * Validate that setting a parent won't create circular reference
     *
     * @param int|null $parentid
     * @return bool|lang_string
     * @throws coding_exception
     */
    protected function validate_parent(?int $parentid): lang_string|bool {
        if (empty($parentid)) {
            return true;
        }

        if ($parentid == $this->get('id')) {
            return new lang_string('cannotbeselfparent', 'local_iomadcustompage');
        }

        try {
            $parent = new page($parentid);

            if (!$parent->is_container()) {
                return new lang_string('parentmustbecontainer', 'local_iomadcustompage');
            }

            if ($parent->get('depth') >= constants::PAGE_MAX_DEPTH) {
                return new lang_string('maxdepthreached', 'local_iomadcustompage');
            }

            if ($this->get('id') && $parent->is_descendant_of($this)) {
                return new lang_string('circularreference', 'local_iomadcustompage');
            }
        } catch (Exception $e) {
            return new lang_string('invalidparent', 'local_iomadcustompage');
        }

        return true;
    }

    /**
     * Check if this page is a descendant of the given page
     *
     * @param page $page
     * @return bool
     * @throws coding_exception
     */
    public function is_descendant_of(page $page): bool {
        return $page->is_ancestor_of($this);
    }

    /**
     * Check if this page is an ancestor of the given page
     *
     * @param page $page
     * @return bool
     * @throws coding_exception
     */
    public function is_ancestor_of(page $page): bool {
        $current = $page->get_page_parent();

        while ($current) {
            if ($current->get('id') == $this->get('id')) {
                return true;
            }
            $current = $current->get_page_parent();
        }

        return false;
    }

    /**
     * IOMAD Custom validation for iscontainer field
     *
     * @param int $value
     * @return bool|lang_string
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function validate_iscontainer(int $value): lang_string|bool {
        // If changing from container to content page, check if it has children.
        if ($this->get('id') > 0 && $value == 0) {
            if ($this->has_children()) {
                return new lang_string('containerhaschildren', 'local_iomadcustompage');
            }
        }

        // If setting as container and has parent, container pages must be root level.
        if ($value == 1 && $this->get('parent')) {
            return new lang_string('containermustberoot', 'local_iomadcustompage');
        }

        return true;
    }

    /**
     * Check if page has children
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function has_children(): bool {
        global $DB;
        return $DB->record_exists('local_iomadcustompages', ['parent' => $this->get('id')]);
    }

    /**
     * Update sortthread for all pages to maintain proper hierarchical order using vancode system
     *
     * @throws dml_exception
     */
    private function update_all_sortthreads(): void {
        global $DB;

        // Get all existing sortthreads to avoid conflicts.
        $existingsortthreads = $DB->get_fieldset_select('local_iomadcustompages', 'sortthread', 'sortthread IS NOT NULL');

        // Get all root pages (parent IS NULL) ordered by current sortthread.
        $rootpagessql = "SELECT *
                      FROM {local_iomadcustompages}
                      WHERE parent IS NULL OR parent = 0
                      ORDER BY id ASC";
        $rootpages = $DB->get_records_sql($rootpagessql);

        $counter = 1;
        foreach ($rootpages as $rootpage) {
            $newsortthread = vancode::int2vancode($counter);
            $DB->set_field('local_iomadcustompages', 'sortthread', $newsortthread, ['id' => $rootpage->id]);

            // Update children of this root page recursively.
            $this->update_children_sortthreads((int)$rootpage->id, $newsortthread);
            $counter++;
        }
    }
}
