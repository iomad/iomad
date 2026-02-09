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
 * Class user_profile_field_filter.
 *
 * @package    block_dash
 * @copyright  2019 bdecent gmbh <https://bdecent.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dash\local\data_grid\filter;
/**
 * Class user_profile_field_filter.
 *
 * @package block_dash
 */
class user_profile_field_filter extends select_filter {

    /**
     * @var string Record ID of custom profile field.
     */
    private $profilefieldid;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $select
     * @param int $profilefieldid
     * @param string $label
     */
    public function __construct($name, $select, $profilefieldid, $label = '') {
        $this->profilefieldid = $profilefieldid;

        parent::__construct($name, $select, $label);
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
        ];
    }

    /**
     * Get the default raw value to set on form field.
     *
     * @return mixed
     */
    public function get_default_raw_value() {
        return self::ALL_OPTION;
    }

    /**
     * Initialize the filter. It must be initialized before values are extracted or SQL generated.
     * If overridden call parent.
     */
    public function init() {
        global $DB;

        $params['fieldid'] = $this->profilefieldid;

        $options = $DB->get_records_sql_menu("SELECT uid.data AS key1, uid.data AS key2 FROM {user_info_data} uid
                                              WHERE uid.fieldid = :fieldid
                                              GROUP BY uid.data", $params);

        foreach ($options as $key => $option) {
            $this->add_option($key, $option);
        }

        parent::init();
    }
}
