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
 * Container for structuring data, usually from a database.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data;
/**
 * Container for structuring data, usually from a database.
 *
 * @package block_dash
 */
interface data_collection_interface {
    /**
     * Get all fields in this data collection.
     *
     * @return field_interface[]
     */
    public function get_data();

    /**
     * Add data to data collection.
     *
     * @param field_interface $field
     */
    public function add_data(field_interface $field);

    /**
     * Add raw data to collection.
     *
     * @param array $data Associative array of data
     */
    public function add_data_associative($data);

    /**
     * Get child data collections.
     *
     * @param string $type Name of collection type to return. Null returns all.
     * @return data_collection_interface[]
     */
    public function get_child_collections($type = null);

    /**
     * Add a child data collection.
     *
     * @param string $type Name of collection type.
     * @param data_collection_interface $collection
     */
    public function add_child_collection($type, data_collection_interface $collection);

    /**
     * Check if this collection contains any child collection of data.
     *
     * @return bool
     */
    public function has_child_collections();

    /**
     * Returns true if data collection has no data or child collections.
     *
     * @return bool
     */
    public function is_empty();
}
