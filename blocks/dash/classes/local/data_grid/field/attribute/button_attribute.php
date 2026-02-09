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
 * Transforms URL to button.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\field\attribute;
/**
 * Transforms URL to button.
 *
 * @package block_dash
 */
class button_attribute extends abstract_field_attribute {

    /**
     * After records are relieved from database each field has a chance to transform the data.
     * Example: Convert unix timestamp into a human readable date format
     *
     * @param mixed $data Raw data associated with this field definition.
     * @param \stdClass $record Full record from database.
     * @return mixed
     */
    public function transform_data($data, \stdClass $record) {
        global $OUTPUT;

        if ($data) {
            $arialabel = $this->get_option('aria-label');
            $params = [];
            if (!empty($arialabel) && isset($record->$arialabel)) {
                $params = ['aria-label' => $record->$arialabel];
            }

            if ($label = $this->get_option('label')) {
                return $OUTPUT->single_button($data, $label, 'get', $params);
            }

            // Support for dynamic field setup.
            if (($url = $this->get_option('customurl')) && ($labelfield = $this->get_option('label_field'))) {
                $url = $this->update_placeholders($record, urldecode($url));
                return $OUTPUT->single_button($url, $record->$labelfield, 'get', $params);
            }

            if ($labelfield = $this->get_option('label_field')) {
                return $OUTPUT->single_button($data, $record->$labelfield, 'get', $params);
            }

        }

        return $data;
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
     * @param string $customvalue
     * @return void
     */
    public function set_transform_field($field, $customvalue=null) {
        $this->set_option('label_field', $field);

        if ($customvalue !== null) {
            $this->set_option('customurl', new \moodle_url($customvalue));
        }
    }
}
