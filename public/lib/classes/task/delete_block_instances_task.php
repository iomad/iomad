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

namespace core\task;

use core\context_helper;

/**
 * Ad-hoc task to delete all block instances of an uninstalled block plugin in batches.
 *
 * Queued by {@see \core\plugininfo\block::uninstall_cleanup()} so that sites with a large
 * number of block instances do not spend hours deleting them one by one during upgrade
 * or plugin uninstallation.
 *
 * Deleting the record from the block table hides all remaining instances immediately
 * (pages inner join the block table when loading blocks), so the instances left behind
 * until this task runs are invisible and inert.
 *
 * @package    core
 * @copyright  2026 A K M Safat Shahin <safat.shahin@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_block_instances_task extends adhoc_task {
    /** @var int The number of block instances deleted per batch. */
    public const BATCH_SIZE = 1000;

    /**
     * Create a new instance of the task for the given block.
     *
     * @param string $blockname The block name, without the block_ frankenstyle prefix.
     * @return static
     */
    public static function instance(string $blockname): static {
        $task = new static();
        $task->set_custom_data((object) ['blockname' => $blockname]);
        return $task;
    }

    /**
     * Delete all remaining instances of the uninstalled block in batches.
     */
    public function execute(): void {
        global $DB;

        $blockname = $this->get_custom_data()->blockname;

        if ($DB->record_exists('block', ['name' => $blockname])) {
            // The block plugin has been (re)installed since this task was queued.
            // Deleting its instances now would destroy data belonging to the new installation.
            mtrace("Block '{$blockname}' is installed. Skipping deletion of its instances.");
            return;
        }

        // Plugins implementing the pre_block_delete callback must still see every instance.
        $pluginsfunction = get_plugins_with_function('pre_block_delete');

        $total = 0;
        while ($instances = $DB->get_records('block_instances', ['blockname' => $blockname], 'id', '*', 0, self::BATCH_SIZE)) {
            foreach ($pluginsfunction as $plugins) {
                foreach ($plugins as $pluginfunction) {
                    foreach ($instances as $instance) {
                        $pluginfunction($instance);
                    }
                }
            }

            $this->delete_batch(array_keys($instances));
            $total += count($instances);
            mtrace("Deleted {$total} instances of block '{$blockname}' so far...");
        }

        mtrace("Completed deletion of {$total} instances of block '{$blockname}'.");
    }

    /**
     * Delete a batch of block instances and all their related data.
     *
     * Block contexts containing content (files, comments, ratings, role assignments or
     * repository instances) are deleted through the full context deletion API. All other
     * contexts, the common case by far, are removed with bulk queries.
     *
     * @param int[] $instanceids The block instance ids to delete.
     */
    protected function delete_batch(array $instanceids): void {
        global $DB;

        [$insql, $inparams] = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED, 'biid');
        $ctxparams = array_merge(['contextlevel' => CONTEXT_BLOCK], $inparams);

        $contexts = $DB->get_records_select_menu(
            'context',
            "contextlevel = :contextlevel AND instanceid {$insql}",
            $ctxparams,
            '',
            'id, instanceid',
        );
        $contextids = array_keys($contexts);

        // Find the rare block contexts that actually hold content and delete those properly.
        $contentcontextids = $this->get_contexts_with_content($contextids);
        foreach ($contentcontextids as $contextid) {
            context_helper::delete_instance(CONTEXT_BLOCK, $contexts[$contextid]);
            unset($contexts[$contextid]);
        }
        $contextids = array_keys($contexts);

        $transaction = $DB->start_delegated_transaction();

        if ($contextids) {
            [$ctxinsql, $ctxinparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctxid');
            $DB->delete_records_select('context', "id {$ctxinsql}", $ctxinparams);
        }

        $DB->delete_records_select('block_positions', "blockinstanceid {$insql}", $inparams);
        $DB->delete_records_select('block_instances', "id {$insql}", $inparams);

        $preferences = [];
        foreach ($instanceids as $instanceid) {
            $preferences[] = 'block' . $instanceid . 'hidden';
            $preferences[] = 'docked_block_instance_' . $instanceid;
        }
        $DB->delete_records_list('user_preferences', 'name', $preferences);

        $transaction->allow_commit();

        context_helper::reset_caches();

        // Remove any search index data for the bulk deleted contexts. This mirrors what
        // context::delete() does, but outside the upgrade critical path.
        if (\core_search\manager::is_indexing_enabled()) {
            $engine = \core_search\manager::instance()->get_engine();
            foreach ($contextids as $contextid) {
                try {
                    $engine->delete_index_for_context($contextid);
                } catch (\moodle_exception $e) {
                    debugging('Error deleting search index data for context ' . $contextid . ': ' .
                        $e->getMessage());
                }
            }
        }
    }

    /**
     * Return the subset of the given context ids that have content attached.
     *
     * @param int[] $contextids Candidate context ids.
     * @return int[] Context ids with files, comments, ratings, role assignments or repositories.
     */
    protected function get_contexts_with_content(array $contextids): array {
        global $DB;

        if (!$contextids) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'ctx');

        $found = [];
        $checks = [
            ['files', 'contextid'],
            ['comments', 'contextid'],
            ['rating', 'contextid'],
            ['role_assignments', 'contextid'],
            ['role_capabilities', 'contextid'],
            ['repository_instances', 'contextid'],
        ];
        foreach ($checks as [$table, $field]) {
            $ids = $DB->get_fieldset_select($table, "DISTINCT {$field}", "{$field} {$insql}", $inparams);
            $found = array_merge($found, $ids);
        }

        return array_unique($found);
    }
}
