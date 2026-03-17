<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_iomad\custom_context;

use coding_exception;
use context_system;
use core\context;
use local_iomad\iomad;
use moodle_url;
use stdClass;

/**
 * Company context class
 *
 * @package   local_iomad
 * @copyright e-Learn Design Ltd. https://www.e-learndesign.co.uk
 * @author    Derick Turner
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 4.3
 */
class context_company extends context {

    /**
     * Please use context_company::instance($companyid) if you need the instance of context.
     * Alternatively if you know only the context id use \core\context::instance_by_id($contextid)
     *
     * @param stdClass $record
     */
    protected function __construct(stdClass $record) {
        parent::__construct($record);

        // Is the context constant already set up?
        if (!defined('CONTEXT_COMPANY')) {
            define('CONTEXT_COMPANY', 13);
        }

        if ($record->contextlevel != CONTEXT_COMPANY) {
            throw new coding_exception('Invalid $record->contextlevel in context_company constructor.');
        }
    }

    /**
     * Returns short context name.
     *
     * @since Moodle 4.2
     *
     * @return string
     */
    public static function get_short_name(): string {
        return 'company';
    }

    /**
     * Returns human readable context level name.
     *
     * @return string the human readable context level name.
     */
    public static function get_level_name() {
        return get_string('company', 'block_iomad_company_admin');
    }

    /**
     * Returns human readable context identifier.
     *
     * @param boolean $withprefix whether to prefix the name of the context with User
     * @param boolean $short does not apply to company context
     * @param boolean $escape does not apply to company context
     * @return string the human readable context name.
     */
    public function get_context_name($withprefix = true, $short = false, $escape = true) {
        global $DB;

        $name = '';
        if ($company = $DB->get_record('local_iomad_companies', ['id' => $this->_instanceid])) {
            if ($withprefix) {
                $name = get_string('company', 'block_iomad_company_admin') . ': ';
            }
            $name .= format_string($company->name, true, ['context' => $this]);
        }
        return $name;
    }

    /**
     * Returns the most relevant URL for this context.
     *
     * @return moodle_url
     */
    public function get_url() {
        $url = new moodle_url('/');
        return $url;
    }

    /**
     * Returns list of all possible parent context levels.
     * @since Moodle 4.2
     *
     * @return int[]
     */
    public static function get_possible_parent_levels(): array {
        return [system::LEVEL];
    }

    /**
     * Returns context instance database name.
     *
     * @return string|null table name for all levels except system.
     */
    protected static function get_instance_table(): ?string {
        return 'company';
    }

    /**
     * Returns list of columns that can be used from behat
     * to look up context by reference.
     *
     * @return array list of column names from instance table
     */
    protected static function get_behat_reference_columns(): array {
        return ['name', 'shortname'];
    }

    /**
     * Returns array of relevant context capability records.
     *
     * @param string $sort
     * @return array
     */
    public function get_capabilities(string $sort = self::DEFAULT_CAPABILITY_SORT) {
        global $DB;

        return $DB->get_records_select('capabilities', "contextlevel = :level", ['level' => CONTEXT_COMPANY], $sort);
    }

    /**
     * Returns company context instance.
     *
     * @param int $companyid id from {local_iomad_companies} table
     * @param int $strictness
     * @return context_company|false context instance
     */
    public static function instance($companyid, $strictness = MUST_EXIST) {
        global $DB;

        if ($context = context::cache_get(CONTEXT_COMPANY, $companyid)) {
            return $context;
        }

        if (!$DB->get_manager()->table_exists('local_iomad_companies')) {
            return context_system::instance();
        }

        // Do we have a valid companyid?
        if (!($companyid > 0) &&
            iomad::has_capability('block/iomad_company_admin:company_view_all', context_system::instance())) {
            redirect(
                new moodle_url(
                    '/blocks/iomad_company_admin/index.php'
                ),
                get_string('pleaseselect', 'block_iomad_company_admin')
            );
        }

        if (!$record = $DB->get_record('context', ['contextlevel' => CONTEXT_COMPANY, 'instanceid' => $companyid])) {
            if ($company = $DB->get_record('local_iomad_companies', ['id' => $companyid], 'id', $strictness)) {
                $record = context::insert_context_record(CONTEXT_COMPANY, $company->id, '/'.SYSCONTEXTID, 0);
            }
        }

        if ($record) {
            $context = new context_company($record);
            context::cache_add($context);
            return $context;
        }

        return false;
    }

    /**
     * Create missing context instances at company context level
     */
    protected static function create_level_instances() {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_iomad_companies')) {
            return;
        }

        $sql = "SELECT " . CONTEXT_COMPANY . ", c.id
                FROM {local_iomad_companies} c
                WHERE 1=1
                AND NOT EXISTS (
                    SELECT 'x'
                    FROM {context} cx
                    WHERE c.id = cx.instanceid
                    AND cx.contextlevel=" . CONTEXT_COMPANY . ")";
        $contextdata = $DB->get_recordset_sql($sql);
        foreach ($contextdata as $context) {
            context::insert_context_record(CONTEXT_COMPANY, $context->id, null);
        }
        $contextdata->close();
    }

    /**
     * Returns sql necessary for purging of stale context instances.
     *
     * @return string cleanup SQL
     */
    protected static function get_cleanup_sql() {
        global $DB;

        if (!$DB->get_manager()->table_exists('local_iomad_companies')) {
            $sql = "
                    SELECT cx.*
                    FROM {context} cx
                    WHERE 1=2";
        } else {

            $sql = "
                     SELECT cx.*
                    FROM {context} cx
                    LEFT OUTER JOIN {local_iomad_companies} c ON (cx.instanceid = c.id)
                    WHERE c.id IS NULL
                    AND cx.contextlevel = " . CONTEXT_COMPANY . "
                   ";
        }

        return $sql;
    }

    /**
     * Rebuild context paths and depths at company context level.
     *
     * @param bool $force
     */
    protected static function build_paths($force) {
        global $DB;

        // First update normal companys.
        $path = $DB->sql_concat('?', 'id');
        $pathstart = '/' . SYSCONTEXTID . '/';
        $params = [$pathstart];

        if ($force) {
            $where = "depth <> 2 OR path IS NULL OR path <> ({$path})";
            $params[] = $pathstart;
        } else {
            $where = "depth = 0 OR path IS NULL";
        }

        $sql = "
                UPDATE {context}
                SET depth = 2,
                path = {$path}
                WHERE contextlevel = " . CONTEXT_COMPANY . "
                AND ($where)";
        $DB->execute($sql, $params);
    }
}
