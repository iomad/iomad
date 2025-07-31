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

namespace local_iomadcustompage\custom_context;

use coding_exception;
use context;
use context_system;
use core\exception\moodle_exception;
use dml_exception;
use dml_transaction_exception;
use moodle_url;
use stdClass;

/**
 *  context_iomadcustompage.php description here.
 *
 * @package     local_iomadcustompage
 * @copyright   2024 BitAscii Solutions <bitascii.dev@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class context_iomadcustompage extends context {
    /**
     * Please use context_iomadcustompage::instance($pageid) if you need the instance of context.
     * Alternatively if you know only the context id use context::instance_by_id($contextid)
     *
     * @param stdClass $record
     * @throws coding_exception
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);
        if ($record->contextlevel != CONTEXT_CUSTOMPAGE) {
            throw new coding_exception('Invalid $record->contextlevel in context_iomadcustompage constructor.');
        }
    }

  /**
   * Returns human readable context level name.
   *
   * @return string the human-readable context level name.
   * @throws coding_exception
   */
    public static function get_level_name() {
        return get_string('iomadcustompage', 'local_iomadcustompage');
    }

  /**
   * Returns human readable context identifier.
   *
   * @param bool $withprefix
   * @param bool $short
   * @param bool $escape
   * @return string the human-readable context name.
   * @throws dml_exception
   * @throws coding_exception
   */
    public function get_context_name($withprefix = true, $short = false, $escape = true) {
        global $DB;

        $name = '';
        if ($iomadcustompage = $DB->get_record('local_iomadcustompages', ['id' => $this->_instanceid])) {
            if ($withprefix) {
                $name = get_string('iomadcustompage', 'local_iomadcustompage').': ';
            }
            $name .= format_string($iomadcustompage->name, true, ['context' => $this]);
        }
        return $name;
    }

  /**
   * Returns the most relevant URL for this context.
   *
   * @return moodle_url
   * @throws moodle_exception
   */
    public function get_url() {
        return new moodle_url('/local/iomadcustompage/index.php', ['pageid' => $this->_instanceid]);
    }

  /**
   * Returns an array of relevant context capability records.
   *
   * @param string $sort
   * @return array
   * @throws dml_exception
   */
    public function get_capabilities(string $sort = self::DEFAULT_CAPABILITY_SORT) {
        global $DB;

        return $DB->get_records_list('capabilities', 'contextlevel', [
        CONTEXT_CUSTOMPAGE,
        CONTEXT_BLOCK,
        ], $sort);
    }

  /**
   * Returns iomadcustompage context instance.
   *
   * @param int $pageid id from {local_iomadcustompages} table
   * @param int $strictness
   * @return context|bool context instance
   * @throws coding_exception
   * @throws dml_exception
   */
    public static function instance(int $pageid, int $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_CUSTOMPAGE, $pageid)) {
            return $context;
        }

        if (!$record = $DB->get_record('context', ['contextlevel' => CONTEXT_CUSTOMPAGE, 'instanceid' => $pageid])) {
            if ($iomadcustompage = $DB->get_record('local_iomadcustompages', ['id' => $pageid], 'id,parent', $strictness)) {
                if ($iomadcustompage->parent) {
                    $parentcontext = self::instance($iomadcustompage->parent);
                    $record = context::insert_context_record(CONTEXT_CUSTOMPAGE, $iomadcustompage->id, $parentcontext->path);
                } else {
                    // IOMAD
                    $PATH = '/' . SYSCONTEXTID;
                    $companyid = \iomad::get_my_companyid(context_system::instance());
                    if ($companyid > 0) {
                        $companycontext = \core\context\company::instance($companyid);
                        $PATH = $companycontext->path;
                    }
                    $record = context::insert_context_record(CONTEXT_CUSTOMPAGE, $iomadcustompage->id, $PATH);
                }
            }
        }

        if ($record) {
            $context = new context_iomadcustompage($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

  /**
   * Returns immediate child contexts of pages and all sub-pages,
   * children of sub-pages are not returned.
   *
   * @return array
   * @throws dml_exception
   */
    public function get_child_contexts() {
        global $DB;

        if (empty($this->_path) || empty($this->_depth)) {
            debugging('Can not find child contexts of context '.$this->_id.' try rebuilding of context paths');
            return [];
        }

        $sql = "SELECT ctx.*
                  FROM {context} ctx
                 WHERE ctx.path LIKE ? AND (ctx.depth = ? OR ctx.contextlevel = ?)";
        $params = [$this->_path.'/%', $this->depth + 1, CONTEXT_CUSTOMPAGE];
        $records = $DB->get_records_sql($sql, $params);

        $result = [];
        foreach ($records as $record) {
            $result[$record->id] = context::create_instance_from_record($record);
        }

        return $result;
    }

  /**
   * Creates context level instances for custom pages that do not yet have an associated context record.
   *
   * @return void
   * @throws dml_exception If a database operation fails.
   */
    protected static function create_level_instances() {
        global $DB;

        if ($DB->get_manager()->table_exists('local_iomadcustompages')) {
            $sql = "SELECT ".CONTEXT_CUSTOMPAGE.", sp.id
                      FROM {local_iomadcustompages} sp
                     WHERE NOT EXISTS (SELECT 'x'
                                         FROM {context} cx
                                        WHERE sp.id = cx.instanceid AND cx.contextlevel=".CONTEXT_CUSTOMPAGE.")";
            $contextdata = $DB->get_recordset_sql($sql);
            foreach ($contextdata as $context) {
                context::insert_context_record(CONTEXT_CUSTOMPAGE, $context->id, null);
            }
            $contextdata->close();
        }
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        global $DB;

        $sql = " SELECT c.*
                 FROM {context} c
                 WHERE 1=2";

        if ($DB->get_manager()->table_exists('local_iomadcustompages')) {
            $sql = "
                      SELECT c.*
                        FROM {context} c
             LEFT OUTER JOIN {local_iomadcustompages} sp ON c.instanceid = sp.id
                       WHERE sp.id IS NULL AND c.contextlevel = ".CONTEXT_CUSTOMPAGE."
                   ";

        }

        return $sql;
    }

  /**
   * Rebuild context paths and depths at iomadcustompage context level.
   *
   * @param bool $force
   * @throws \core\exception\coding_exception
   * @throws dml_transaction_exception
   * @throws dml_exception
   */
    protected static function build_paths($force) {
        global $DB;

        $syscontextid = SYSCONTEXTID;

        if ($force ||
                $DB->record_exists_select('context', "contextlevel = ".CONTEXT_CUSTOMPAGE." AND (depth = 0 OR path IS NULL)")) {
            if ($force) {
                $ctxemptyclause = $emptyclause = '';
            } else {
                $ctxemptyclause = "AND (ctx.path IS NULL OR ctx.depth = 0)";
                $emptyclause    = "AND ({context}.path IS NULL OR {context}.depth = 0)";
            }

            $base = '/'.SYSCONTEXTID;

            // Normal top-level pages.
            // This will be used when we allow creating hierarchical iomadcustompages. For now we only have flat ones
            //$sql = "UPDATE {context}
            //           SET depth=2,
            //               path=".$DB->sql_concat("'$base/'", 'id')."
            //         WHERE contextlevel=".CONTEXT_CUSTOMPAGE."
            //               AND EXISTS (SELECT 'x'
            //                             FROM {local_iomadcustompages} sp
            //                            WHERE sp.id = {context}.instanceid AND sp.depth=1)
            //               $emptyclause";
            //$DB->execute($sql);

            // Deeper pages - one query per depthlevel.
            // This will be used when we allow creating hierarchical iomadcustompages. For now we only have flat ones, so hardcoding max depth to 2
            //$maxdepth = $DB->get_field_sql("SELECT MAX(depth) FROM {local_iomadcustompages}");
            $maxdepth = 2;
            $syscontextid = context_system::instance()->id;
            for ($n = 2; $n <= $maxdepth; $n++) {
                $sql = "INSERT INTO {context_temp} (id, path, depth, locked)
                        SELECT ctx.id, ".$DB->sql_concat('pctx.path', "'/'", 'ctx.id').", pctx.depth+1, ctx.locked
                          FROM {context} ctx
                          JOIN {local_iomadcustompages} sp
                            ON (sp.id = ctx.instanceid AND ctx.contextlevel = ".CONTEXT_CUSTOMPAGE.")
                          JOIN {context} pctx ON (pctx.instanceid = $syscontextid AND pctx.contextlevel = ".CONTEXT_SYSTEM.")
                         WHERE pctx.path IS NOT NULL AND pctx.depth > 0
                               $ctxemptyclause";
                $trans = $DB->start_delegated_transaction();
                $DB->delete_records('context_temp');
                $DB->execute($sql);
                context::merge_context_temp_table();
                $DB->delete_records('context_temp');
                $trans->allow_commit();
            }
        }
    }
}

