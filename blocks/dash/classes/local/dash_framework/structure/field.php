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
 * Represents a predefined field that can be added to a data grid.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\dash_framework\structure;

use block_dash\local\data_grid\field\attribute\field_attribute_interface;
use lang_string;
/**
 * Represents a predefined field that can be added to a data grid.
 *
 * Add basic functionality for field definitions.
 *
 * @package block_dash
 */
class field implements field_interface {

    /**
     * @var string The column name of the field as it appears in the table (e.g. firstname).
     */
    private $name;

    /**
     * @var lang_string Human readable name of field (e.g. Firstname).
     */
    private $title;

    /**
     * @var table
     */
    private $table;

    /**
     * @var string|array|null SQL select statement.
     * If left null the name will be used (table_alias.name).
     * An array of different selects based on dbtype is also possible ['select' => '', 'select_pgsql' => ''].
     */
    private $select;

    /**
     * @var int Visibility of the field (if it should be displayed to the user).
     */
    private $visibility;

    /**
     * @var array Arbitrary options belonging to this field.
     */
    private $options = [];

    /**
     * @var field_attribute_interface[]
     */
    private $attributes = [];

    /**
     * @var bool If field should be sorted.
     */
    private $sort = false;

    /**
     * @var string Direction of sort, if sorting.
     */
    private $sortdirection = 'asc';

    /**
     * @var string Optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     */
    private $sortselect;

    /**
     * Constructor.
     *
     * @param string $name The column name of the field as it appears in the table (e.g. firstname).
     * @param lang_string $title Human readable name of field (e.g. Firstname).
     * @param table $table The table this field belongs to.
     * @param string|array|null $select SQL select statement.
     * If left null the name will be used (table_alias.name).
     * An array of different selects based on dbtype is also possible ['select' => '', 'select_pgsql' => ''].
     * @param array $attributes Field attributes to be added immediately.
     * @param array $options Arbitrary options belonging to this field.
     * @param int $visibility Visibility of the field (if it should be displayed to the user).
     * @param string $sortselect
     */
    public function __construct(string $name,
                                lang_string $title,
                                table $table,
                                $select = null,
                                array $attributes = [],
                                $options = [],
                                $visibility = self::VISIBILITY_VISIBLE,
                                $sortselect = null) {
        $this->name = $name;
        $this->title = $title;
        $this->table = $table;
        $this->select = $select;
        $this->visibility = $visibility;
        $this->options = $options;
        $this->sortselect = $sortselect;

        foreach ($attributes as $attribute) {
            $this->add_attribute($attribute);
        }
    }

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     */
    final public function transform_data($data, \stdClass $record) {
        foreach ($this->attributes as $attribute) {
            $data = $attribute->transform_data($data, $record);
        }

        return $data;
    }

    // Region Property methods.

    /**
     * Get the column name of the field as it appears in the table (e.g. firstname).
     *
     * @return string
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Get field alias for query.
     *
     * @return string
     */
    public function get_alias(): string {
        return sprintf('%s_%s', $this->get_table()->get_alias(), $this->get_name());
    }

    /**
     * Human readable name of field (e.g. Firstname).
     *
     * @return lang_string
     */
    public function get_title(): lang_string {
        return $this->title;
    }

    /**
     * Override field title.
     *
     * @param lang_string $title
     */
    public function set_title(lang_string $title): void {
        $this->title = $title;
    }

    /**
     * Get table this field belongs to.
     *
     * @return table
     */
    public function get_table(): table {
        return $this->table;
    }

    /**
     * Get SQL select for this field, minus alias.
     *
     * @return string
     */
    public function get_select(): string {
        global $CFG;

        if (is_null($this->select)) {
            return sprintf('%s.%s', $this->get_table()->get_alias(), $this->get_name());
        }

        if (is_string($this->select)) {
            return $this->select;
        }

        if (isset($this->select['select_' . $CFG->dbtype])) {
            return $this->select['select_' . $CFG->dbtype];
        }

        return $this->select['select'];
    }

    /**
     * Get field visibility.
     *
     * @return int
     */
    public function get_visibility() {
        return $this->visibility;
    }

    /**
     * Set field visibility.
     *
     * @param int $visibility
     */
    public function set_visibility($visibility) {
        // Warn the developer if they have used an invalid visibility.
        // ...@ codeCoverageIgnoreStart.
        if (!in_array($visibility, [self::VISIBILITY_HIDDEN, self::VISIBILITY_VISIBLE])) {
            debugging('Invalid visibility set on field ' . get_class($this) . ': ' . $visibility, DEBUG_DEVELOPER);
            // So the application doesn't break, default to visible.
            $visibility = self::VISIBILITY_VISIBLE;
        }
        // ...@ codeCoverageIgnoreEnd.
        $this->visibility = $visibility;
    }

