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
 * Class sql_field_definition.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field;
/**
 * Class sql_field_definition.
 *
 * @package block_dash
 */
class sql_field_definition extends abstract_field_definition {

    /**
     * @var string SQL select statement for this field.
     */
    private $select;

    /**
     * Constructor.
     *
     * @param string $select SQL select statement for this field.
     * @param string $name String identifier of human readable name of field (e.g. Firstname).
     * @param string $title String identifier of human readable name of field (e.g. Firstname).
     * @param int $visibility Visibility of the field (if it should be displayed to the user).
     * @param array $options Arbitrary options belonging to this field.
     */
    public function __construct($select, $name, $title, $visibility = self::VISIBILITY_VISIBLE, $options = []) {
        $this->select = $select;

        parent::__construct($name, $title, $visibility, $options);
    }

    /**
     * Get SQL select statement for this field.
     *
     * @return string
     */
    public function get_select() {
        return $this->select;
    }
}
