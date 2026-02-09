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
 * Transforms data to a percentage label.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;
/**
 * Transforms data to a percentage label.
 *
 * @package block_dash
 */
class percent_attribute extends abstract_field_attribute {

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     * @throws \coding_exception
     */
    public function transform_data($data, \stdClass $record) {

        if (($percentfor = $this->get_option('outof'))) {
            $data = $data / $percentfor;
        }

        return round($data * 100) . '%';
    }

    /**
     * Need custom value for transform data, which table uses the attribute dynamically.
     *
     * @return bool
     */
    public function is_needs_construct_data() {
        return true;
    }

    /**
     * Set the options before transform the data. this will usefull for dynamic field setup.
     *
     * @param string $field
     * @param int $customvalue
     *
     * @return void
     */
    public function set_transform_field($field, $customvalue=null) {
        $customvalue = $customvalue ?: 100;
        $customvalue = intval($customvalue);
        $this->set_option('outof', $customvalue);
    }
}
