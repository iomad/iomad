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
 * A field is a simple container for a single value within a row/collection.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\data;
/**
 * A field is a simple container for a single value within a row/collection.
 *
 * @package block_dash
 */
class field implements field_interface {

    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed|string
     */
    private $value;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $visible;

    /**
     * Create a new field.
     *
     * @param string $name
     * @param string $value
     * @param string $visible
     * @param string $label
     */
    public function __construct($name, $value, $visible, $label = '') {
        $this->name = $name;
        $this->value = $value;
        $this->visible = $visible;
        $this->label = $label;
    }

    /**
     * Get field name.
     *
     * @return string
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get field value.
     *
     * @return mixed|string
     */
    public function get_value() {
        if (!filter_var($this->value, FILTER_VALIDATE_URL)) {
            return format_text($this->value, FORMAT_HTML, ['noclean' => true]);
        }
        return $this->value;
    }

    /**
     * Get label of field definition.
     *
     * @return string|null
     */
    public function get_label() {
        return $this->label;
    }

    /**
     * Check the field is visible.
     * @return bool
     */
    public function is_visible() {
        return $this->visible;
    }
}
