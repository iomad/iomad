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
 * Container for structuring data, usually from a database. Supports PHP 8.1 and above.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data;

use context;

/**
 * Container for structuring data, usually from a database.
 *
 * @package block_dash
 */
class data_collection_new implements data_collection_interface, \ArrayAccess {

    /**
     * @var field_interface[]
     */
    private $data = [];

    /**
     * @var array type => [array of collections]
     */
    private $children = [];

    /**
     * @var context
     */
    private $context;

    /**
     * Check if data collection is active.
     *
     * @return bool
     */
    public function is_active() {
        global $PAGE;

        if ($PAGE->context && $context = $this->get_context()) {
            return $PAGE->context->id == $context->id;
        }

        return false;
    }

    /**
     * Set data context.
     * @param context $context
     */
    public function set_context(context $context) {
        $this->context = $context;
    }

    /**
     * Get data context.
     * @return context|null
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get all fields in this data collection.
     *
     * @return field_interface[]
     */
    public function get_data() {
        return array_values($this->data);
    }

    /**
     * Add data to data collection.
     *
     * @param field_interface $field
     */
    public function add_data(field_interface $field) {
        $this->data[$field->get_name()] = $field;
    }

    /**
     * Add raw data to collection.
     *
     * @param array $data Associative array of data
     * @deprecated use add_data instead.
     * @throws \coding_exception
     */
    public function add_data_associative($data) {
        throw new \coding_exception('data_collection::add_data_associative is deprecated');
    }

    /**
     * Get child data collections.
     *
     * @param string $type Name of collection type to return. Null returns all.
     * @return data_collection_interface[]
     */
    public function get_child_collections($type = null) {
        if ($type) {
            if (isset($this->children[$type])) {
                return $this->children[$type];
            }
        } else {
            return array_values($this->children);
        }

        return [];
    }

    /**
     * Add a child data collection.
     *
     * @param string $type Name of collection type.
     * @param data_collection_interface $collection
     */
    public function add_child_collection($type, data_collection_interface $collection) {
        if (!isset($this->children[$type])) {
            $this->children[$type] = [];
        }
        $this->children[$type][] = $collection;
    }

    /**
     * Check if this collection contains any child collection of data.
     *
     * @return bool
     */
    public function has_child_collections() {
        return count($this->children) > 0;
    }

    /**
     * Get first child data collection.
     *
     * @return data_collection_interface
     */
    public function first_child() {
        if ($type = reset($this->children)) {
            return reset($type);
        }

        return null;
    }

    /**
     * Returns the first field of the data array.
     *
     * @return field_interface|null
     */
    public function first_data() {
        return isset(array_values($this->data)[0]) ? array_values($this->data)[0] : null;
    }

    /**
     * Returns the second field of the data array.
     *
     * @return field_interface|null
     */
    public function second_data() {
        return isset(array_values($this->data)[1]) ? array_values($this->data)[1] : null;
    }

    /**
     * Returns true if data collection has no data or child collections.
     *
     * @return bool
     */
    public function is_empty() {
        return empty($this->data) && !$this->has_child_collections();
    }

    /**
     * Whether a offset exists
     * @link https://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset): bool {
        if ($offset == 'data') {
            return true;
        }
        return isset($this->data[$offset]) || isset($this->children[$offset]);
    }

    /**
     * Offset to retrieve
     * @link https://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet(mixed $offset): mixed {
        if ($offset == 'data') {
            return $this->get_data();
        }

        if (isset($this->data[$offset])) {
            return $this->data[$offset]->get_value();
        } else {
            return $this->children[$offset];
        }
    }

    /**
     * Offset to set
     * @link https://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet(mixed $offset, mixed $value): void {
        throw new \coding_exception('Setting data not supported with array access.');
    }

    /**
     * Offset to unset
     * @link https://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset(mixed $offset): void {
        throw new \coding_exception('Unsetting data not supported with array access.');
    }
}
