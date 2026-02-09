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
 * Transform data by renaming delimited IDs to fields. Such as course name or group name.
 *
 * @package    block_dash
 * @copyright  2020 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;

use dml_exception;

/**
 * Transform data by renaming delimited IDs to fields. Such as course name or group name.
 *
 * @package block_dash
 */
class rename_ids_attribute extends abstract_field_attribute {

    /** @var array stored the fields used in current table.*/
    private static $fieldstore = [];

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data
     * @param \stdClass $record Entire row
     * @return mixed
     * @throws \moodle_exception
     */
    final public function transform_data($data, \stdClass $record) {
        $groups = [];
        if ($data) {
            foreach (explode($this->get_option('delimiter'), $data) as $id) {
                if ($this->check_id($id) && $id != '') {
                    $fields = self::get_fields($this->get_option('table'), $this->get_option('field'));
                    if (isset($fields[$id])) {
                        $groups[] = $fields[$id];
                    }
                }
            }
        }
        return implode($this->get_option('delimiter') . ' ', $groups);
    }

    /**
     * Override in child class to add additional checks per ID.
     *
     * @param int $id
     * @return bool
     */
    public function check_id($id) {
        return true;
    }

    /**
     * Get the fields used in this table.
     *
     * @param string $table
     * @param string $field
     * @return mixed
     * @throws dml_exception
     */
    protected static function get_fields($table, $field) {
        global $DB;

        if (!isset(self::$fieldstore[$table][$field])) {
            if (!isset(self::$fieldstore[$table])) {
                self::$fieldstore[$table] = [];
            }
            self::$fieldstore[$table][$field] = [];

            foreach ($DB->get_recordset($table, null, '', 'id, ' . $field) as $record) {
                self::$fieldstore[$table][$field][$record->id] = $record->$field;
            }
        }

        return self::$fieldstore[$table][$field];
    }
}
