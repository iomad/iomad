<?php
// This file is part of The Bootstrap Moodle theme
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
 * Class select_filter.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * Class select_filter.
 *
 * @package block_dash
 */
abstract class select_filter extends filter {

    /**
     * All option value.
     */
    const ALL_OPTION = -1;

    /**
     * Select options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        $this->add_all_option();

        parent::init();
    }

    /**
     * Return a list of operations this filter can handle.
     *
     * @return array
     */
    public function get_supported_operations() {
        return [
            self::OPERATION_EQUAL,
            self::OPERATION_IN_OR_EQUAL,
            self::OPERATION_LIKE,
            self::OPERATION_LIKE_WILDCARD,
        ];
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value() {
        return null;
    }

    /**
     * Conditionally add an "All" option.
     * @throws \coding_exception
     */
    public function add_all_option() {
        $this->add_option(self::ALL_OPTION, get_string('all') . ' ' . $this->get_label());
    }

    /**
     * Add select option.
     *
     * @param mixed $value
     * @param string $label
     */
    public function add_option($value, $label) {
        $this->options[$value] = format_string($label, false);
    }

    /**
     * Add multiple options.
     *
     * @param array $options
     */
    public function add_options($options) {
        foreach ($options as $key => $option) {
            $this->options[$key] = format_string($option, false);
        }
    }

    /**
     * Get selected options.
     * @return array
     */
    public function get_selected_options() {
        // Return raw values.
        return parent::get_values();
    }

    /**
     * Get values from filter based on user selection. All filters must return an array of values.
     *
     * Override in child class to add more values.
     *
     * @return array
     */
    public function get_values() {
        $values = parent::get_values();

        // If 'All' was selected.
        if (count($values) == 1 && $values[0] == self::ALL_OPTION) {
            return [];
        }

        return $values;
    }

    /**
     * Override this method and call it after creating a form element.
     *
     * @param filter_collection_interface $filtercollection
     * @param string $elementnameprefix
     * @throws \Exception
     * @return string
     */
    public function create_form_element(filter_collection_interface $filtercollection,
                                        $elementnameprefix = '') {
        global $OUTPUT;
        $options = $this->options;
        $options = array_filter($options);

        // Display the select box as tags only for grid layouts.
        $tags = ($filtercollection->layout == 'local_dash\layout\cards_layout'
            && count($options) > 1
            && count($options) <= BLOCK_DASH_FILTER_TABS_COUNT
            ) ? true : false;

        // If All option is present, send it to top.
        if (isset($options[self::ALL_OPTION])) {
            $options = [self::ALL_OPTION => $options[self::ALL_OPTION]] + $options;

            if (isset($options[self::ALL_OPTION]) && $tags) {
                $expstring = explode(" ", $options[self::ALL_OPTION]);
                if (isset($expstring[1])) {
                    array_shift($expstring); // Remove first string.
                    $selectlabel = implode(" ", $expstring);
                }
                unset($options[self::ALL_OPTION]);
            }
        }

        $newoptions = [];
        foreach ($options as $value => $label) {
            $newoptions[] = ['value' => $value, 'label' => $label, 'selected' => in_array($value, $this->get_selected_options())];
        }

        $name = $elementnameprefix . $this->get_name();
        return $OUTPUT->render_from_template('block_dash/filter_select', [
            'name' => $name,
            'options' => $newoptions,
            'multiple' => true,
            'tabs' => $tags,
            'label' => $selectlabel ?? '',
        ]);
    }
}