    // Endregion.

    // Region Attributes.

    /**
     * Add attribute to this field definition.
     *
     * @param field_attribute_interface $attribute
     */
    public function add_attribute(field_attribute_interface $attribute) {
        $attribute->set_field($this);
        $this->attributes[] = $attribute;
    }

    /**
     * Remove attribute to this field definition.
     *
     * @param field_attribute_interface $attribute
     */
    public function remove_attribute(field_attribute_interface $attribute) {
        foreach ($this->attributes as $key => $fsearchattribute) {
            if (get_class($fsearchattribute) === get_class($attribute)) {
                unset($this->attributes[$key]);
            }
        }
    }

    /**
     * Get all attributes associated with this field definition.
     *
     * @return field_attribute_interface[]
     */
    public function get_attributes() {
        return array_values($this->attributes);
    }

    /**
     * Check if field has an attribute type.
     *
     * @param string $classname Full class path to attribute
     * @return bool
     */
    public function has_attribute($classname) {
        foreach ($this->get_attributes() as $fattribute) {
            if (get_class($fattribute) == $classname) {
                return true;
            }
        }

        return false;
    }

    // Endregion.

    // Region Options.

    /**
     * Get a single option.
     *
     * @param string $optname
     * @return mixed|null
     */
    public function get_option($optname) {
        return isset($this->options[$optname]) ? $this->options[$optname] : null;
    }

    /**
     * Set option on field.
     *
     * @param string $name
     * @param string $optvalue
     */
    public function set_option($name, $optvalue) {
        $this->options[$name] = $optvalue;
    }

    /**
     * Set options on field.
     *
     * @param array $options
     */
    public function set_options($options) {
        foreach ($options as $optname => $value) {
            $this->set_option($optname, $value);
        }
    }

    /**
     * Get all options for this field.
     *
     * @return array
     */
    public function get_options() {
        return $this->options;
    }

    // Endregion.

    // Region Sorting.

    /**
     * Set if field should be sorted.
     *
     * @param bool $sort
     * @throws \Exception
     */
    public function set_sort($sort) {

        if (!is_bool($sort)) {
            throw new \Exception('Sort expected to be a boolean.');
        }

        $this->sort = $sort;
    }

    /**
     * Is the field sorted.
     *
     * @return bool
     */
    public function get_sort() {
        return $this->sort;
    }

    /**
     * Set direction sort should happen for this field.
     *
     * @param string $direction
     * @throws \Exception
     */
    public function set_sort_direction($direction) {
        if (!in_array($direction, ['desc', 'asc'])) {
            throw new \Exception('Invalid sort direction: ' . $direction);
        }
        $this->sortdirection = $direction;
    }

    /**
     * Get sort direction.
     *
     * @return string
     */
    public function get_sort_direction() {
        $dir = $this->sortdirection;
        return $dir;
    }

    /**
     * Set optional sort select (ORDER BY <select>), useful for fields that can't sort based on their field name.
     *
     * @param string $select
     */
    public function set_sort_select($select) {
        $this->sortselect = $select;
    }

    /**
     * Return select for ORDER BY.
     *
     * @return string
     */
    public function get_sort_select() {
        $sort = $this->sortselect;
        if (is_null($sort)) {
            return $this->get_select();
        }
        return empty($sort) ? $this->get_alias() : '';
    }

    // Endregion.

    /**
     * Get custom form.
     *
     * @return string
     */
    public function get_custom_form() {
        $html = '<input type="hidden" name="available_fields[' . $this->get_alias()
            . '][enabled]" value="1">';

        $html .= '<input type="text" name="available_fields[' . $this->get_alias()
            . '][title_override]" placeholder="' . get_string('titleoverride', 'block_dash') . '"
            value="' . $this->get_title() . '">';

        return $html;
    }

    /**
     * When an object is cloned, PHP 5 will perform a shallow copy of all of the object's properties.
     * Any properties that are references to other variables, will remain references.
     * Once the cloning is complete, if a __clone() method is defined,
     * then the newly created object's __clone() method will be called, to allow any necessary properties that need to be changed.
     * NOT CALLABLE DIRECTLY.
     *
     * @return void
     * @link https://php.net/manual/en/language.oop5.cloning.php
     */
    public function __clone() {
        // Update attribute references.
        foreach ($this->get_attributes() as $attribute) {
            $attribute->set_field($this);
        }
    }
}
